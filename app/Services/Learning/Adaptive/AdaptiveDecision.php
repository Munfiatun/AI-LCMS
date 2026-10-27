<?php

namespace App\Services\Learning\Adaptive;

class AdaptiveDecision
{
    /**
     * @param array<string, mixed> $inputs
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public readonly string $action,
        public readonly ?int $targetConceptId,
        public readonly string $reasonCode,
        public readonly string $algorithmCode,
        public readonly string $algorithmVersion,
        public readonly array $inputs = [],
        public readonly array $metadata = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'target_concept_id' => $this->targetConceptId,
            'reason_code' => $this->reasonCode,
            'algorithm_code' => $this->algorithmCode,
            'algorithm_version' => $this->algorithmVersion,
            'inputs' => $this->inputs,
            'metadata' => $this->metadata,
        ];
    }
}
