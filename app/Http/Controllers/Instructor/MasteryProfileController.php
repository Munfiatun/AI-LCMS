<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\MasteryHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MasteryProfileController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::query()
            ->where('instructor_id', $request->user()->id)
            ->orderBy('title')
            ->get();

        $selectedCourse = null;
        $students = collect();

        if ($courses->isNotEmpty()) {
            $requestedCourseId = (int) $request->query('course_id', $courses->first()->id);
            $selectedCourse = $courses->firstWhere('id', $requestedCourseId) ?? $courses->first();
            Gate::authorize('update', $selectedCourse);

            $studentIds = LearnerMastery::query()
                ->whereHas('concept', fn ($query) => $query->where('course_id', $selectedCourse->id))
                ->distinct()
                ->pluck('student_id');

            $students = User::query()
                ->whereIn('id', $studentIds)
                ->with(['role'])
                ->orderBy('name')
                ->get()
                ->map(function (User $student) use ($selectedCourse): array {
                    $masteries = LearnerMastery::query()
                        ->where('student_id', $student->id)
                        ->whereHas('concept', fn ($query) => $query->where('course_id', $selectedCourse->id))
                        ->get();

                    return [
                        'student' => $student,
                        'concept_count' => $masteries->count(),
                        'average_mastery' => $masteries->isEmpty() ? null : round((float) $masteries->avg('mastery_score'), 2),
                        'evidence_count' => (int) $masteries->sum('evidence_count'),
                        'last_evidence_at' => $masteries->max('last_evidence_at'),
                    ];
                });
        }

        return view('instructor.mastery.index', compact('courses', 'selectedCourse', 'students'));
    }

    public function audit(Request $request): View
    {
        $courses = Course::query()
            ->where('instructor_id', $request->user()->id)
            ->orderBy('title')
            ->get();

        $selectedCourse = null;
        $rows = collect();
        $gates = [];
        $summary = [
            'active_concepts' => 0,
            'students_with_mastery' => 0,
            'complete_profiles' => 0,
            'distinct_profiles' => 0,
            'explainable_profiles' => 0,
            'passed_gates' => 0,
            'total_gates' => 4,
            'passed' => false,
        ];

        if ($courses->isNotEmpty()) {
            $requestedCourseId = (int) $request->query('course_id', $courses->first()->id);
            $selectedCourse = $courses->firstWhere('id', $requestedCourseId) ?? $courses->first();
            Gate::authorize('update', $selectedCourse);

            $concepts = LearningConcept::query()
                ->where('course_id', $selectedCourse->id)
                ->where('is_active', true)
                ->orderBy('order')
                ->orderBy('code')
                ->get(['id', 'code', 'name']);

            $conceptIds = $concepts->pluck('id');
            $studentIds = LearnerMastery::query()
                ->whereIn('concept_id', $conceptIds)
                ->distinct()
                ->pluck('student_id');

            $students = User::query()
                ->whereIn('id', $studentIds)
                ->orderBy('name')
                ->get();

            $rows = $students->map(function (User $student) use ($concepts, $conceptIds): array {
                $masteries = LearnerMastery::query()
                    ->where('student_id', $student->id)
                    ->whereIn('concept_id', $conceptIds)
                    ->get()
                    ->keyBy('concept_id');

                $historyConceptIds = MasteryHistory::query()
                    ->where('student_id', $student->id)
                    ->whereIn('concept_id', $conceptIds)
                    ->where('evidence_type', 'diagnostic_quiz_attempt')
                    ->distinct()
                    ->pluck('concept_id');

                $profile = $concepts->map(function (LearningConcept $concept) use ($masteries): array {
                    $mastery = $masteries->get($concept->id);

                    return [
                        'concept_id' => $concept->id,
                        'code' => $concept->code,
                        'score' => $mastery?->mastery_score,
                        'evidence_count' => $mastery?->evidence_count ?? 0,
                    ];
                });

                $observed = $profile->whereNotNull('score');
                $complete = $concepts->isNotEmpty() && $observed->count() === $concepts->count();
                $historyComplete = $observed->every(fn (array $item): bool => $historyConceptIds->contains($item['concept_id']));
                $positiveEvidence = $observed->every(fn (array $item): bool => $item['evidence_count'] > 0);
                $explainable = $observed->isNotEmpty() && $historyComplete && $positiveEvidence;
                $signature = $complete
                    ? $profile->map(fn (array $item): string => number_format((float) $item['score'], 2, '.', ''))->implode('|')
                    : null;

                return [
                    'student' => $student,
                    'profile' => $profile,
                    'observed_concepts' => $observed->count(),
                    'total_concepts' => $concepts->count(),
                    'evidence_count' => (int) $profile->sum('evidence_count'),
                    'complete' => $complete,
                    'explainable' => $explainable,
                    'signature' => $signature,
                ];
            });

            $completeRows = $rows->where('complete', true);
            $distinctProfiles = $completeRows
                ->pluck('signature')
                ->filter()
                ->unique()
                ->count();
            $explainableProfiles = $completeRows->where('explainable', true)->count();

            $gates = [
                [
                    'passed' => $concepts->isNotEmpty(),
                    'title' => 'Domain Model aktif tersedia untuk audit Learner Model',
                    'detail' => $concepts->count().' concept aktif menjadi dimensi profil mastery.',
                ],
                [
                    'passed' => $completeRows->count() >= 2,
                    'title' => 'Minimal dua siswa memiliki profil mastery lengkap',
                    'detail' => $completeRows->count().' siswa memiliki evidence pada seluruh concept aktif.',
                ],
                [
                    'passed' => $distinctProfiles >= 2,
                    'title' => 'Pola jawaban berbeda menghasilkan profil mastery berbeda',
                    'detail' => $distinctProfiles.' profil mastery unik terdeteksi pada siswa dengan profil lengkap.',
                ],
                [
                    'passed' => $completeRows->count() >= 2 && $explainableProfiles === $completeRows->count(),
                    'title' => 'Setiap mastery dapat ditelusuri ke evidence diagnostik',
                    'detail' => $explainableProfiles.' dari '.$completeRows->count().' profil lengkap memiliki diagnostic history dan evidence count yang dapat ditelusuri.',
                ],
            ];

            $passedGates = collect($gates)->where('passed', true)->count();
            $summary = [
                'active_concepts' => $concepts->count(),
                'students_with_mastery' => $rows->count(),
                'complete_profiles' => $completeRows->count(),
                'distinct_profiles' => $distinctProfiles,
                'explainable_profiles' => $explainableProfiles,
                'passed_gates' => $passedGates,
                'total_gates' => count($gates),
                'passed' => $passedGates === count($gates),
            ];
        }

        return view('instructor.mastery.audit', compact('courses', 'selectedCourse', 'rows', 'gates', 'summary'));
    }

    public function show(Request $request, Course $course, User $student): View
    {
        Gate::authorize('update', $course);
        abort_unless($course->instructor_id === $request->user()->id, 403);

        $concepts = LearningConcept::query()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->with('learningObjective')
            ->orderBy('order')
            ->orderBy('code')
            ->get();

        $masteries = LearnerMastery::query()
            ->where('student_id', $student->id)
            ->whereIn('concept_id', $concepts->pluck('id'))
            ->get()
            ->keyBy('concept_id');

        abort_if($masteries->isEmpty(), 404);

        $history = MasteryHistory::query()
            ->where('student_id', $student->id)
            ->whereIn('concept_id', $concepts->pluck('id'))
            ->with('concept:id,code,name')
            ->latest('created_at')
            ->get();

        $profile = $concepts->map(function (LearningConcept $concept) use ($masteries): array {
            $mastery = $masteries->get($concept->id);

            return [
                'concept' => $concept,
                'mastery' => $mastery,
                'score' => $mastery?->mastery_score,
                'evidence_count' => $mastery?->evidence_count ?? 0,
                'last_evidence_at' => $mastery?->last_evidence_at,
            ];
        });

        $observed = $profile->whereNotNull('score');
        $summary = [
            'observed_concepts' => $observed->count(),
            'total_concepts' => $profile->count(),
            'average_mastery' => $observed->isEmpty() ? null : round((float) $observed->avg('score'), 2),
            'evidence_count' => (int) $profile->sum('evidence_count'),
            'history_count' => $history->count(),
        ];

        return view('instructor.mastery.show', compact('course', 'student', 'profile', 'history', 'summary'));
    }
}
