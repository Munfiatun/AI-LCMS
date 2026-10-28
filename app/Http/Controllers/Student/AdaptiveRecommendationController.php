<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AdaptiveRecommendation;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveLearningPathService;
use App\Services\Learning\Adaptive\AdaptiveLearningResourceResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AdaptiveRecommendationController extends Controller
{
    public function __construct(
        private AdaptiveLearningResourceResolver $resources,
        private AdaptiveLearningPathService $learningPath,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $student */
        $student = $request->user();

        $enrolledCourseIds = $student->courseEnrollments()
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id');

        $completedMaterialIds = $student->materialProgress()
            ->where('status', 'completed')
            ->pluck('learning_material_id')
            ->map(fn ($id) => (int) $id);

        $recommendations = AdaptiveRecommendation::query()
            ->where('student_id', $student->id)
            ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
            ->whereIn('course_id', $enrolledCourseIds)
            ->whereHas('course', fn ($query) => $query->published())
            ->whereHas('algorithmVersion', fn ($query) => $query
                ->where('is_baseline', true)
                ->where('status', 'active'))
            ->with([
                'course:id,title,slug',
                'sourceConcept:id,course_id,code,name,order',
                'targetConcept:id,course_id,code,name,order',
                'algorithmVersion:id,code,version,status,is_baseline',
            ])
            ->get()
            ->sortBy(fn (AdaptiveRecommendation $recommendation): string => sprintf(
                '%s-%08d-%08d',
                $recommendation->course?->title ?? '',
                $recommendation->sourceConcept?->order ?? 0,
                $recommendation->id,
            ))
            ->values();

        $rows = $recommendations->map(function (AdaptiveRecommendation $recommendation) use ($completedMaterialIds): array {
            $recommendedMaterial = $this->resources->materialFor($recommendation);
            $formativeQuiz = $this->resources->formativeQuizFor($recommendation);
            $materialCompleted = $recommendedMaterial
                ? $completedMaterialIds->contains((int) $recommendedMaterial->id)
                : false;

            return [
                'recommendation' => $recommendation,
                'action_label' => $this->actionLabel($recommendation->action),
                'reason_text' => $this->reasonText($recommendation->reason_code),
                'is_prerequisite_redirect' => $recommendation->source_concept_id !== $recommendation->target_concept_id,
                'recommended_material' => $recommendedMaterial,
                'material_relevance' => $recommendedMaterial?->pivot?->relevance_weight === null
                    ? null
                    : (float) $recommendedMaterial->pivot->relevance_weight,
                'material_completed' => $materialCompleted,
                'formative_quiz' => $formativeQuiz,
                'reassessment_ready' => $formativeQuiz !== null && ($recommendedMaterial === null || $materialCompleted),
                'activity_type' => $recommendation->action === 'additional_diagnostic'
                    ? 'diagnostic'
                    : ($recommendedMaterial ? 'material' : 'course'),
            ];
        });

        $courseGroups = $rows->groupBy(
            fn (array $row): int => (int) $row['recommendation']->course_id
        );

        return view('student.adaptive-recommendations.index', [
            'student' => $student,
            'rows' => $rows,
            'courseGroups' => $courseGroups,
            'learningPaths' => $this->learningPath->build($rows),
            'summary' => $this->summary($rows),
        ]);
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    private function summary(Collection $rows): array
    {
        $actions = $rows->map(
            fn (array $row): string => $row['recommendation']->action
        );

        return [
            'total' => $rows->count(),
            'remediation' => $actions->filter(
                fn (string $action): bool => in_array($action, [
                    'remediation',
                    'prerequisite_remediation',
                ], true)
            )->count(),
            'reinforcement' => $actions->filter(
                fn (string $action): bool => $action === 'reinforcement'
            )->count(),
            'progression' => $actions->filter(
                fn (string $action): bool => in_array($action, [
                    'next_concept',
                    'enrichment',
                ], true)
            )->count(),
        ];
    }

    private function actionLabel(string $action): string
    {
        return match ($action) {
            'additional_diagnostic' => 'Lengkapi Diagnosis',
            'prerequisite_remediation' => 'Perkuat Konsep Prasyarat',
            'remediation' => 'Pelajari Kembali Konsep',
            'reinforcement' => 'Latihan Penguatan',
            'next_concept' => 'Lanjut ke Konsep Berikutnya',
            'enrichment' => 'Pengayaan',
            default => 'Rekomendasi Belajar',
        };
    }

    private function reasonText(string $reasonCode): string
    {
        return match ($reasonCode) {
            'current_evidence_insufficient' => 'Bukti belajar pada konsep ini belum cukup untuk menentukan langkah berikutnya secara stabil.',
            'prerequisite_evidence_insufficient' => 'Bukti penguasaan pada konsep prasyarat belum cukup dan perlu dilengkapi terlebih dahulu.',
            'prerequisite_below_threshold' => 'Konsep prasyarat masih perlu diperkuat sebelum melanjutkan fokus pada konsep ini.',
            'mastery_low' => 'Hasil diagnosis menunjukkan konsep ini masih perlu dipelajari kembali.',
            'mastery_medium' => 'Konsep sudah mulai dikuasai dan membutuhkan latihan penguatan.',
            'mastery_high_next_ready' => 'Penguasaan konsep sudah kuat dan prasyarat untuk langkah berikutnya telah terpenuhi.',
            'mastery_high_no_next_ready' => 'Penguasaan konsep sudah kuat; sistem merekomendasikan aktivitas pengayaan.',
            default => 'Rekomendasi dibuat dari profil penguasaan konsep dan evidence belajar yang tersedia.',
        };
    }
}
