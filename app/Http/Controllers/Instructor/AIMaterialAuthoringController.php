<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Services\AI\AIMaterialAuthoringService;
use App\Services\AI\AIMaterialRevisionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AIMaterialAuthoringController extends Controller
{
    public function __construct(
        private AIMaterialAuthoringService $authoringService,
        private AIMaterialRevisionService $revisionService,
    ) {}

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
        } catch (ValidationException $exception) {
            report($exception);
            $message = collect($exception->errors())->flatten()->first();

            return back()
                ->withInput()
                ->withErrors([
                    'ai' => 'AI sudah menghasilkan draft, tetapi formatnya belum lolos pemeriksaan sistem.'
                        .($message ? ' '.$message : ' Silakan generate ulang.'),
                ]);
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
            'revision_count' => 0,
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

    public function revise(Request $request, CourseSection $section): RedirectResponse
    {
        Gate::authorize('create', [LearningMaterial::class, $section]);
        $section->loadMissing('course');

        if (! $request->session()->has($this->draftKey($section))) {
            throw ValidationException::withMessages([
                'revision_instruction' => 'Draft materi AI sudah tidak tersedia. Buat ulang draft sebelum meminta revisi.',
            ]);
        }

        $validated = $request->validate([
            'revision_instruction' => ['required', 'string', 'min:3', 'max:1500'],
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

        try {
            $result = $this->revisionService->reviseDraft(
                $section,
                $validated['material'],
                $validated['revision_instruction'],
            );
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'revision_instruction' => $this->safeRevisionErrorMessage($exception),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'revision_instruction' => 'AI belum berhasil merevisi draft materi. Draft lama tetap aman dan belum berubah.',
            ]);
        }

        $draft = $request->session()->get($this->draftKey($section), []);
        $draft['material'] = $result['material'];
        $draft['provider'] = $result['provider'];
        $draft['model'] = $result['model'];
        $draft['revision_count'] = ((int) ($draft['revision_count'] ?? 0)) + 1;
        $draft['last_revision'] = $validated['revision_instruction'];
        $draft['revised_at'] = now()->toIso8601String();
        $request->session()->put($this->draftKey($section), $draft);

        return redirect()
            ->route('instructor.materials.ai-authoring.review', $section)
            ->with('success', 'Draft materi berhasil direvisi AI. Periksa kembali perubahan sebelum menyimpan.');
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

    private function safeRevisionErrorMessage(RuntimeException $exception): string
    {
        $message = trim($exception->getMessage());

        if (str_starts_with($message, 'AI menghasilkan')) {
            return $message.' Draft lama tetap dipertahankan.';
        }

        return 'AI belum berhasil merevisi draft materi. Draft lama tetap dipertahankan.';
    }

    private function draftKey(CourseSection $section): string
    {
        return 'ai_material_authoring.'.$section->getKey();
    }
}
