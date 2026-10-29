<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionDocument;
use App\Services\AI\AIQuestionAuthoringService;
use App\Services\Document\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AIQuestionAuthoringController extends Controller
{
    public function __construct(
        private AIQuestionAuthoringService $authoringService,
        private DocumentService $documentService,
    ) {}

    public function create(Request $request, QuestionBank $questionBank): View
    {
        Gate::authorize('create', [Question::class, $questionBank]);
        $questionBank->loadMissing('course');

        $sourceMaterials = $questionBank->course
            ? $questionBank->course->materials()->with('section')->orderBy('learning_materials.order')->get()
            : collect();
        $sourceDocuments = $questionBank->documents()->latest()->get();

        return view('instructor.question_banks.ai-create', [
            'questionBank' => $questionBank,
            'sourceMaterials' => $sourceMaterials,
            'sourceDocuments' => $sourceDocuments,
            'provider' => (string) config('ai.provider', 'mock'),
            'model' => (string) config('ai.providers.'.config('ai.provider', 'mock').'.model', ''),
        ]);
    }

    public function generate(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('create', [Question::class, $questionBank]);
        $questionBank->loadMissing('course');

        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:255'],
            'total_questions' => ['required', 'integer', 'min:'.AIQuestionAuthoringService::MIN_QUESTIONS, 'max:'.AIQuestionAuthoringService::MAX_QUESTIONS],
            'difficulty' => ['required', 'in:easy,medium,hard,mixed'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'source_material_id' => ['nullable', 'integer', 'min:1'],
            'source_document_id' => ['nullable', 'integer', 'min:1'],
            'source_document' => ['nullable', 'file', 'mimes:pdf,docx', 'extensions:pdf,docx', 'max:10240'],
            'force_regenerate' => ['nullable', 'boolean'],
        ]);

        $selectedSourceCount = collect([
            ! empty($validated['source_material_id']),
            ! empty($validated['source_document_id']),
            $request->hasFile('source_document'),
        ])->filter()->count();

        if ($selectedSourceCount > 1) {
            return back()->withInput()->withErrors([
                'source' => 'Pilih satu sumber saja: materi dalam sistem, dokumen yang sudah diunggah, atau upload PDF/Word baru.',
            ]);
        }

        try {
            $sourceDocument = null;
            if ($request->hasFile('source_document')) {
                $sourceDocument = $this->documentService->storeQuestionDocument(
                    $questionBank,
                    $request->file('source_document'),
                    $request->user(),
                );
            } elseif (! empty($validated['source_document_id'])) {
                $sourceDocument = QuestionDocument::where('question_bank_id', $questionBank->id)
                    ->findOrFail((int) $validated['source_document_id']);
            }

            if ($sourceDocument) {
                $documentText = trim($this->documentService->extractTextFromQuestionDocument($sourceDocument));
                if ($documentText === '') {
                    throw ValidationException::withMessages([
                        'source_document' => 'Dokumen tidak memiliki teks yang dapat diekstrak. Gunakan PDF teks atau DOCX yang valid.',
                    ]);
                }

                $validated['source_document_id'] = $sourceDocument->id;
                $validated['source_document_name'] = $sourceDocument->original_name;
                $validated['source_document_text'] = mb_substr($documentText, 0, 28000);
            }

            $draft = $this->authoringService->generateDraft(
                $questionBank,
                $validated,
                (bool) ($validated['force_regenerate'] ?? false),
            );
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->withErrors(['ai' => 'AI belum berhasil membuat draft soal yang lengkap dan konsisten. Silakan generate ulang atau sederhanakan instruksi.']);
        }

        $request->session()->put($this->draftKey($questionBank), [
            'questions' => $draft['questions'],
            'settings' => [
                'topic' => $validated['topic'],
                'total_questions' => (int) $validated['total_questions'],
                'difficulty' => $validated['difficulty'],
                'instructions' => $validated['instructions'] ?? '',
                'source_material_id' => $validated['source_material_id'] ?? null,
                'source_document_id' => $validated['source_document_id'] ?? null,
            ],
            'source_material' => $draft['source_material'] ?? null,
            'source_document' => $draft['source_document'] ?? null,
            'provider' => $draft['provider'],
            'model' => $draft['model'],
            'generated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('instructor.question-banks.ai-authoring.review', $questionBank);
    }

    public function review(Request $request, QuestionBank $questionBank): View|RedirectResponse
    {
        Gate::authorize('create', [Question::class, $questionBank]);

        $draft = $request->session()->get($this->draftKey($questionBank));
        if (! is_array($draft) || empty($draft['questions'])) {
            return redirect()
                ->route('instructor.question-banks.ai-authoring.create', $questionBank)
                ->with('info', 'Belum ada draft AI. Buat draft soal terlebih dahulu.');
        }

        return view('instructor.question_banks.ai-review', [
            'questionBank' => $questionBank,
            'draft' => $draft,
        ]);
    }

    public function store(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('create', [Question::class, $questionBank]);

        if (! $request->session()->has($this->draftKey($questionBank))) {
            throw ValidationException::withMessages([
                'questions' => 'Draft AI sudah tidak tersedia. Buat ulang draft sebelum menyimpan.',
            ]);
        }

        $validated = $request->validate([
            'questions' => ['required', 'array', 'list', 'min:1', 'max:'.AIQuestionAuthoringService::MAX_QUESTIONS],
            'questions.*.question_text' => ['required', 'string', 'max:1200'],
            'questions.*.topic' => ['nullable', 'string', 'max:100'],
            'questions.*.difficulty' => ['required', 'in:easy,medium,hard'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
            'questions.*.points' => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.options' => ['required', 'array', 'list', 'size:4'],
            'questions.*.options.*' => ['required', 'string', 'max:255'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0', 'max:3'],
        ]);

        $created = $this->authoringService->persistReviewedDraft($questionBank, $validated['questions']);
        $request->session()->forget($this->draftKey($questionBank));

        return redirect()
            ->route('instructor.question-banks.show', $questionBank)
            ->with('success', $created->count().' soal AI telah direview dan disimpan ke bank soal.');
    }

    public function destroy(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('create', [Question::class, $questionBank]);
        $request->session()->forget($this->draftKey($questionBank));

        return redirect()
            ->route('instructor.question-banks.show', $questionBank)
            ->with('info', 'Draft soal AI dibatalkan. Tidak ada soal yang disimpan.');
    }

    private function draftKey(QuestionBank $questionBank): string
    {
        return 'ai_question_authoring.'.$questionBank->getKey();
    }
}
