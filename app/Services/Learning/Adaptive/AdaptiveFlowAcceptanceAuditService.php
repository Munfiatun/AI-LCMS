<?php

namespace App\Services\Learning\Adaptive;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\MasteryHistory;
use App\Models\MaterialProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class AdaptiveFlowAcceptanceAuditService
{
    public function __construct(
        private AdaptiveAlgorithmRegistry $registry,
        private AdaptiveEngineAcceptanceAuditService $engineAudit,
        private AdaptiveLearningPathService $learningPath,
        private AdaptiveLearningResourceResolver $resources,
    ) {}

    /**
     * @return array{
     *     gates:list<array{passed:bool,title:string,detail:string}>,
     *     rows:Collection<int,array<string,mixed>>,
     *     summary:array{passed_gates:int,total_gates:int,passed:bool},
     *     current_focus:array<string,mixed>|null
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

        $enrolled = CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        $masteries = LearnerMastery::query()
            ->where('student_id', $student->id)
            ->whereIn('concept_id', $concepts->pluck('id'))
            ->get()
            ->keyBy('concept_id');

        $learnerStateReady = $enrolled
            && $concepts->isNotEmpty()
            && $concepts->every(fn (LearningConcept $concept): bool => ($masteries->get($concept->id)?->evidence_count ?? 0) > 0);

        $r3 = $this->engineAudit->audit($student, $course);
        $engineReady = (bool) $r3['summary']['passed'];

        $baseline = $this->registry->baseline();
        $activeRecommendations = AdaptiveRecommendation::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('algorithm_version_id', $baseline->id)
            ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
            ->with([
                'course:id,title,slug',
                'sourceConcept:id,course_id,code,name,order',
                'targetConcept:id,course_id,code,name,order',
                'algorithmVersion:id,code,version,status,is_baseline',
            ])
            ->orderBy('source_concept_id')
            ->get();

        $pathInput = $activeRecommendations->map(fn (AdaptiveRecommendation $recommendation): array => [
            'recommendation' => $recommendation,
        ]);
        $firstPaths = $this->learningPath->build($pathInput);
        $secondPaths = $this->learningPath->build($pathInput->shuffle());
        $pathReproducible = $firstPaths->isNotEmpty()
            && $this->pathSignature($firstPaths) === $this->pathSignature($secondPaths);

        $path = $firstPaths->first();
        $currentStep = $path['current_step'] ?? null;
        /** @var AdaptiveRecommendation|null $currentRecommendation */
        $currentRecommendation = $currentStep['representative_row']['recommendation'] ?? null;
        $currentMaterial = $currentRecommendation ? $this->resources->materialFor($currentRecommendation) : null;
        $currentFormative = $currentRecommendation ? $this->resources->formativeQuizFor($currentRecommendation) : null;
        $materialCompleted = $currentMaterial !== null
            && MaterialProgress::query()
                ->where('student_id', $student->id)
                ->where('learning_material_id', $currentMaterial->id)
                ->where('status', 'completed')
                ->exists();

        $resourceReady = $currentRecommendation !== null
            && $currentRecommendation->action !== 'additional_diagnostic'
            && $currentMaterial !== null
            && $currentFormative !== null
            && $materialCompleted;

        $formativeHistory = MasteryHistory::query()
            ->where('student_id', $student->id)
            ->whereIn('concept_id', $concepts->pluck('id'))
            ->where('evidence_type', 'formative_quiz_attempt')
            ->where('model_version', (string) config('mastery.model_version', 'diagnostic-formative-weighted-v2'))
            ->orderByDesc('id')
            ->get();

        $formativeAttemptIds = $formativeHistory->pluck('evidence_id')->filter()->unique()->values();
        $formativeAttempts = QuizAttempt::query()
            ->whereIn('id', $formativeAttemptIds)
            ->where('student_id', $student->id)
            ->whereIn('status', ['submitted', 'expired'])
            ->whereHas('quiz', fn ($query) => $query
                ->where('course_id', $course->id)
                ->where('assessment_type', Quiz::ASSESSMENT_FORMATIVE))
            ->with('quiz:id,course_id,title,assessment_type')
            ->get()
            ->keyBy('id');

        $formativeEvidenceReady = $formativeHistory->isNotEmpty()
            && $formativeHistory->every(fn (MasteryHistory $history): bool => $formativeAttempts->has($history->evidence_id));

        $supersededCount = AdaptiveRecommendation::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('algorithm_version_id', $baseline->id)
            ->where('status', AdaptiveRecommendation::STATUS_SUPERSEDED)
            ->count();

        $readaptedActive = $activeRecommendations->filter(function (AdaptiveRecommendation $recommendation) use ($formativeAttemptIds): bool {
            $context = data_get($recommendation->metadata, 'adaptation_context', []);

            return data_get($context, 'trigger') === 'formative_reassessment'
                && $formativeAttemptIds->contains((int) data_get($context, 'attempt_id'))
                && data_get($context, 'mastery_model_version') === (string) config('mastery.model_version', 'diagnostic-formative-weighted-v2')
                && is_array(data_get($context, 'updated_concept_ids'))
                && data_get($context, 'updated_concept_ids') !== [];
        });

        $readaptationReady = $concepts->isNotEmpty()
            && $activeRecommendations->count() === $concepts->count()
            && $readaptedActive->count() === $activeRecommendations->count()
            && $supersededCount > 0
            && $engineReady;

        $rows = $concepts->map(function (LearningConcept $concept) use ($masteries, $activeRecommendations): array {
            $mastery = $masteries->get($concept->id);
            $recommendation = $activeRecommendations->firstWhere('source_concept_id', $concept->id);

            return [
                'concept' => $concept,
                'mastery_score' => $mastery?->mastery_score,
                'evidence_count' => $mastery?->evidence_count ?? 0,
                'recommendation_action' => $recommendation?->action,
                'target_code' => $recommendation?->targetConcept?->code,
                'adaptation_trigger' => data_get($recommendation?->metadata, 'adaptation_context.trigger'),
            ];
        });

        $gates = [
            [
                'passed' => $learnerStateReady,
                'title' => 'Learner terdaftar dan mastery baseline tersedia',
                'detail' => ($enrolled ? 'Enrollment valid; ' : 'Enrollment tidak valid; ')
                    .$rows->where('evidence_count', '>', 0)->count().' dari '.$concepts->count().' concept memiliki evidence.',
            ],
            [
                'passed' => $engineReady,
                'title' => 'Adaptive Engine R3 tetap valid untuk learner state saat ini',
                'detail' => $r3['summary']['passed_gates'].'/'.$r3['summary']['total_gates'].' gate R3 terpenuhi.',
            ],
            [
                'passed' => $pathReproducible,
                'title' => 'Adaptive learning path dapat direproduksi',
                'detail' => $pathReproducible
                    ? (($path['unique_target_count'] ?? 0).' target unik; fokus saat ini '.($currentRecommendation?->targetConcept?->code ?? '—').'.')
                    : 'Learning path belum tersedia atau tidak reproducible.',
            ],
            [
                'passed' => $resourceReady,
                'title' => 'Fokus saat ini memiliki material selesai dan formative reassessment',
                'detail' => 'Material: '.($currentMaterial?->title ?? 'MISSING')
                    .'; status: '.($materialCompleted ? 'completed' : 'not completed')
                    .'; formative: '.($currentFormative?->title ?? 'MISSING').'.',
            ],
            [
                'passed' => $formativeEvidenceReady,
                'title' => 'Formative reassessment tercatat sebagai learner-model evidence',
                'detail' => $formativeHistory->count().' formative mastery history; '
                    .$formativeAttempts->count().' attempt valid dengan model '.config('mastery.model_version').'.',
            ],
            [
                'passed' => $readaptationReady,
                'title' => 'Automatic re-adaptation tersimpan dan dapat ditelusuri',
                'detail' => $readaptedActive->count().' dari '.$activeRecommendations->count()
                    .' active recommendation membawa formative trigger; '.$supersededCount.' recommendation lama superseded.',
            ],
        ];

        $passedGates = collect($gates)->where('passed', true)->count();

        return [
            'gates' => $gates,
            'rows' => $rows,
            'current_focus' => $currentRecommendation ? [
                'concept' => $currentRecommendation->targetConcept,
                'action' => $currentRecommendation->action,
                'material' => $currentMaterial,
                'formative' => $currentFormative,
                'material_completed' => $materialCompleted,
            ] : null,
            'summary' => [
                'passed_gates' => $passedGates,
                'total_gates' => count($gates),
                'passed' => $passedGates === count($gates),
            ],
        ];
    }

    /** @param Collection<int, array<string, mixed>> $paths */
    private function pathSignature(Collection $paths): array
    {
        return $paths->map(fn (array $path): array => [
            'course_id' => $path['course']->id,
            'recommendation_count' => $path['recommendation_count'],
            'unique_target_count' => $path['unique_target_count'],
            'steps' => $path['steps']->map(fn (array $step): array => [
                'position' => $step['position'],
                'status' => $step['status'],
                'target_concept_id' => $step['target_concept']?->id,
                'action' => $step['representative_row']['recommendation']->action,
                'support_count' => $step['support_count'],
            ])->all(),
        ])->all();
    }
}
