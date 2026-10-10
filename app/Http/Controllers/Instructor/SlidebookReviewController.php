<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSlidebookDesignRequest;
use App\Models\LearningMaterial;
use App\Models\Slidebook;
use App\Services\SlidebookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SlidebookReviewController extends Controller
{
    public function __construct(private SlidebookService $service) {}

    /**
     * Show the side-by-side Slidebook review page.
     */
    public function show(Request $request, LearningMaterial $material): View|RedirectResponse
    {
        Gate::authorize('generate', [Slidebook::class, $material]);

        $material->load([
            'section.course',
            'documents.extraction',
        ]);

        $slidebook = $material->slidebooks()
            ->whereIn('status', [Slidebook::STATUS_DRAFT, Slidebook::STATUS_REVIEW])
            ->with(['slides', 'material'])
            ->latest('version')
            ->first();

        if (! $slidebook) {
            $slidebook = $material->slidebooks()
                ->where('status', Slidebook::STATUS_PUBLISHED)
                ->with(['slides', 'material'])
                ->latest('version')
                ->first();
        }

        if (! $slidebook) {
            return redirect()
                ->route('instructor.materials.edit', $material)
                ->with('error', 'Materi ini belum memiliki Slidebook. Silakan generate terlebih dahulu.');
        }

        return view('instructor.slidebooks.review', [
            'material' => $material,
            'slidebook' => $slidebook,
            'course' => $material->section->course,
            'reviewErrors' => $this->service->reviewErrors($slidebook),
            'publicationErrors' => $this->service->publicationErrors($slidebook),
        ]);
    }

    /**
     * Preview the slidebook from the student's perspective.
     *
     * Optional design query parameters are applied only in memory so the
     * instructor can compare themes/audiences before saving.
     */
    public function preview(Request $request, Slidebook $slidebook): View
    {
        Gate::authorize('view', $slidebook);

        $validated = $request->validate([
            'preset' => ['nullable', 'string', 'in:indigo-dark,modern-tech,academic-blue,creative-education,fresh-learning,minimalist'],
            'audience' => ['nullable', 'string', 'in:general,smp,sma-smk'],
            'embedded' => ['nullable', 'boolean'],
        ]);

        $designOverrides = [];
        if (! empty($validated['preset'])) {
            $designOverrides['preset'] = $validated['preset'];
        }
        if (! empty($validated['audience'])) {
            $designOverrides['audience'] = $validated['audience'];
        }

        if ($designOverrides !== []) {
            $slidebook->setAttribute('design_settings', array_merge(
                $slidebook->design_settings ?? [],
                $designOverrides
            ));
        }

        $slidebook->load([
            'slides' => fn ($q) => $q->orderBy('order'),
            'material.section.course.instructor',
        ]);

        return view('student.slidebooks.show', [
            'slidebook' => $slidebook,
            'material' => $slidebook->material,
            'course' => $slidebook->material->section->course,
            'slides' => $slidebook->slides,
            'isPreview' => true,
            'embedded' => (bool) ($validated['embedded'] ?? false),
        ]);
    }

    /**
     * Approve the Slidebook draft.
     */
    public function approve(Request $request, Slidebook $slidebook): RedirectResponse
    {
        Gate::authorize('approve', $slidebook);
        $this->service->approve($slidebook, $request->user());

        return back()->with('success', 'Slidebook berhasil disetujui (Approved) dan tersimpan sebagai draft siap rilis.');
    }

    /**
     * Publish the Slidebook to enrolled students.
     */
    public function publish(Request $request, Slidebook $slidebook): RedirectResponse
    {
        Gate::authorize('publish', $slidebook);
        $this->service->publish($slidebook);

        return back()->with('success', 'Slidebook resmi dipublikasikan! Siswa yang terdaftar kini dapat membaca materi ini.');
    }

    public function revision(Request $request, Slidebook $slidebook): RedirectResponse
    {
        Gate::authorize('update', $slidebook);
        $this->service->createRevision($slidebook, $request->user());

        return redirect()->route('instructor.materials.slidebook.review', $slidebook->material_id)
            ->with('success', 'Revision draft siap diedit. Versi published tetap tersedia untuk siswa.');
    }

    public function updateDesign(UpdateSlidebookDesignRequest $request, Slidebook $slidebook): RedirectResponse
    {
        Gate::authorize('update', $slidebook);

        $slidebook->update([
            'design_settings' => array_merge(
                $slidebook->design_settings ?? [],
                $request->validated()
            ),
        ]);

        return back()->with('success', 'Pengaturan desain berhasil diperbarui.');
    }
}
