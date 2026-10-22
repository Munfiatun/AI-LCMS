<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMaterialRequest;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Services\MaterialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function create(CourseSection $section): View
    {
        Gate::authorize('create', [LearningMaterial::class, $section]);

        return view('instructor.materials.create', compact('section'));
    }

    public function store(StoreMaterialRequest $request, CourseSection $section, MaterialService $service): RedirectResponse
    {
        $material = $service->createMaterial($section, $request->validated());

        return redirect()->route('instructor.materials.edit', $material)->with('success', 'Materi berhasil dibuat. Anda dapat mengunggah berkas PDF/Word pendukung.');
    }

    public function edit(LearningMaterial $material, MaterialService $service): View
    {
        Gate::authorize('update', $material);

        $material->load([
            'section.course',
            'documents.extraction',
            'slidebook',
        ]);

        $publicationErrors = $service->publicationErrors($material);
        $publicationReadiness = $service->getPublicationReadiness($material);

        return view('instructor.materials.edit', compact('material', 'publicationErrors', 'publicationReadiness'));
    }

    public function update(StoreMaterialRequest $request, LearningMaterial $material, MaterialService $service): RedirectResponse
    {
        Gate::authorize('update', $material);

        $service->updateMaterial($material, $request->validated());

        return back()->with('success', 'Konten materi berhasil diperbarui.');
    }

    public function publish(LearningMaterial $material, MaterialService $service): RedirectResponse
    {
        Gate::authorize('update', $material);
        $service->publishMaterial($material);

        return back()->with('success', 'Materi berhasil dipublikasikan.');
    }

    public function preview(LearningMaterial $material): View|RedirectResponse
    {
        Gate::authorize('update', $material);

        $material->load(['section.course', 'slidebook', 'documents']);

        // If a Slidebook exists, preview the exact student renderer directly.
        // This avoids nesting the student material page and prevents instructor
        // navigation/chrome from appearing inside the preview frame.
        if ($material->slidebook) {
            return redirect()->route('instructor.slidebooks.preview', $material->slidebook);
        }

        $course = $material->section->course;
        $course->load([
            'sections.materials' => fn ($query) => $query->published(),
            'quizzes' => fn ($query) => $query->available(),
        ]);

        $isCompleted = false;
        $completedMaterialIds = [];
        $isPreview = true;

        return view('student.materials.show', compact('course', 'material', 'isCompleted', 'completedMaterialIds', 'isPreview'));
    }

    public function unpublish(LearningMaterial $material, MaterialService $service): RedirectResponse
    {
        Gate::authorize('update', $material);
        $service->unpublishMaterial($material);

        return back()->with('success', 'Materi kembali ke draft untuk diedit dan ditinjau.');
    }

    public function destroy(LearningMaterial $material): RedirectResponse
    {
        Gate::authorize('delete', $material);

        $course = $material->section->course;
        $material->delete();

        return redirect()->route('instructor.courses.edit', $course)->with('success', 'Materi berhasil dihapus.');
    }
}
