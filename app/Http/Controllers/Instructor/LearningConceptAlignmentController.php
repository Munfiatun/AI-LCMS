<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningConcept;
use App\Models\LearningMaterial;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LearningConceptAlignmentController extends Controller
{
    public function syncPrerequisites(Request $request, LearningConcept $learningConcept): RedirectResponse
    {
        Gate::authorize('update', $learningConcept->course);

        $conceptIds = $this->validatedConceptIds($request, $learningConcept->course);

        if (in_array($learningConcept->id, $conceptIds, true)) {
            throw ValidationException::withMessages([
                'learning_concept_ids' => 'Sebuah konsep tidak dapat menjadi prerequisite bagi dirinya sendiri.',
            ]);
        }

        foreach ($conceptIds as $conceptId) {
            if ($this->wouldCreateCycle($learningConcept, $conceptId)) {
                throw ValidationException::withMessages([
                    'learning_concept_ids' => 'Mapping prerequisite membentuk siklus. Periksa kembali urutan konsep.',
                ]);
            }
        }

        $syncData = [];
        foreach ($conceptIds as $index => $conceptId) {
            $syncData[$conceptId] = [
                'weight' => 1.00,
                'order' => $index + 1,
            ];
        }

        $learningConcept->prerequisites()->sync($syncData);

        return back()->with('success', 'Prerequisite konsep berhasil diperbarui.');
    }

    public function syncMaterial(Request $request, LearningMaterial $material): RedirectResponse
    {
        Gate::authorize('update', $material);

        $material->loadMissing('section.course');
        $course = $material->section->course;
        $conceptIds = $this->validatedConceptIds($request, $course);

        $syncData = collect($conceptIds)
            ->mapWithKeys(fn (int $conceptId): array => [$conceptId => ['relevance_weight' => 1.00]])
            ->all();

        $material->learningConcepts()->sync($syncData);

        return back()->with('success', 'Mapping konsep untuk materi berhasil diperbarui.');
    }

    public function syncQuestion(Request $request, Question $question): RedirectResponse
    {
        Gate::authorize('update', $question);

        $question->loadMissing('questionBank.course');
        $course = $question->questionBank->course;

        if (! $course) {
            return back()->withErrors([
                'learning_concept_ids' => 'Bank soal harus terhubung ke kursus sebelum soal dapat dipetakan ke konsep.',
            ]);
        }

        $conceptIds = $this->validatedConceptIds($request, $course);
        $syncData = collect($conceptIds)
            ->mapWithKeys(fn (int $conceptId): array => [
                $conceptId => [
                    'weight' => 1.00,
                    'difficulty_level' => $question->difficulty,
                ],
            ])
            ->all();

        $question->learningConcepts()->sync($syncData);

        return back()->with('success', 'Mapping konsep untuk soal berhasil diperbarui.');
    }

    /** @return list<int> */
    private function validatedConceptIds(Request $request, Course $course): array
    {
        $validated = $request->validate([
            'learning_concept_ids' => ['sometimes', 'array'],
            'learning_concept_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('learning_concepts', 'id')
                    ->where(fn ($query) => $query->where('course_id', $course->id)),
            ],
        ]);

        return collect($validated['learning_concept_ids'] ?? [])
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function wouldCreateCycle(LearningConcept $concept, int $candidatePrerequisiteId): bool
    {
        $queue = [$candidatePrerequisiteId];
        $visited = [];

        while ($queue !== []) {
            $currentId = array_shift($queue);

            if ($currentId === $concept->id) {
                return true;
            }

            if (isset($visited[$currentId])) {
                continue;
            }

            $visited[$currentId] = true;

            $prerequisiteIds = LearningConcept::query()
                ->find($currentId)?->prerequisites()
                ->pluck('learning_concepts.id')
                ->map(fn ($id): int => (int) $id)
                ->all() ?? [];

            foreach ($prerequisiteIds as $prerequisiteId) {
                if (! isset($visited[$prerequisiteId])) {
                    $queue[] = $prerequisiteId;
                }
            }
        }

        return false;
    }
}
