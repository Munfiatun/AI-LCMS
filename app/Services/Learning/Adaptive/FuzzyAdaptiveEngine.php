<?php

namespace App\Services\Learning\Adaptive;

use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class FuzzyAdaptiveEngine implements AdaptiveEngine
{
    public function __construct(private AdaptiveAlgorithmRegistry $registry) {}

    public function decide(User $student, LearningConcept $concept): AdaptiveDecision
    {
        $algorithm = $this->registry->comparison();
        $configuration = $algorithm->configuration ?? [];

        $mastery = $this->masteryFor($student, $concept);
        $prerequisites = $concept->prerequisites()
            ->where('learning_concepts.is_active', true)
            ->orderByPivot('order')
            ->get();

        $weakestPrerequisite = $this->weakestPrerequisite($student, $prerequisites);
        $prerequisiteMastery = $weakestPrerequisite['score'] ?? 100.0;

        $inputs = [
            'student_id' => $student->id,
            'concept_id' => $concept->id,
            'mastery_score' => (float) ($mastery?->mastery_score ?? 0.0),
            'evidence_count' => (int) ($mastery?->evidence_count ?? 0),
            'prerequisite_mastery' => (float) $prerequisiteMastery,
        ];

        $memberships = $this->fuzzify($inputs, $configuration);
        [$outputActivation, $firedRules] = $this->evaluateRules($memberships, $configuration);
        $crispOutput = $this->defuzzify($outputActivation, $configuration);
        $selectedAction = $this->selectAction($crispOutput, $outputActivation, $configuration);

        [$action, $targetConcept, $targetMetadata] = $this->resolveTarget(
            $selectedAction,
            $student,
            $concept,
            $weakestPrerequisite,
            $configuration,
        );

        return new AdaptiveDecision(
            action: $action,
            targetConceptId: $targetConcept?->id,
            reasonCode: 'fuzzy_mamdani_centroid',
            algorithmCode: $algorithm->code,
            algorithmVersion: $algorithm->version,
            inputs: $inputs,
            metadata: [
                'inference_method' => data_get($configuration, 'inference_method', 'mamdani'),
                'defuzzification' => data_get($configuration, 'defuzzification', 'centroid'),
                'memberships' => $memberships,
                'output_activation' => $outputActivation,
                'crisp_output' => round($crispOutput, 4),
                'selected_output' => $selectedAction,
                'fired_rules' => $firedRules,
                'weakest_prerequisite' => $weakestPrerequisite,
                'target_concept_code' => $targetConcept?->code,
                ...$targetMetadata,
            ],
        );
    }

    /**
     * @param array<string, int|float> $inputs
     * @param array<string, mixed> $configuration
     * @return array<string, array<string, float>>
     */
    private function fuzzify(array $inputs, array $configuration): array
    {
        $result = [];

        foreach (data_get($configuration, 'inputs', []) as $inputName => $definition) {
            $value = (float) ($inputs[$inputName] ?? 0.0);
            $universe = $definition['universe'] ?? [0, 100];
            $value = max((float) $universe[0], min((float) $universe[1], $value));

            foreach ($definition['sets'] ?? [] as $setName => $setDefinition) {
                $result[$inputName][$setName] = round($this->membership($value, $setDefinition), 6);
            }
        }

        return $result;
    }

    /**
     * @param array<string, array<string, float>> $memberships
     * @param array<string, mixed> $configuration
     * @return array{array<string, float>, list<array<string, mixed>>}
     */
    private function evaluateRules(array $memberships, array $configuration): array
    {
        $outputActivation = [];
        foreach (array_keys(data_get($configuration, 'outputs', [])) as $output) {
            $outputActivation[$output] = 0.0;
        }

        $firedRules = [];

        foreach (data_get($configuration, 'rules', []) as $index => $rule) {
            $conditions = $rule['if'] ?? [];
            $strengths = [];

            foreach ($conditions as $inputName => $setName) {
                $strengths[] = (float) data_get($memberships, $inputName.'.'.$setName, 0.0);
            }

            $strength = $strengths === [] ? 0.0 : min($strengths);
            $output = (string) ($rule['then'] ?? '');

            if ($output === '' || ! array_key_exists($output, $outputActivation)) {
                continue;
            }

            $outputActivation[$output] = max($outputActivation[$output], $strength);

            if ($strength > 0.0) {
                $firedRules[] = [
                    'rule' => $index + 1,
                    'if' => $conditions,
                    'then' => $output,
                    'strength' => round($strength, 6),
                ];
            }
        }

        return [$outputActivation, $firedRules];
    }

    /**
     * @param array<string, float> $outputActivation
     * @param array<string, mixed> $configuration
     */
    private function defuzzify(array $outputActivation, array $configuration): float
    {
        $universe = data_get($configuration, 'output_universe', ['min' => 0, 'max' => 100, 'step' => 1]);
        $minimum = (float) ($universe['min'] ?? 0);
        $maximum = (float) ($universe['max'] ?? 100);
        $step = max(0.1, (float) ($universe['step'] ?? 1));
        $outputs = data_get($configuration, 'outputs', []);

        $numerator = 0.0;
        $denominator = 0.0;

        for ($x = $minimum; $x <= $maximum + 1e-9; $x += $step) {
            $aggregated = 0.0;

            foreach ($outputs as $outputName => $setDefinition) {
                $activation = (float) ($outputActivation[$outputName] ?? 0.0);
                if ($activation <= 0.0) {
                    continue;
                }

                $aggregated = max(
                    $aggregated,
                    min($activation, $this->membership($x, $setDefinition)),
                );
            }

            $numerator += $x * $aggregated;
            $denominator += $aggregated;
        }

        if ($denominator <= 0.0) {
            return 0.0;
        }

        return $numerator / $denominator;
    }

    /**
     * @param array<string, float> $outputActivation
     * @param array<string, mixed> $configuration
     */
    private function selectAction(float $crispOutput, array $outputActivation, array $configuration): string
    {
        $outputs = data_get($configuration, 'outputs', []);
        $selected = null;
        $selectedMembership = -1.0;

        foreach ($outputs as $outputName => $setDefinition) {
            $membership = $this->membership($crispOutput, $setDefinition);

            if ($membership > $selectedMembership) {
                $selected = $outputName;
                $selectedMembership = $membership;
            }
        }

        if ($selected !== null && $selectedMembership > 0.0) {
            return $selected;
        }

        $maxActivation = max($outputActivation ?: [0.0]);
        foreach ($outputs as $outputName => $_) {
            if (($outputActivation[$outputName] ?? 0.0) === $maxActivation && $maxActivation > 0.0) {
                return $outputName;
            }
        }

        return 'additional_diagnostic';
    }

    /**
     * @param array{concept_id:int, code:string, score:float, evidence_count:int}|null $weakestPrerequisite
     * @param array<string, mixed> $configuration
     * @return array{string, ?LearningConcept, array<string, mixed>}
     */
    private function resolveTarget(
        string $selectedAction,
        User $student,
        LearningConcept $concept,
        ?array $weakestPrerequisite,
        array $configuration,
    ): array {
        if ($selectedAction === 'prerequisite_remediation' && $weakestPrerequisite !== null) {
            $target = LearningConcept::query()->find($weakestPrerequisite['concept_id']);

            return [
                'prerequisite_remediation',
                $target,
                ['target_resolution' => 'weakest_prerequisite'],
            ];
        }

        if ($selectedAction === 'next_concept') {
            $nextConcept = $this->nextReadyConcept($student, $concept, $configuration);

            if ($nextConcept !== null) {
                return [
                    'next_concept',
                    $nextConcept,
                    ['target_resolution' => 'next_ready_concept'],
                ];
            }

            return [
                'enrichment',
                $concept,
                [
                    'target_resolution' => 'fallback_current_concept',
                    'fuzzy_output_before_target_resolution' => 'next_concept',
                ],
            ];
        }

        return [
            $selectedAction,
            $concept,
            ['target_resolution' => 'current_concept'],
        ];
    }

    /**
     * @param Collection<int, LearningConcept> $prerequisites
     * @return array{concept_id:int, code:string, score:float, evidence_count:int}|null
     */
    private function weakestPrerequisite(User $student, Collection $prerequisites): ?array
    {
        if ($prerequisites->isEmpty()) {
            return null;
        }

        $weakest = null;

        foreach ($prerequisites as $prerequisite) {
            $mastery = $this->masteryFor($student, $prerequisite);
            $candidate = [
                'concept_id' => $prerequisite->id,
                'code' => $prerequisite->code,
                'score' => (float) ($mastery?->mastery_score ?? 0.0),
                'evidence_count' => (int) ($mastery?->evidence_count ?? 0),
            ];

            if ($weakest === null
                || $candidate['score'] < $weakest['score']
                || ($candidate['score'] === $weakest['score'] && $candidate['concept_id'] < $weakest['concept_id'])) {
                $weakest = $candidate;
            }
        }

        return $weakest;
    }

    /** @param array<string, mixed> $configuration */
    private function nextReadyConcept(User $student, LearningConcept $concept, array $configuration): ?LearningConcept
    {
        $minimumEvidence = (int) data_get($configuration, 'targeting.minimum_evidence_count', 2);
        $prerequisiteMinimum = (float) data_get($configuration, 'targeting.prerequisite_ready_minimum', 70.0);

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

    /** @param array<string, mixed> $definition */
    private function membership(float $value, array $definition): float
    {
        $type = $definition['type'] ?? null;
        $points = array_map('floatval', $definition['points'] ?? []);

        return match ($type) {
            'triangle' => $this->triangle($value, $points),
            'trapezoid' => $this->trapezoid($value, $points),
            default => throw new RuntimeException('Unsupported fuzzy membership function ['.(string) $type.'].'),
        };
    }

    /** @param list<float> $points */
    private function triangle(float $x, array $points): float
    {
        if (count($points) !== 3) {
            throw new RuntimeException('Triangle membership requires exactly 3 points.');
        }

        [$a, $b, $c] = $points;

        if ($x <= $a || $x >= $c) {
            return $x === $b ? 1.0 : 0.0;
        }

        if ($x === $b) {
            return 1.0;
        }

        if ($x < $b) {
            return ($x - $a) / ($b - $a);
        }

        return ($c - $x) / ($c - $b);
    }

    /** @param list<float> $points */
    private function trapezoid(float $x, array $points): float
    {
        if (count($points) !== 4) {
            throw new RuntimeException('Trapezoid membership requires exactly 4 points.');
        }

        [$a, $b, $c, $d] = $points;

        if ($x < $a || $x > $d) {
            return 0.0;
        }

        if ($x >= $b && $x <= $c) {
            return 1.0;
        }

        if ($x >= $a && $x < $b) {
            return $b === $a ? 1.0 : ($x - $a) / ($b - $a);
        }

        if ($x > $c && $x <= $d) {
            return $d === $c ? 1.0 : ($d - $x) / ($d - $c);
        }

        return 0.0;
    }

    private function masteryFor(User $student, LearningConcept $concept): ?LearnerMastery
    {
        return LearnerMastery::query()
            ->where('student_id', $student->id)
            ->where('concept_id', $concept->id)
            ->first();
    }
}
