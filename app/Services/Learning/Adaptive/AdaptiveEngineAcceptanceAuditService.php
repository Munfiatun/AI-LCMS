<?php

namespace App\Services\Learning\Adaptive;

use App\Models\AdaptiveAlgorithmVersion;
use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use Illuminate\Support\Collection;

class AdaptiveEngineAcceptanceAuditService
{
    public function __construct(
        private AdaptiveAlgorithmRegistry $registry,
        private RuleBasedAdaptiveEngine $ruleBased,
        private FuzzyAdaptiveEngine $fuzzy,
        private AdaptiveAlgorithmComparisonService $comparison,
    ) {}

    /**
     * @return array{
     *     gates: list<array{passed:bool,title:string,detail:string}>,
     *     rows: Collection<int, array<string, mixed>>,
     *     comparison: array<string, int|float>,
     *     summary: array{passed_gates:int,total_gates:int,passed:bool}
     * }
     */
    public function audit(User $student, Course $course): array
    {
        $concepts = LearningConcept::query()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        $baseline = $this->registry->baseline();
        $fuzzyAlgorithm = $this->registry->comparison();

        $masteries = LearnerMastery::query()
            ->where('student_id', $student->id)
            ->whereIn('concept_id', $concepts->pluck('id'))
            ->get()
            ->keyBy('concept_id');

        $activeRecommendations = AdaptiveRecommendation::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('algorithm_version_id', $baseline->id)
            ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
            ->get()
            ->keyBy('source_concept_id');

        $rows = $concepts->map(function (LearningConcept $concept) use ($student, $masteries, $activeRecommendations, $course): array {
            $ruleFirst = $this->ruleBased->decide($student, $concept);
            $ruleSecond = $this->ruleBased->decide($student, $concept);
            $fuzzyFirst = $this->fuzzy->decide($student, $concept);
            $fuzzySecond = $this->fuzzy->decide($student, $concept);
            $mastery = $masteries->get($concept->id);
            $recommendation = $activeRecommendations->get($concept->id);

            $ruleTargetValid = $ruleFirst->targetConceptId === null
                || LearningConcept::query()
                    ->whereKey($ruleFirst->targetConceptId)
                    ->where('course_id', $course->id)
                    ->exists();

            $fuzzyTargetValid = $fuzzyFirst->targetConceptId === null
                || LearningConcept::query()
                    ->whereKey($fuzzyFirst->targetConceptId)
                    ->where('course_id', $course->id)
                    ->exists();

            $recommendationTraceable = $recommendation !== null
                && $recommendation->action === $ruleFirst->action
                && $recommendation->target_concept_id === $ruleFirst->targetConceptId
                && $recommendation->reason_code === $ruleFirst->reasonCode
                && is_array($recommendation->inputs)
                && $recommendation->inputs !== [];

            $firedRules = data_get($fuzzyFirst->metadata, 'fired_rules', []);
            $crispOutput = data_get($fuzzyFirst->metadata, 'crisp_output');

            return [
                'concept' => $concept,
                'mastery_ready' => $mastery !== null && $mastery->evidence_count > 0,
                'rule_deterministic' => $ruleFirst->toArray() === $ruleSecond->toArray(),
                'rule_explainable' => $ruleFirst->reasonCode !== '' && $ruleTargetValid,
                'recommendation_traceable' => $recommendationTraceable,
                'fuzzy_deterministic' => $fuzzyFirst->toArray() === $fuzzySecond->toArray(),
                'fuzzy_traceable' => $fuzzyFirst->reasonCode === 'fuzzy_mamdani_centroid'
                    && is_numeric($crispOutput)
                    && is_array($firedRules)
                    && $firedRules !== []
                    && $fuzzyTargetValid,
                'rule_action' => $ruleFirst->action,
                'fuzzy_action' => $fuzzyFirst->action,
                'fuzzy_crisp_output' => $crispOutput,
                'recommendation_status' => $recommendation?->status,
            ];
        });

        $firstComparison = $this->comparison->compareCourse($student, $course);
        $secondComparison = $this->comparison->compareCourse($student, $course);
        $comparisonReproducible = $this->comparisonSignature($firstComparison['rows'])
            === $this->comparisonSignature($secondComparison['rows'])
            && $firstComparison['summary'] === $secondComparison['summary'];

        $versionsReady = $baseline->engine_type === AdaptiveAlgorithmVersion::TYPE_RULE_BASED
            && $baseline->status === AdaptiveAlgorithmVersion::STATUS_ACTIVE
            && $baseline->is_baseline
            && $fuzzyAlgorithm->engine_type === AdaptiveAlgorithmVersion::TYPE_FUZZY_INFERENCE
            && $fuzzyAlgorithm->status === AdaptiveAlgorithmVersion::STATUS_EXPERIMENTAL
            && ! $fuzzyAlgorithm->is_baseline;

        $allConceptsReady = $concepts->isNotEmpty()
            && $rows->every(fn (array $row): bool => $row['mastery_ready']);
        $ruleReady = $concepts->isNotEmpty()
            && $rows->every(fn (array $row): bool => $row['rule_deterministic'] && $row['rule_explainable']);
        $persistenceReady = $concepts->isNotEmpty()
            && $rows->every(fn (array $row): bool => $row['recommendation_traceable']);
        $fuzzyReady = $concepts->isNotEmpty()
            && $rows->every(fn (array $row): bool => $row['fuzzy_deterministic'] && $row['fuzzy_traceable']);

        $gates = [
            [
                'passed' => $versionsReady,
                'title' => 'Algorithm versioning terdaftar dan dapat dibedakan',
                'detail' => "Baseline {$baseline->code}@{$baseline->version}; comparison {$fuzzyAlgorithm->code}@{$fuzzyAlgorithm->version}.",
            ],
            [
                'passed' => $allConceptsReady,
                'title' => 'Learner state tersedia untuk seluruh concept aktif',
                'detail' => $rows->where('mastery_ready', true)->count().' dari '.$concepts->count().' concept memiliki mastery evidence.',
            ],
            [
                'passed' => $ruleReady,
                'title' => 'Rule-Based baseline deterministik dan explainable',
                'detail' => $rows->where('rule_deterministic', true)->count().' deterministic; '.$rows->where('rule_explainable', true)->count().' explainable dari '.$concepts->count().' concept.',
            ],
            [
                'passed' => $persistenceReady,
                'title' => 'Recommendation baseline tersimpan dan dapat ditelusuri',
                'detail' => $rows->where('recommendation_traceable', true)->count().' dari '.$concepts->count().' active recommendation cocok dengan keputusan baseline saat ini.',
            ],
            [
                'passed' => $fuzzyReady,
                'title' => 'Fuzzy Mamdani menghasilkan trace numerik yang reproducible',
                'detail' => $rows->where('fuzzy_traceable', true)->count().' fuzzy trace lengkap; '.$rows->where('fuzzy_deterministic', true)->count().' deterministic dari '.$concepts->count().' concept.',
            ],
            [
                'passed' => $comparisonReproducible,
                'title' => 'Perbandingan Rule-Based vs Fuzzy dapat direproduksi',
                'detail' => 'Full agreement '.number_format((float) $firstComparison['summary']['full_agreement_rate'], 2).'% dengan '.$firstComparison['summary']['full_divergences'].' divergence; agreement bukan syarat superioritas algoritma.',
            ],
        ];

        $passedGates = collect($gates)->where('passed', true)->count();

        return [
            'gates' => $gates,
            'rows' => $rows,
            'comparison' => $firstComparison['summary'],
            'summary' => [
                'passed_gates' => $passedGates,
                'total_gates' => count($gates),
                'passed' => $passedGates === count($gates),
            ],
        ];
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    private function comparisonSignature(Collection $rows): array
    {
        return $rows->map(fn (array $row): array => [
            'concept_id' => $row['concept']->id,
            'rule' => $row['rule_decision']->toArray(),
            'fuzzy' => $row['fuzzy_decision']->toArray(),
            'full_agreement' => $row['full_agreement'],
        ])->all();
    }
}
