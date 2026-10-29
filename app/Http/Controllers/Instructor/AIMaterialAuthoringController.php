<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Services\AI\AIMaterialAuthoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AIMaterialAuthoringController extends Controller
{
    public function __construct(private AIMaterialAuthoringService $authoringService) {}

    public function create(Request $request, CourseSection $section): View
    {
        Gate::authorize('create', [LearningMaterial::class, $section]);
        $section->loadMissing('course');

        return view('instructor.materials.ai-create', [
            'section' => $section,
            'provider' => (string) config('ai.provider', 'mock'),
            'model' => (string) config('ai.providers.'.config('ai.provider', 'mock').'.model', ''),
        ]);
    }

    public function generate(Request $request, CourseSection $section): RedirectResponse
    {
        Gate::authorize('create', [LearningMaterial::class, $section]);
        $section->loadMissing('course');

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'audience' => ['required', 'string', 'max:255'],
            'depth' => ['required', 'in:brief,standard,deep'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'force_regenerate' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $this->authoringService->generateDraft(
                $section,
                $validated,
                (bool) ($validated['force_regenerate'] ?? false),
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['ai' => 'AI belum berhasil membuat draft materi. Silakan coba lagi atau sederhanakan instruksi.']);
        }

        $request->session()->put($this->draftKey($section), [
            'material' => $result['draft'],
            'settings' => [
                'topic' => $validated['topic'],
                'audience' => $validated['audience'],
                'depth' => $validated['depth'],
                'instructions' => $validated['instructions'] ?? '',
            ],
            'provider' => $result['provider'],
            'model' => $result['model'],
            'generated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('instructor.materials.ai-authoring.review', $section);
    }

    public function review(Request $request, CourseSection $section): View|RedirectResponse
    {
        Gate::authorize('create', [LearningMaterial::class, $section]);
        $section->loadMissing('course');

        $draft = $request->session()->get($this->draftKey($section));
        if (! is_array($draft) || empty($draft['material'])) {
            return redirect()
                ->route('instructor.materials.ai-authoring.create', $section)
                ->with('info', 'Belum ada draft materi AI. Buat draft terlebih dahulu.');
        }

        return view('instructor.materials.ai-review', [
            'section' => $section,
            'draft' => $draft,
        ]);
    }

    public function store(Request $request, CourseSection $section): RedirectResponse
    {
        Gate::authorize('create', [LearningMaterial::class, $section]);

        if (! $request->session()->has($this->draftKey($section))) {
            throw ValidationException::withMessages([
                'material' => 'Draft AI sudah tidak tersedia. Buat ulang draft sebelum menyimpan.',
            ]);
        }

        $validated = $request->validate([
            'material.title' => ['required', 'string', 'max:255'],
            'material.description' => ['nullable', 'string', 'max:2000'],
            'material.duration_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'material.learning_objectives' => ['required', 'array', 'list', 'min:1', 'max:8'],
            'material.learning_objectives.*' => ['required', 'string', 'max:300'],
            'material.sections' => ['required', 'array', 'list', 'min:1', 'max:10'],
            'material.sections.*.heading' => ['required', 'string', 'max:200'],
            'material.sections.*.content' => ['required', 'string', 'max:8000'],
            'material.sections.*.example' => ['nullable', 'string', 'max:5000'],
            'material.summary' => ['required', 'string', 'max:4000'],
            'material.practice' => ['nullable', 'array', 'list', 'max:8'],
            'material.practice.*' => ['required', 'string', 'max:700'],
        ]);

        $material = $this->authoringService->persistReviewedDraft($section, $validated['material']);
        $request->session()->forget($this->draftKey($section));

        return redirect()
            ->route('instructor.materials.edit', $material)
            ->with('success', 'Draft materi AI telah direview dan disimpan. Materi masih berstatus draft.');
    }

    public function destroy(Request $request, CourseSection $section): RedirectResponse
    {
        Gate::authorize('create', [LearningMaterial::class, $section]);
        $request->session()->forget($this->draftKey($section));

        return redirect()
            ->route('instructor.materials.create', $section)
            ->with('info', 'Draft materi AI dibatalkan. Tidak ada materi yang disimpan.');
    }

    private function draftKey(CourseSection $section): string
    {
        return 'ai_material_authoring.'.$section->getKey();
    }
}
