<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LearningConcept;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Services\Quiz\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    public function __construct(private QuizService $quizService) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', QuestionBank::class);

        $user = $request->user();

        $query = QuestionBank::query()
            ->with(['course', 'instructor'])
            ->withCount(['questions', 'documents'])
            ->latest();

        if (! $user->isAdmin()) {
            $query->where('instructor_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->input('course_id'));
        }

        $questionBanks = $query->paginate(10)->withQueryString();

        $courses = $user->isAdmin()
            ? Course::orderBy('title')->get()
            : Course::where('instructor_id', $user->id)->orderBy('title')->get();

        return view('instructor.question_banks.index', [
            'questionBanks' => $questionBanks,
            'courses' => $courses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', QuestionBank::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'description' => ['nullable', 'string'],
        ]);

        $courseId = isset($validated['course_id']) ? (int) $validated['course_id'] : null;
        $this->authorizeCourseSelection($request, $courseId);

        $bank = QuestionBank::create([
            'instructor_id' => $request->user()->id,
            'course_id' => $courseId,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);

        return redirect()
            ->route('instructor.question-banks.show', $bank)
            ->with('success', 'Bank soal baru berhasil dibuat. Silakan tambahkan pertanyaan atau unggah berkas naskah soal.');
    }

    public function show(Request $request, QuestionBank $questionBank): View
    {
        Gate::authorize('view', $questionBank);

        $questionBank->load([
            'course',
            'documents.uploader',
            'questions.options',
        ]);

        $user = $request->user();
        $courses = $user->isAdmin()
            ? Course::orderBy('title')->get()
            : Course::where('instructor_id', $user->id)->orderBy('title')->get();

        $concepts = $questionBank->course
            ? $questionBank->course->learningConcepts()->where('is_active', true)->orderBy('order')->orderBy('code')->get()
            : collect();

        return view('instructor.question_banks.show', [
            'questionBank' => $questionBank,
            'questions' => $questionBank->questions,
            'documents' => $questionBank->documents,
            'courses' => $courses,
            'concepts' => $concepts,
        ]);
    }

    public function createFormative(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('update', $questionBank);
        $questionBank->loadMissing('course');

        if (! $questionBank->course) {
            throw ValidationException::withMessages([
                'learning_concept_id' => 'Hubungkan bank soal ke course terlebih dahulu sebelum membuat formative quiz.',
            ]);
        }

        $validated = $request->validate([
            'learning_concept_id' => [
                'required',
                'integer',
                Rule::exists('learning_concepts', 'id')->where(fn ($query) => $query
                    ->where('course_id', $questionBank->course_id)
                    ->where('is_active', true)),
            ],
        ]);

        $questions = $questionBank->questions()->with('options')->orderBy('order')->orderBy('id')->get();
        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'learning_concept_id' => 'Bank soal belum memiliki pertanyaan.',
            ]);
        }

        $invalidCount = $questions->filter(fn (Question $question): bool =>
            $question->status !== Question::STATUS_APPROVED
            || $question->needs_review
            || $question->options->count() < 2
            || $question->options->where('is_correct', true)->count() !== 1
        )->count();

        if ($invalidCount > 0) {
            throw ValidationException::withMessages([
                'learning_concept_id' => "Masih ada {$invalidCount} soal yang belum terverifikasi atau belum memiliki kunci jawaban valid. Selesaikan review terlebih dahulu.",
            ]);
        }

        $concept = LearningConcept::query()
            ->whereKey($validated['learning_concept_id'])
            ->where('course_id', $questionBank->course_id)
            ->where('is_active', true)
            ->firstOrFail();

        $quiz = DB::transaction(function () use ($questionBank, $questions, $concept) {
            foreach ($questions as $question) {
                $question->learningConcepts()->sync([
                    $concept->id => [
                        'weight' => 1.00,
                        'difficulty_level' => $question->difficulty,
                    ],
                ]);
            }

            $quiz = $this->quizService->createQuiz($questionBank->course, [
                'title' => "Formative {$concept->code} — {$concept->name}",
                'description' => "Formative reassessment otomatis dari bank soal {$questionBank->title} untuk {$concept->code} — {$concept->name}.",
                'instructions' => 'Kerjakan seluruh soal untuk mengukur pemahaman setelah mempelajari materi rekomendasi.',
                'assessment_type' => 'formative',
                'passing_score' => 70,
                'duration_minutes' => 10,
                'total_questions' => $questions->count(),
                'randomize_questions' => true,
                'randomize_options' => true,
                'max_attempts' => 2,
            ]);

            $this->quizService->syncQuestions($quiz, $questions->values()->map(fn (Question $question, int $index): array => [
                'id' => $question->id,
                'points' => max(1, (int) ($question->points ?: 10)),
                'order' => $index + 1,
            ])->all());

            return $quiz->fresh();
        });

        return redirect()
            ->route('instructor.quizzes.show', $quiz)
            ->with('success', "Formative {$concept->code} berhasil disiapkan otomatis. Periksa hasilnya lalu klik Terbitkan Kuis.");
    }

    public function update(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('update', $questionBank);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,archived'],
        ]);

        $targetCourseId = isset($validated['course_id']) ? (int) $validated['course_id'] : null;
        $this->authorizeCourseSelection($request, $targetCourseId);

        if ($targetCourseId !== null && (int) $questionBank->course_id !== $targetCourseId) {
            $hasForeignConceptMappings = $questionBank->questions()
                ->whereHas('learningConcepts', fn ($query) => $query
                    ->where('learning_concepts.course_id', '!=', $targetCourseId))
                ->exists();

            if ($hasForeignConceptMappings) {
                throw ValidationException::withMessages([
                    'course_id' => 'Bank soal tidak dapat dipindahkan karena ada soal yang sudah dipetakan ke concept dari course lain. Sesuaikan Concept Mapping terlebih dahulu.',
                ]);
            }
        }

        $validated['course_id'] = $targetCourseId;
        $questionBank->update($validated);

        return back()->with('success', 'Informasi bank soal berhasil diperbarui.');
    }

    public function destroy(Request $request, QuestionBank $questionBank): RedirectResponse
    {
        Gate::authorize('delete', $questionBank);

        $questionBank->delete();

        return redirect()
            ->route('instructor.question-banks.index')
            ->with('success', 'Bank soal berhasil dihapus.');
    }

    private function authorizeCourseSelection(Request $request, ?int $courseId): void
    {
        if ($courseId === null || $request->user()->isAdmin()) {
            return;
        }

        $ownsCourse = Course::query()
            ->whereKey($courseId)
            ->where('instructor_id', $request->user()->id)
            ->exists();

        abort_unless($ownsCourse, 403);
    }
}
