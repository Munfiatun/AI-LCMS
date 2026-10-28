<?php

namespace App\Services\Learning;

use App\Models\LearnerMastery;
use App\Models\MasteryHistory;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConceptMasteryService
{
    public const EVIDENCE_DIAGNOSTIC = 'diagnostic_quiz_attempt';

    public const EVIDENCE_FORMATIVE = 'formative_quiz_attempt';

    /**
     * Recalculate mastery for concepts touched by a completed diagnostic or
     * formative attempt.
     *
     * Diagnostic establishes the initial learner-model baseline. Formative
     * evidence may update an existing baseline, but does not create mastery
     * for a concept that has never been diagnosed. Summative evidence is not
     * part of this learner-model calculation.
     *
     * The calculation remains transparent and versioned:
     * weighted correct concept evidence / total concept evidence * 100.
     *
     * @return Collection<int, LearnerMastery>
     */
    public function updateFromAttempt(QuizAttempt $attempt): Collection
    {
        $attempt->loadMissing([
            'quiz.course',
            'answers.question.learningConcepts',
        ]);

        if (! $attempt->quiz
            || ! in_array($attempt->quiz->assessment_type, [Quiz::ASSESSMENT_DIAGNOSTIC, Quiz::ASSESSMENT_FORMATIVE], true)
            || ! in_array($attempt->status, ['submitted', 'expired'], true)) {
            return collect();
        }

        $conceptIds = $attempt->answers
            ->flatMap(fn (QuizAnswer $answer) => $answer->question?->learningConcepts?->pluck('id') ?? collect())
            ->unique()
            ->values()
            ->map(fn ($id) => (int) $id);

        if ($conceptIds->isEmpty()) {
            return collect();
        }

        $answers = QuizAnswer::query()
            ->whereHas('attempt', fn ($query) => $query
                ->where('student_id', $attempt->student_id)
                ->whereIn('status', ['submitted', 'expired'])
                ->whereHas('quiz', fn ($quizQuery) => $quizQuery
                    ->where('course_id', $attempt->quiz->course_id)
                    ->whereIn('assessment_type', [Quiz::ASSESSMENT_DIAGNOSTIC, Quiz::ASSESSMENT_FORMATIVE])))
            ->with([
                'attempt.quiz:id,course_id,assessment_type',
                'question.learningConcepts',
            ])
            ->get();

        return DB::transaction(function () use ($attempt, $answers, $conceptIds): Collection {
            $updated = collect();
            $modelVersion = (string) config('mastery.model_version', 'diagnostic-formative-weighted-v2');
            $minimumEvidence = max(1, (int) config('mastery.minimum_evidence_count', 1));
            $assessmentWeights = (array) config('mastery.assessment_weights', [
                Quiz::ASSESSMENT_DIAGNOSTIC => 1.0,
                Quiz::ASSESSMENT_FORMATIVE => 1.0,
            ]);
            $requiresBaseline = (bool) config('mastery.formative_requires_diagnostic_baseline', true);

            foreach ($conceptIds as $conceptId) {
                $mastery = LearnerMastery::query()
                    ->where('student_id', $attempt->student_id)
                    ->where('concept_id', $conceptId)
                    ->lockForUpdate()
                    ->first();

                if ($attempt->quiz->assessment_type === Quiz::ASSESSMENT_FORMATIVE
                    && $requiresBaseline
                    && ! $mastery) {
                    continue;
                }

                $baselineAt = $mastery?->created_at;
                $totalWeight = 0.0;
                $correctWeight = 0.0;
                $evidenceCount = 0;
                $assessmentCounts = [
                    Quiz::ASSESSMENT_DIAGNOSTIC => 0,
                    Quiz::ASSESSMENT_FORMATIVE => 0,
                ];

                foreach ($answers as $answer) {
                    if (! $answer->question || ! $answer->attempt?->quiz) {
                        continue;
                    }

                    $concept = $answer->question->learningConcepts->firstWhere('id', $conceptId);
                    if (! $concept) {
                        continue;
                    }

                    $assessmentType = $answer->attempt->quiz->assessment_type;
                    if (! in_array($assessmentType, [Quiz::ASSESSMENT_DIAGNOSTIC, Quiz::ASSESSMENT_FORMATIVE], true)) {
                        continue;
                    }

                    // Formative evidence only counts after the concept has a
                    // diagnostic baseline. This prevents a formative attempt
                    // taken before diagnosis from silently creating a baseline.
                    if ($assessmentType === Quiz::ASSESSMENT_FORMATIVE
                        && (! $baselineAt || ($answer->attempt->submitted_at && $answer->attempt->submitted_at->lt($baselineAt)))) {
                        continue;
                    }

                    $conceptWeight = max(0.0, (float) ($concept->pivot?->weight ?? 1.0));
                    $assessmentWeight = max(0.0, (float) ($assessmentWeights[$assessmentType] ?? 0.0));
                    $weight = $conceptWeight * $assessmentWeight;

                    if ($weight <= 0.0) {
                        continue;
                    }

                    $totalWeight += $weight;
                    $evidenceCount++;
                    $assessmentCounts[$assessmentType]++;

                    if ($answer->is_correct) {
                        $correctWeight += $weight;
                    }
                }

                if ($totalWeight <= 0.0 || $evidenceCount === 0) {
                    continue;
                }

                $score = round(($correctWeight / $totalWeight) * 100, 2);
                $status = $evidenceCount >= $minimumEvidence ? 'observed' : 'insufficient_evidence';
                $beforeScore = $mastery?->mastery_score;

                if (! $mastery) {
                    $mastery = new LearnerMastery([
                        'student_id' => $attempt->student_id,
                        'concept_id' => $conceptId,
                    ]);
                }

                $mastery->fill([
                    'mastery_score' => $score,
                    'status' => $status,
                    'evidence_count' => $evidenceCount,
                    'last_evidence_at' => $attempt->submitted_at ?? now(),
                ])->save();

                $evidenceType = $attempt->quiz->assessment_type === Quiz::ASSESSMENT_FORMATIVE
                    ? self::EVIDENCE_FORMATIVE
                    : self::EVIDENCE_DIAGNOSTIC;

                MasteryHistory::firstOrCreate(
                    [
                        'student_id' => $attempt->student_id,
                        'concept_id' => $conceptId,
                        'evidence_type' => $evidenceType,
                        'evidence_id' => $attempt->id,
                        'model_version' => $modelVersion,
                    ],
                    [
                        'before_score' => $beforeScore,
                        'after_score' => $score,
                        'evidence_count' => $evidenceCount,
                        'evidence_payload' => [
                            'quiz_id' => $attempt->quiz_id,
                            'attempt_id' => $attempt->id,
                            'course_id' => $attempt->quiz->course_id,
                            'assessment_type' => $attempt->quiz->assessment_type,
                            'calculation' => 'weighted_correctness',
                            'correct_weight' => round($correctWeight, 4),
                            'total_weight' => round($totalWeight, 4),
                            'cumulative_evidence_count' => $evidenceCount,
                            'assessment_evidence_counts' => $assessmentCounts,
                            'assessment_weights' => [
                                Quiz::ASSESSMENT_DIAGNOSTIC => (float) ($assessmentWeights[Quiz::ASSESSMENT_DIAGNOSTIC] ?? 0.0),
                                Quiz::ASSESSMENT_FORMATIVE => (float) ($assessmentWeights[Quiz::ASSESSMENT_FORMATIVE] ?? 0.0),
                            ],
                            'model_status' => (string) config('mastery.model_status', 'research_draft'),
                        ],
                    ]
                );

                $updated->push($mastery->fresh());
            }

            return $updated;
        });
    }
}
