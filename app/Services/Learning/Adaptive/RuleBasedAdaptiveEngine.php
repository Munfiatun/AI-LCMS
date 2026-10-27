<?php

namespace App\Services\Learning\Adaptive;

use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;

class RuleBasedAdaptiveEngine implements AdaptiveEngine
{
    public function __construct(private AdaptiveAlgorithmRegistry $registry) {}

    public function decide(User $student, LearningConcept $concept): AdaptiveDecision
    {
        $algorithm = $this->registry->baseline();
        $configuration = $algorithm->configuration ?? [];
        $minimumEvidence = (int) data_get($configuration, 'minimum_evidence_count', 1);
        $lowMax = (float) data_get($configuration, 'mastery_thresholds.low_max', 39.99);
        $mediumMax = (float) data_get($configuration, 'mastery_thresholds.medium_max', 79.99);
        $prerequisiteMinimum = (float) data_get($configuration, 'mastery_thresholds.prerequisite_minimum', 70.0);

        $mastery = $this->masteryFor($student, $concept);
        $inputs = [
            'student_id' => $student->id,
            'concept_id' => $concept->id,
            'mastery_score' => $mastery?->mastery_score,
            'evidence_count' => $mastery?->evidence_count ?? 0,
            'minimum_evidence_count' => $minimumEvidence,
            'prerequisite_minimum' => $prerequisiteMinimum,
            'thresholds' => [
                'low_max' => $lowMax,
                'medium_max' => $mediumMax,
            ],
        ];

        if (! $mastery || $mastery->evidence_count < $minimumEvidence) {
            return $this->decision(
                'additional_diagnostic',
                $concept->id,
                'current_evidence_insufficient',
                $algorithm->code,
                $algorithm->version,
                $inputs,
                ['target_concept_code' => $concept->code]
            );
        }

        $prerequisites = $concept->prerequisites()
            ->where('learning_concepts.is_active', true)
            ->get();

        foreach ($prerequisites as $prerequisite) {
            $prerequisiteMastery = $this->masteryFor($student, $prerequisite);

            if (! $prerequisiteMastery || $prerequisiteMastery->evidence_count < $minimumEvidence) {
                return $this->decision(
                    'additional_diagnostic',
                    $prerequisite->id,
                    'prerequisite_evidence_insufficient',
                    $algorithm->code,
                    $algorithm->version,
                    $inputs,
                    [
                        'target_concept_code' => $prerequisite->code,
                        'current_concept_id' => $concept->id,
                    ]
                );
            }

            if ($prerequisiteMastery->mastery_score < $prerequisiteMinimum) {
                return $this->decision(
                    'prerequisite_remediation',
                    $prerequisite->id,
                    'prerequisite_below_threshold',
                    $algorithm->code,
                    $algorithm->version,
                    $inputs,
                    [
                        'target_concept_code' => $prerequisite->code,
                        'prerequisite_score' => $prerequisiteMastery->mastery_score,
                        'current_concept_id' => $concept->id,
                    ]
                );
            }
        }

        $score = (float) $mastery->mastery_score;

        if ($score <= $lowMax) {
            return $this->decision(
                'remediation',
                $concept->id,
                'mastery_low',
                $algorithm->code,
                $algorithm->version,
                $inputs,
                ['target_concept_code' => $concept->code]
            );
        }

        if ($score <= $mediumMax) {
            return $this->decision(
                'reinforcement',
                $concept->id,
                'mastery_medium',
                $algorithm->code,
                $algorithm->version,
                $inputs,
                ['target_concept_code' => $concept->code]
            );
        }

        $nextConcept = $this->nextReadyConcept(
            $student,
            $concept,
            $minimumEvidence,
            $prerequisiteMinimum
        );

        if ($nextConcept) {
            return $this->decision(
                'next_concept',
                $nextConcept->id,
                'mastery_high_next_ready',
                $algorithm->code,
                $algorithm->version,
                $inputs,
                [
                    'target_concept_code' => $nextConcept->code,
                    'source_concept_id' => $concept->id,
                ]
            );
        }

        return $this->decision(
            'enrichment',
            $concept->id,
            'mastery_high_no_next_ready',
            $algorithm->code,
            $algorithm->version,
            $inputs,
            ['target_concept_code' => $concept->code]
        );
    }

    private function masteryFor(User $student, LearningConcept $concept): ?LearnerMastery
    {
        return LearnerMastery::query()
            ->where('student_id', $student->id)
            ->where('concept_id', $concept->id)
            ->first();
    }

    private function nextReadyConcept(
        User $student,
        LearningConcept $concept,
        int $minimumEvidence,
        float $prerequisiteMinimum,
    ): ?LearningConcept {
        $candidates = LearningConcept::query()
            ->where('course_id', $concept->course_id)
            ->where('is_active', true)
            ->where(function ($query) use ($concept): void {
                $query->where('order', '>', $concept->order)
                    ->orWhere(function ($query) use ($concept): void {
                        $query->where('order', $concept->order)
                            ->where('id', '>', $concept->id);
                    });
            })
            ->with('prerequisites')
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        foreach ($candidates as $candidate) {
            $ready = $candidate->prerequisites->every(function (LearningConcept $prerequisite) use ($student, $minimumEvidence, $prerequisiteMinimum): bool {
                $mastery = $this->masteryFor($student, $prerequisite);

                return $mastery !== null
                    && $mastery->evidence_count >= $minimumEvidence
                    && $mastery->mastery_score >= $prerequisiteMinimum;
            });

            if ($ready) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $inputs
     * @param array<string, mixed> $metadata
     */
    private function decision(
        string $action,
        ?int $targetConceptId,
        string $reasonCode,
        string $algorithmCode,
        string $algorithmVersion,
        array $inputs,
        array $metadata,
    ): AdaptiveDecision {
        return new AdaptiveDecision(
            action: $action,
            targetConceptId: $targetConceptId,
            reasonCode: $reasonCode,
            algorithmCode: $algorithmCode,
            algorithmVersion: $algorithmVersion,
            inputs: $inputs,
            metadata: $metadata,
        );
    }
}
