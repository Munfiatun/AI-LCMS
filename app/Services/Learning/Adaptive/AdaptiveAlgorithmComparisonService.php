<?php

namespace App\Services\Learning\Adaptive;

use App\Models\Course;
use App\Models\LearningConcept;
use App\Models\User;
use Illuminate\Support\Collection;

class AdaptiveAlgorithmComparisonService
{
    public function __construct(
        private RuleBasedAdaptiveEngine $ruleBased,
        private FuzzyAdaptiveEngine $fuzzy,
    ) {}

    /**
     * @return array{
     *     rows: Collection<int, array<string, mixed>>,
     *     summary: array<string, int|float>
     * }
     */
    public function compareCourse(User $student, Course $course): array
    {
        $concepts = LearningConcept::query()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $rows = $concepts->map(function (LearningConcept $concept) use ($student): array {
            $rule = $this->ruleBased->decide($student, $concept);
            $fuzzy = $this->fuzzy->decide($student, $concept);

            $actionAgreement = $rule->action === $fuzzy->action;
            $targetAgreement = $rule->targetConceptId === $fuzzy->targetConceptId;

            return [
                'concept' => $concept,
                'rule_decision' => $rule,
                'fuzzy_decision' => $fuzzy,
                'rule_action' => $rule->action,
                'fuzzy_action' => $fuzzy->action,
                'rule_target_concept_id' => $rule->targetConceptId,
                'fuzzy_target_concept_id' => $fuzzy->targetConceptId,
                'action_agreement' => $actionAgreement,
                'target_agreement' => $targetAgreement,
                'full_agreement' => $actionAgreement && $targetAgreement,
                'fuzzy_crisp_output' => data_get($fuzzy->metadata, 'crisp_output'),
                'fuzzy_fired_rules' => data_get($fuzzy->metadata, 'fired_rules', []),
            ];
        });

        $total = $rows->count();
        $actionAgreements = $rows->where('action_agreement', true)->count();
        $targetAgreements = $rows->where('target_agreement', true)->count();
        $fullAgreements = $rows->where('full_agreement', true)->count();

        return [
            'rows' => $rows,
            'summary' => [
                'total_concepts' => $total,
                'action_agreements' => $actionAgreements,
                'action_divergences' => $total - $actionAgreements,
                'target_agreements' => $targetAgreements,
                'target_divergences' => $total - $targetAgreements,
                'full_agreements' => $fullAgreements,
                'full_divergences' => $total - $fullAgreements,
                'action_agreement_rate' => $this->rate($actionAgreements, $total),
                'target_agreement_rate' => $this->rate($targetAgreements, $total),
                'full_agreement_rate' => $this->rate($fullAgreements, $total),
            ],
        ];
    }

    private function rate(int $matching, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($matching / $total) * 100, 2);
    }
}
