<?php

namespace App\Services\Learning\Adaptive;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\LearningConcept;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdaptiveRecommendationService
{
    public function __construct(
        private AdaptiveEngine $engine,
        private AdaptiveAlgorithmRegistry $registry,
    ) {}

    /** @return Collection<int, AdaptiveRecommendation> */
    public function generateForCourse(User $student, Course $course): Collection
    {
        $concepts = LearningConcept::query()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return $concepts->map(fn (LearningConcept $concept): AdaptiveRecommendation => $this->persistDecision(
            $student,
            $course,
            $concept,
            $this->engine->decide($student, $concept),
        ));
    }

    public function persistDecision(
        User $student,
        Course $course,
        LearningConcept $sourceConcept,
        AdaptiveDecision $decision,
    ): AdaptiveRecommendation {
        if ($sourceConcept->course_id !== $course->id) {
            throw new InvalidArgumentException('Source concept does not belong to the supplied course.');
        }

        if ($decision->targetConceptId !== null) {
            $targetBelongsToCourse = LearningConcept::query()
                ->whereKey($decision->targetConceptId)
                ->where('course_id', $course->id)
                ->exists();

            if (! $targetBelongsToCourse) {
                throw new InvalidArgumentException('Target concept does not belong to the supplied course.');
            }
        }

        $algorithm = $this->registry->findConfigured($decision->algorithmCode);

        if ($algorithm->version !== $decision->algorithmVersion) {
            throw new InvalidArgumentException('Decision algorithm version does not match the registered version.');
        }

        $fingerprint = $this->fingerprint($student, $course, $sourceConcept, $decision);

        return DB::transaction(function () use ($student, $course, $sourceConcept, $decision, $algorithm, $fingerprint): AdaptiveRecommendation {
            $existing = AdaptiveRecommendation::query()
                ->where('decision_fingerprint', $fingerprint)
                ->first();

            if ($existing) {
                if ($existing->status !== AdaptiveRecommendation::STATUS_ACTIVE) {
                    AdaptiveRecommendation::query()
                        ->where('student_id', $student->id)
                        ->where('source_concept_id', $sourceConcept->id)
                        ->where('algorithm_version_id', $algorithm->id)
                        ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
                        ->whereKeyNot($existing->id)
                        ->update([
                            'status' => AdaptiveRecommendation::STATUS_SUPERSEDED,
                            'superseded_at' => now(),
                        ]);

                    $existing->update([
                        'status' => AdaptiveRecommendation::STATUS_ACTIVE,
                        'superseded_at' => null,
                    ]);
                }

                return $existing->refresh();
            }

            AdaptiveRecommendation::query()
                ->where('student_id', $student->id)
                ->where('source_concept_id', $sourceConcept->id)
                ->where('algorithm_version_id', $algorithm->id)
                ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
                ->update([
                    'status' => AdaptiveRecommendation::STATUS_SUPERSEDED,
                    'superseded_at' => now(),
                ]);

            return AdaptiveRecommendation::query()->create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'source_concept_id' => $sourceConcept->id,
                'target_concept_id' => $decision->targetConceptId,
                'algorithm_version_id' => $algorithm->id,
                'action' => $decision->action,
                'reason_code' => $decision->reasonCode,
                'decision_fingerprint' => $fingerprint,
                'status' => AdaptiveRecommendation::STATUS_ACTIVE,
                'inputs' => $decision->inputs,
                'metadata' => $decision->metadata,
                'generated_at' => now(),
            ]);
        });
    }

    private function fingerprint(
        User $student,
        Course $course,
        LearningConcept $sourceConcept,
        AdaptiveDecision $decision,
    ): string {
        $payload = [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'source_concept_id' => $sourceConcept->id,
            'action' => $decision->action,
            'target_concept_id' => $decision->targetConceptId,
            'reason_code' => $decision->reasonCode,
            'algorithm_code' => $decision->algorithmCode,
            'algorithm_version' => $decision->algorithmVersion,
            'inputs' => $decision->inputs,
            'metadata' => $decision->metadata,
        ];

        $this->sortRecursive($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /** @param array<string|int, mixed> $value */
    private function sortRecursive(array &$value): void
    {
        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->sortRecursive($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }
    }
}
