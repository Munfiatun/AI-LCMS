<?php

namespace App\Services\Quiz;

use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttemptQuestion;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuizService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createQuiz(Course $course, array $data): Quiz
    {
        $data['course_id'] = $course->id;
        $data['status'] = 'draft';
        $data['published_at'] = null;
        $data['assessment_type'] = $data['assessment_type'] ?? Quiz::ASSESSMENT_FORMATIVE;
        $this->validateSection($data);

        return $course->quizzes()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        $this->assertEditable($quiz);
        unset($data['status'], $data['published_at']);
        $this->validateSection(array_merge($quiz->only(['course_id', 'section_id']), $data));
        $quiz->update($data);

        return $quiz;
    }

    public function updateAssessmentType(Quiz $quiz, string $assessmentType): Quiz
    {
        $this->assertEditable($quiz);

        Validator::make(['assessment_type' => $assessmentType], [
            'assessment_type' => ['required', Rule::in([
                Quiz::ASSESSMENT_DIAGNOSTIC,
                Quiz::ASSESSMENT_FORMATIVE,
                Quiz::ASSESSMENT_SUMMATIVE,
            ])],
        ])->validate();

        $quiz->update(['assessment_type' => $assessmentType]);

        return $quiz->fresh();
    }

    /** @param array<string, mixed> $data */
    private function validateSection(array $data): void
    {
        Validator::make($data, [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'section_id' => ['nullable', 'integer', Rule::exists('course_sections', 'id')->where('course_id', $data['course_id'])],
        ])->validate();
    }

    /**
     * Sync questions to quiz with their specific points and order
     *
     * @param  array<int, array{id: int, points: int, order: int}>  $questionsData
     */
    public function syncQuestions(Quiz $quiz, array $questionsData): void
    {
        DB::transaction(function () use ($quiz, $questionsData) {
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            $this->assertEditable($quiz);
            $quiz->quizQuestions()->delete();

            foreach ($questionsData as $data) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_id' => $data['id'],
                    'points' => $data['points'] ?? 10,
                    'order' => $data['order'] ?? 1,
                ]);
            }

            $quiz->update(['total_questions' => count($questionsData)]);
        });
    }

    public function assertEditable(Quiz $quiz): void
    {
        $hasCompletedAttempts = $quiz->attempts()->whereNotNull('submitted_at')->exists();

        if ($quiz->status !== 'draft' || $hasCompletedAttempts) {
            throw ValidationException::withMessages(['quiz' => 'Quiz hanya dapat diedit saat berstatus draft dan belum memiliki attempt yang sudah selesai.']);
        }
    }

    public function assertQuestionEditable(Question $question): void
    {
        if (Quiz::where('status', 'published')->whereHas('quizQuestions', fn ($query) => $query->where('question_id', $question->id))->exists()
            || QuizAttemptQuestion::where('question_id', $question->id)->exists()) {
            throw ValidationException::withMessages(['question' => 'Soal pada quiz published atau histori attempt tidak dapat diubah/dihapus. Buat soal baru.']);
        }
    }

    public function unpublishQuiz(Quiz $quiz): void
    {
        DB::transaction(function () use ($quiz): void {
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();

            if ($quiz->status !== 'published') {
                throw ValidationException::withMessages(['quiz' => 'Hanya quiz published yang dapat dikembalikan ke draft.']);
            }

            $hasCompletedAttempts = $quiz->attempts()->whereNotNull('submitted_at')->exists();
            if ($hasCompletedAttempts) {
                throw ValidationException::withMessages(['quiz' => 'Quiz tidak dapat dikembalikan ke draft karena sudah memiliki attempt yang selesai. Histori hasil belajar harus tetap dipertahankan.']);
            }

            // A started-but-unsubmitted attempt contains no final learning evidence yet.
            // Cancel it before reopening the quiz for editing so the student never
            // resumes an obsolete question snapshot after the quiz changes.
            $quiz->attempts()->whereNull('submitted_at')->delete();

            $quiz->update(['status' => 'draft', 'published_at' => null]);
        });
    }

    /** @return list<string> */
    public function publicationErrors(Quiz $quiz): array
    {
        $quiz->loadMissing('course.instructor.role');
        if ($quiz->status !== 'draft' || ! $quiz->course || $quiz->course->status === 'archived'
            || ! $quiz->course->instructor?->is_active || ! $quiz->course->instructor->isInstructor()) {
            return ['Quiz harus draft dan berada pada kursus valid dengan instruktur aktif.'];
        }
        try {
            $this->validateSection($quiz->only(['course_id', 'section_id']));
            Validator::make($quiz->toArray(), [
                'title' => ['required', 'string'], 'passing_score' => ['required', 'integer', 'between:0,100'],
                'total_questions' => ['required', 'integer', 'min:1'], 'max_attempts' => ['required', 'integer', 'min:1'],
                'duration_minutes' => ['nullable', 'integer', 'min:1'],
                'assessment_type' => ['required', Rule::in([
                    Quiz::ASSESSMENT_DIAGNOSTIC,
                    Quiz::ASSESSMENT_FORMATIVE,
                    Quiz::ASSESSMENT_SUMMATIVE,
                ])],
            ])->validate();
        } catch (ValidationException $exception) {
            return ['Konfigurasi quiz atau relasi course/chapter tidak valid.'];
        }
        if ($quiz->section_id && $quiz->section?->status !== 'active') {
            return ['Chapter quiz harus aktif.'];
        }
        $questions = $quiz->questions()->with(['options', 'questionBank', 'learningConcepts'])->get();
        if ($questions->isEmpty() || $questions->count() < $quiz->total_questions) {
            return ['Quiz harus memiliki soal sesuai jumlah yang ditentukan.'];
        }
        foreach ($questions as $question) {
            if (trim(strip_tags($question->question_text)) === ''
                || $question->questionBank?->instructor_id !== $quiz->course->instructor_id
                || ! in_array($question->type, ['multiple_choice', 'true_false'], true)
                || $question->needs_review || $question->status !== 'approved'
                || $question->pivot->points < 1
                || $question->options->count() < 2
                || ($question->type === 'true_false' && $question->options->count() !== 2)
                || $question->options->contains(fn ($option): bool => trim($option->option_text) === '')
                || $question->options->where('is_correct', true)->count() !== 1
                || $question->options->map(fn ($option): string => mb_strtolower(trim($option->option_text)))->unique()->count() !== $question->options->count()) {
                return ['Periksa dan verifikasi semua soal serta kunci jawaban sebelum menerbitkan Quiz.'];
            }
        }

        if ($quiz->isDiagnostic()) {
            $unmappedCount = $questions
                ->filter(fn (Question $question): bool => $question->learningConcepts->isEmpty())
                ->count();

            $foreignConceptCount = $questions
                ->flatMap(fn (Question $question) => $question->learningConcepts)
                ->filter(fn ($concept): bool => (int) $concept->course_id !== (int) $quiz->course_id)
                ->count();

            if ($unmappedCount > 0 || $foreignConceptCount > 0) {
                return ['Diagnostic Quiz hanya dapat diterbitkan jika setiap soal memiliki Concept Mapping pada course yang sama.'];
            }
        }

        return [];
    }

    public function publishQuiz(Quiz $quiz): void
    {
        DB::transaction(function () use ($quiz): void {
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            if ($errors = $this->publicationErrors($quiz)) {
                throw ValidationException::withMessages(['quiz' => $errors]);
            }
            $quiz->update(['status' => 'published', 'published_at' => now()]);
        });
    }
}
