<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\ExperimentAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ResearchExperimentController extends Controller
{
    public function show(Course $course): View
    {
        Gate::authorize('update', $course);

        $course->load([
            'enrollments.student',
        ]);

        $assignments = ExperimentAssignment::query()
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('student_id');

        return view('instructor.research.experiment', compact('course', 'assignments'));
    }

    public function updateAssignment(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('course_enrollments', 'student_id')
                    ->where(fn ($query) => $query->where('course_id', $course->id)),
            ],
            'condition' => ['required', Rule::in([
                ExperimentAssignment::CONDITION_ADAPTIVE,
                ExperimentAssignment::CONDITION_NON_ADAPTIVE,
            ])],
            'class_cluster' => ['nullable', 'string', 'max:100'],
            'assignment_version' => ['required', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($course, $validated, $request): void {
            $assignment = ExperimentAssignment::query()->lockForUpdate()->firstOrNew([
                'course_id' => $course->id,
                'student_id' => $validated['student_id'],
            ]);
            $wasControl = $assignment->condition === ExperimentAssignment::CONDITION_NON_ADAPTIVE;

            $assignment->fill([
                'condition' => $validated['condition'],
                'class_cluster' => $validated['class_cluster'] ?? null,
                'assignment_version' => $validated['assignment_version'],
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
            ])->save();

            if ($wasControl || $assignment->condition === ExperimentAssignment::CONDITION_NON_ADAPTIVE) {
                AdaptiveRecommendation::query()
                    ->where('student_id', $assignment->student_id)
                    ->where('course_id', $course->id)
                    ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
                    ->update([
                        'status' => AdaptiveRecommendation::STATUS_SUPERSEDED,
                        'superseded_at' => now(),
                    ]);
            }
        });

        return back()->with('success', 'Kondisi eksperimen siswa berhasil diperbarui.');
    }
}
