<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\QuestionBank;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuestionBankController extends Controller
{
    /**
     * Display a listing of the instructor's question banks.
     */
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

    /**
     * Store a newly created question bank.
     */
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

    /**
     * Display the specified question bank with question list and upload options.
     */
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

        return view('instructor.question_banks.show', [
            'questionBank' => $questionBank,
            'questions' => $questionBank->questions,
            'documents' => $questionBank->documents,
            'courses' => $courses,
        ]);
    }

    /**
     * Update the specified question bank.
     */
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

    /**
     * Remove the specified question bank.
     */
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
