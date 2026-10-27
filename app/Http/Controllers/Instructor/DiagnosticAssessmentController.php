<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\Quiz\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DiagnosticAssessmentController extends Controller
{
    public function __construct(private QuizService $quizService) {}

    public function index(Request $request): View
    {
        $courses = Course::query()
            ->where('instructor_id', $request->user()->id)
            ->orderBy('title')
            ->get();

        $selectedCourse = null;
        $rows = collect();

        if ($courses->isNotEmpty()) {
            $requestedCourseId = (int) $request->query('course_id', $courses->first()->id);
            $selectedCourse = $courses->firstWhere('id', $requestedCourseId) ?? $courses->first();

            Gate::authorize('update', $selectedCourse);

            $quizzes = Quiz::query()
                ->where('course_id', $selectedCourse->id)
                ->with([
                    'questions' => fn ($query) => $query
                        ->select('questions.id', 'question_bank_id', 'question_text')
                        ->with('learningConcepts:id,course_id,code,name'),
                ])
                ->withCount('attempts')
                ->latest()
                ->get();

            $rows = $quizzes->map(function (Quiz $quiz): array {
                $questionCount = $quiz->questions->count();
                $mappedCount = $quiz->questions
                    ->filter(fn ($question): bool => $question->learningConcepts->isNotEmpty())
                    ->count();
                $unmappedCount = $questionCount - $mappedCount;

                return [
                    'quiz' => $quiz,
                    'question_count' => $questionCount,
                    'mapped_count' => $mappedCount,
                    'unmapped_count' => $unmappedCount,
                    'concept_ready' => $questionCount > 0 && $unmappedCount === 0,
                    'editable' => $quiz->status === 'draft' && $quiz->attempts_count === 0,
                ];
            });
        }

        return view('instructor.diagnostics.index', compact('courses', 'selectedCourse', 'rows'));
    }

    public function updateType(Request $request, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('update', $quiz);

        $validated = $request->validate([
            'assessment_type' => ['required', Rule::in([
                Quiz::ASSESSMENT_DIAGNOSTIC,
                Quiz::ASSESSMENT_FORMATIVE,
                Quiz::ASSESSMENT_SUMMATIVE,
            ])],
        ]);

        $this->quizService->updateAssessmentType($quiz, $validated['assessment_type']);

        return redirect()
            ->route('instructor.diagnostics.index', ['course_id' => $quiz->course_id])
            ->with('success', 'Tipe assessment berhasil diperbarui.');
    }
}
