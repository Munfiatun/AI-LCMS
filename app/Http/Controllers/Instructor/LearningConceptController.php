<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningConcept;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LearningConceptController extends Controller
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
                'learningObjectives' => fn ($query) => $query->orderBy('order')->orderBy('code'),
                'learningConcepts' => fn ($query) => $query
                    ->with(['learningObjective', 'prerequisites', 'materials', 'questions'])
                    ->orderBy('order')
                    ->orderBy('code'),
                'sections.materials.learningConcepts',
                'questionBanks.questions.learningConcepts',
            ]);
        }

        return view('instructor.learning_concepts.index', compact('courses', 'selectedCourse'));
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate($this->rules($course));
        $validated['course_id'] = $course->id;
        $validated['order'] = $validated['order'] ?? ((int) $course->learningConcepts()->max('order') + 1);

        LearningConcept::create($validated);

        return back()->with('success', 'Learning Concept berhasil ditambahkan.');
    }

    public function update(Request $request, LearningConcept $learningConcept): RedirectResponse
    {
        $course = $learningConcept->course;
        Gate::authorize('update', $course);

        $request->merge([
            'code' => Str::upper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate($this->rules($course, $learningConcept));
        $learningConcept->update($validated);

        return back()->with('success', 'Learning Concept berhasil diperbarui.');
    }

    public function destroy(LearningConcept $learningConcept): RedirectResponse
    {
        Gate::authorize('update', $learningConcept->course);

        $learningConcept->delete();

        return back()->with('success', 'Learning Concept berhasil dihapus beserta mapping-nya.');
    }

    /** @return array<string, mixed> */
    private function rules(Course $course, ?LearningConcept $learningConcept = null): array
    {
        return [
            'learning_objective_id' => [
                'nullable',
                'integer',
                Rule::exists('learning_objectives', 'id')
                    ->where(fn ($query) => $query->where('course_id', $course->id)),
            ],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('learning_concepts', 'code')
                    ->where(fn ($query) => $query->where('course_id', $course->id))
                    ->ignore($learningConcept?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => [$learningConcept ? 'required' : 'nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
