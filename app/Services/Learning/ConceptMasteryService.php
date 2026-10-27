<?php

namespace App\Services\Learning;

use App\Models\LearnerMastery;
use App\Models\MasteryHistory;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConceptMasteryService
{
    public const EVIDENCE_TYPE = 'diagnostic_quiz_attempt';

    /**
     * Recalculate mastery for concepts touched by one completed diagnostic attempt.
     *
     * The score is intentionally transparent for R2:
     * weighted correct concept evidence / total concept evidence * 100.
     * No low/medium/high research thresholds are applied here.
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
            || $attempt->quiz->assessment_type !== 'diagnostic'
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
                    ->where('assessment_type', 'diagnostic')))
            ->with('question.learningConcepts')
            ->get();

        return DB::transaction(function () use ($attempt, $answers, $conceptIds): Collection {
            $updated = collect();
            $modelVersion = (string) config('mastery.model_version', 'diagnostic-weighted-v1');
            $minimumEvidence = max(1, (int) config('mastery.minimum_evidence_count', 1));

            foreach ($conceptIds as $conceptId) {
                $totalWeight = 0.0;
                $correctWeight = 0.0;
                $evidenceCount = 0;

                foreach ($answers as $answer) {
                    if (! $answer->question) {
                        continue;
                    }

                    $concept = $answer->question->learningConcepts->firstWhere('id', $conceptId);
                    if (! $concept) {
                        continue;
                    }

                    $weight = max(0.0, (float) ($concept->pivot?->weight ?? 1.0));
                    if ($weight <= 0.0) {
                        continue;
                    }

                    $totalWeight += $weight;
                    $evidenceCount++;

                    if ($answer->is_correct) {
                        $correctWeight += $weight;
                    }
                }

                if ($totalWeight <= 0.0 || $evidenceCount === 0) {
                    continue;
                }

                $score = round(($correctWeight / $totalWeight) * 100, 2);
                $status = $evidenceCount >= $minimumEvidence ? 'observed' : 'insufficient_evidence';

                $mastery = LearnerMastery::query()
                    ->where('student_id', $attempt->student_id)
                    ->where('concept_id', $conceptId)
                    ->lockForUpdate()
                    ->first();

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

                MasteryHistory::firstOrCreate(
                    [
                        'student_id' => $attempt->student_id,
                        'concept_id' => $conceptId,
                        'evidence_type' => self::EVIDENCE_TYPE,
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
                            'calculation' => 'weighted_correctness',
                            'correct_weight' => round($correctWeight, 4),
                            'total_weight' => round($totalWeight, 4),
                            'cumulative_evidence_count' => $evidenceCount,
                        ],
                    ]
                );

                $updated->push($mastery->fresh());
            }

            return $updated;
        });
    }
}
