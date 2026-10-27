<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Jobs\ExtractQuestionsAIJob;
use App\Models\AIProcessingLog;
use App\Models\QuestionBank;
use App\Models\QuestionDocument;
use App\Services\Document\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AIQuestionExtractController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * Upload a document for AI question extraction.
     */
    public function uploadDocument(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('update', $questionBank);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,docx', 'extensions:pdf,docx', 'max:10240'], // 10MB max
        ]);

        $file = $request->file('document');

        $this->documentService->storeQuestionDocument($questionBank, $file, $request->user());

        return back()->with('success', 'Dokumen berhasil diunggah. Silakan klik "Ekstrak dengan AI" untuk mulai memproses naskah soal.');
    }

    /**
     * Dispatch the AI question extraction job.
     */
    public function extract(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('update', $questionBank);

        $request->validate([
            'document_id' => ['required', 'exists:question_documents,id'],
        ]);

        $document = QuestionDocument::where('question_bank_id', $questionBank->id)
            ->findOrFail($request->input('document_id'));

        try {
            $documentText = $this->documentService->extractTextFromQuestionDocument($document);
            $startedAt = now()->timestamp;

            ExtractQuestionsAIJob::dispatch($questionBank, $documentText);

            return redirect()->route('instructor.question-banks.review', [
                'question_bank' => $questionBank,
                'processing' => 1,
                'started_at' => $startedAt,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI question extraction failed', [
                'exception_type' => $e::class,
                'document_id' => $document->id,
            ]);

            return back()->with('error', 'Proses AI gagal. Silakan coba kembali.');
        }
    }

    /**
     * Show the review page or a truthful processing state for AI extraction.
     */
    public function review(Request $request, QuestionBank $questionBank): View|RedirectResponse
    {
        Gate::authorize('view', $questionBank);

        $questions = $questionBank->questions()
            ->where('needs_review', true)
            ->with('options')
            ->get();
        $totalQuestionCount = $questionBank->questions()->count();

        $latestLog = AIProcessingLog::query()
            ->where('process_type', AIProcessingLog::PROCESS_QUESTION_EXTRACTION)
            ->where('source_type', QuestionBank::class)
            ->where('source_id', $questionBank->id)
            ->latest()
            ->first();

        if ($request->boolean('processing')) {
            $startedAt = max(0, (int) $request->query('started_at', 0));
            $currentLog = $latestLog;

            if ($currentLog && $startedAt > 0 && $currentLog->created_at?->timestamp < ($startedAt - 5)) {
                $currentLog = null;
            }

            if (! $currentLog) {
                return $this->statusView($questionBank, 'queued', $totalQuestionCount, $startedAt);
            }

            if (in_array($currentLog->status, [AIProcessingLog::STATUS_PENDING, AIProcessingLog::STATUS_PROCESSING], true)) {
                return $this->statusView($questionBank, 'processing', $totalQuestionCount, $startedAt);
            }

            if ($currentLog->status === AIProcessingLog::STATUS_FAILED) {
                return $this->statusView($questionBank, 'failed', $totalQuestionCount, $startedAt);
            }

            if ($currentLog->status === AIProcessingLog::STATUS_COMPLETED && $questions->isEmpty()) {
                if ($totalQuestionCount > 0) {
                    return redirect()
                        ->route('instructor.question-banks.show', $questionBank)
                        ->with('success', "Ekstraksi AI selesai. {$totalQuestionCount} soal tersedia di bank soal dan tidak ada yang memerlukan review manual.");
                }

                return $this->statusView($questionBank, 'empty', 0, $startedAt);
            }
        }

        if ($questions->isNotEmpty()) {
            return view('instructor.question_banks.review', [
                'questionBank' => $questionBank,
                'questions' => $questions,
            ]);
        }

        if ($totalQuestionCount > 0) {
            return redirect()->route('instructor.question-banks.show', $questionBank);
        }

        if ($latestLog?->status === AIProcessingLog::STATUS_FAILED) {
            return $this->statusView($questionBank, 'failed', 0, 0);
        }

        return $this->statusView($questionBank, 'idle', 0, 0);
    }

    private function statusView(QuestionBank $questionBank, string $state, int $totalQuestionCount, int $startedAt): View
    {
        return view('instructor.question_banks.extraction-status', [
            'questionBank' => $questionBank,
            'state' => $state,
            'totalQuestionCount' => $totalQuestionCount,
            'startedAt' => $startedAt,
        ]);
    }
}
