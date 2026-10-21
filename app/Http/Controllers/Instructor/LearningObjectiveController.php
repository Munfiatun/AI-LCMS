<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningObjective;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LearningObjectiveController extends Controller
{
    public function index(Request $request): View
    {
        $courses = Course::query()
            ->where('instructor_id', $request->user()->id)
            ->orderBy('title')
            ->get();

        $selectedCourse = null;

        if ($courses->isNotEmpty()) {
            $requestedCourseId = (int) $request->query('course_id', $courses->first()->id);
            $selectedCourse = $courses->firstWhere('id', $requestedCourseId) ?? $courses->first();

            Gate::authorize('update', $selectedCourse);

            $selectedCourse->load([
                'learningObjectives' => fn ($query) => $query
                    ->withCount(['materials', 'questions'])
                    ->orderBy('order')
                    ->orderBy('code'),
                'sections.materials.learningObjectives',
                'questionBanks.questions.learningObjectives',
            ]);
        }

        return view('instructor.learning_objectives.index', compact('courses', 'selectedCourse'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('learning_objectives', 'code')
                    ->where(fn ($query) => $query->where('course_id', $course->id)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['course_id'] = $course->id;
        $validated['order'] = $validated['order'] ?? ((int) $course->learningObjectives()->max('order') + 1);

        LearningObjective::create($validated);

        return back()->with('success', 'Learning Objective berhasil ditambahkan.');
    }

    public function update(Request $request, LearningObjective $learningObjective): RedirectResponse
    {
        $course = $learningObjective->course;
        Gate::authorize('update', $course);

        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('learning_objectives', 'code')
                    ->where(fn ($query) => $query->where('course_id', $course->id))
                    ->ignore($learningObjective->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $learningObjective->update($validated);

        return back()->with('success', 'Learning Objective berhasil diperbarui.');
    }

    public function destroy(LearningObjective $learningObjective): RedirectResponse
    {
        Gate::authorize('update', $learningObjective->course);

        $learningObjective->delete();

        return back()->with('success', 'Learning Objective berhasil dihapus beserta alignment-nya.');
    }
}
