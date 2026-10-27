<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\MasteryHistory;
use App\Models\User;
use Illuminate\Http\Request;
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
