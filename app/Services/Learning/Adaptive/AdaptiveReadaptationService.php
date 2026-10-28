<?php

namespace App\Services\Learning\Adaptive;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class AdaptiveReadaptationService
{
    public function __construct(
        private AdaptiveRecommendationService $recommendations,
    ) {}

    /**
     * Re-run the active baseline adaptive engine after a formative reassessment
     * has actually changed learner-model evidence.
     *
     * @param Collection<int, \App\Models\LearnerMastery> $updatedMasteries
     * @return Collection<int, \App\Models\AdaptiveRecommendation>
     */
    public function afterMasteryUpdate(QuizAttempt $attempt, Collection $updatedMasteries): Collection
    {
        $attempt->loadMissing(['quiz.course', 'student']);

        if ($updatedMasteries->isEmpty()
            || ! $attempt->quiz
            || $attempt->quiz->assessment_type !== Quiz::ASSESSMENT_FORMATIVE
            || ! $attempt->quiz->course
            || ! $attempt->student) {
            return collect();
        }

        /** @var User $student */
        $student = $attempt->student;

        return $this->recommendations->generateForCourse(
            $student,
            $attempt->quiz->course,
            [
                'trigger' => 'formative_reassessment',
                'quiz_id' => $attempt->quiz_id,
                'attempt_id' => $attempt->id,
                'updated_concept_ids' => $updatedMasteries->pluck('concept_id')->map(fn ($id): int => (int) $id)->values()->all(),
                'mastery_model_version' => (string) config('mastery.model_version', 'diagnostic-formative-weighted-v2'),
            ],
        );
    }
}
