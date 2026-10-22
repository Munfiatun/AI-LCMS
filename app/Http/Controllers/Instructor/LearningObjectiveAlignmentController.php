<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LearningObjectiveAlignmentController extends Controller
{
    public function syncMaterial(Request $request, LearningMaterial $material): RedirectResponse
    {
        Gate::authorize('update', $material);

        $material->loadMissing('section.course');
        $course = $material->section->course;
        $objectiveIds = $this->validatedObjectiveIds($request, $course);

        $material->learningObjectives()->sync($objectiveIds);

        return back()->with('success', 'Alignment Learning Objective untuk materi berhasil diperbarui.');
    }

    public function syncQuestion(Request $request, Question $question): RedirectResponse
    {
        Gate::authorize('update', $question);

        $question->loadMissing('questionBank.course');
        $course = $question->questionBank->course;

        if (! $course) {
            return back()->withErrors([
                'learning_objective_ids' => 'Bank soal harus terhubung ke kursus sebelum soal dapat dipetakan ke Learning Objective.',
            ]);
        }

        $objectiveIds = $this->validatedObjectiveIds($request, $course);
        $question->learningObjectives()->sync($objectiveIds);

        return back()->with('success', 'Alignment Learning Objective untuk soal berhasil diperbarui.');
    }

    /** @return list<int> */
    private function validatedObjectiveIds(Request $request, Course $course): array
    {
        $validated = $request->validate([
            'learning_objective_ids' => ['sometimes', 'array'],
            'learning_objective_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('learning_objectives', 'id')
                    ->where(fn ($query) => $query->where('course_id', $course->id)),
            ],
        ]);

        return collect($validated['learning_objective_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
