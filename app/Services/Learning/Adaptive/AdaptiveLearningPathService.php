<?php

namespace App\Services\Learning\Adaptive;

use App\Models\AdaptiveRecommendation;
use App\Models\LearningConcept;
use Illuminate\Support\Collection;

class AdaptiveLearningPathService
{
    /** @var array<string, int> */
    private const ACTION_PRIORITY = [
        'additional_diagnostic' => 10,
        'prerequisite_remediation' => 20,
        'remediation' => 30,
        'reinforcement' => 40,
        'next_concept' => 50,
        'enrichment' => 60,
    ];

    /**
     * Collapse recommendation rows into one provisional learning step per target concept.
     * The first step is the only current focus; queued steps must be re-evaluated after learner state changes.
     *
     * @param Collection<int, array<string, mixed>> $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function build(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn (array $row): int => (int) $row['recommendation']->course_id)
            ->map(function (Collection $courseRows): array {
                $steps = $courseRows
                    ->groupBy(fn (array $row): string => $this->targetKey($row['recommendation']))
                    ->map(fn (Collection $targetRows): array => $this->stepFor($targetRows))
                    ->sortBy(fn (array $step): string => sprintf(
                        '%03d-%08d-%08d-%08d',
                        $step['priority'],
                        $step['target_concept']?->order ?? PHP_INT_MAX,
                        PHP_INT_MAX - $step['support_count'],
                        $step['target_concept']?->id ?? PHP_INT_MAX,
                    ))
                    ->values()
                    ->map(function (array $step, int $index): array {
                        $step['position'] = $index + 1;
                        $step['status'] = $index === 0 ? 'current' : 'queued';

                        return $step;
                    });

                $firstRow = $courseRows->first();

                return [
                    'course' => $firstRow['recommendation']->course,
                    'recommendation_count' => $courseRows->count(),
                    'unique_target_count' => $steps->count(),
                    'steps' => $steps,
                    'current_step' => $steps->first(),
                    'queued_steps' => $steps->slice(1)->values(),
                ];
            })
            ->values();
    }

    /**
     * @param Collection<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function stepFor(Collection $rows): array
    {
        $representative = $rows
            ->sortBy(fn (array $row): string => sprintf(
                '%03d-%08d-%08d',
                $this->priorityFor($row['recommendation']->action),
                $row['recommendation']->sourceConcept?->order ?? PHP_INT_MAX,
                $row['recommendation']->id ?? PHP_INT_MAX,
            ))
            ->first();

        /** @var AdaptiveRecommendation $recommendation */
        $recommendation = $representative['recommendation'];
        /** @var LearningConcept|null $target */
        $target = $recommendation->targetConcept;

        $sourceConcepts = $rows
            ->map(fn (array $row) => $row['recommendation']->sourceConcept)
            ->filter()
            ->unique('id')
            ->sortBy(fn (LearningConcept $concept): string => sprintf('%08d-%08d', $concept->order, $concept->id))
            ->values();

        $actions = $rows
            ->pluck('recommendation.action')
            ->unique()
            ->sortBy(fn (string $action): int => $this->priorityFor($action))
            ->values();

        return [
            'target_concept' => $target,
            'representative_row' => $representative,
            'support_count' => $rows->count(),
            'source_concepts' => $sourceConcepts,
            'actions' => $actions,
            'priority' => $this->priorityFor($recommendation->action),
            'priority_reason' => $this->priorityReason($recommendation->action),
        ];
    }

    private function targetKey(AdaptiveRecommendation $recommendation): string
    {
        if ($recommendation->target_concept_id !== null) {
            return 'concept:'.$recommendation->target_concept_id;
        }

        return 'recommendation:'.$recommendation->id;
    }

    private function priorityFor(string $action): int
    {
        return self::ACTION_PRIORITY[$action] ?? 999;
    }

    private function priorityReason(string $action): string
    {
        return match ($action) {
            'additional_diagnostic' => 'Evidence belum cukup, sehingga sistem memprioritaskan penambahan bukti belajar sebelum keputusan lain.',
            'prerequisite_remediation' => 'Konsep ini menjadi prasyarat yang menghambat satu atau lebih konsep lain.',
            'remediation' => 'Penguasaan konsep masih rendah dan perlu diperbaiki sebelum melanjutkan.',
            'reinforcement' => 'Konsep sudah mulai dikuasai dan perlu diperkuat.',
            'next_concept' => 'Prasyarat terpenuhi dan siswa siap bergerak ke konsep berikutnya.',
            'enrichment' => 'Penguasaan sudah kuat dan sistem merekomendasikan pengayaan.',
            default => 'Prioritas ditentukan dari keputusan adaptive engine aktif.',
        };
    }
}
