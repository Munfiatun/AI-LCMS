<?php

namespace App\Services\Quiz;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptOption;
use App\Models\QuizAttemptQuestion;
use App\Models\User;
use App\Services\Learning\ConceptMasteryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class QuizAttemptService
{
    public function __construct(
        private ConceptMasteryService $conceptMasteryService
    ) {}

    public function startAttempt(Quiz $quiz, User $student): QuizAttempt
    {
        $result = DB::transaction(function () use ($quiz, $student): QuizAttempt|ValidationException {
            $quiz = Quiz::whereKey($quiz->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($student)->authorize('view', $quiz);
            // Validation rules
            if ($quiz->status !== 'published') {
                throw ValidationException::withMessages(['quiz' => 'Quiz is not published.']);
            }

            $activeAttempt = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('student_id', $student->id)
                ->where('status', 'in_progress')
                ->first();

            if ($activeAttempt) {
                if (! $activeAttempt->expires_at || now()->lt($activeAttempt->expires_at)) {
                    return $activeAttempt;
                }

                $this->submitAttempt($activeAttempt, []);
            }

            $attemptsCount = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('student_id', $student->id)
                ->count();

            if ($attemptsCount >= $quiz->max_attempts) {
                return ValidationException::withMessages(['quiz' => 'Maximum attempts reached.']);
            }

            return DB::transaction(function () use ($quiz, $student) {
                $startedAt = now();
                $expiresAt = $quiz->duration_minutes ? $startedAt->copy()->addMinutes($quiz->duration_minutes) : $startedAt->copy()->addYears(100);

                $attempt = QuizAttempt::create([
                    'quiz_id' => $quiz->id,
                    'student_id' => $student->id,
                    'started_at' => $startedAt,
                    'expires_at' => $expiresAt,
                    'status' => 'in_progress',
                ]);

                // Snapshotting questions
                $questionsQuery = $quiz->questions();
                if ($quiz->randomize_questions) {
                    $questionsQuery->inRandomOrder();
                } else {
                    $questionsQuery->orderByPivot('order');
                }

                // Limit to total_questions defined in quiz config
                $questions = $questionsQuery->take($quiz->total_questions)->get();

                $order = 1;
                foreach ($questions as $question) {
                    $attemptQuestion = QuizAttemptQuestion::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'order' => $order++,
                    ]);

                    $optionsQuery = $question->options();
                    if ($quiz->randomize_options) {
                        $optionsQuery->inRandomOrder();
                    } else {
                        $optionsQuery->orderBy('id');
                    }

                    $options = $optionsQuery->get();
                    $optionOrder = 1;
                    foreach ($options as $option) {
                        QuizAttemptOption::create([
                            'attempt_question_id' => $attemptQuestion->id,
                            'option_id' => $option->id,
                            'order' => $optionOrder++,
                        ]);
                    }
                }

                return $attempt;
            });
        });
        if ($result instanceof ValidationException) {
            throw $result;
        }

        return $result;
    }

    /**
     * @param  array<int, int|null>  $answers  Array of [question_id => selected_option_id]
     */
    public function submitAttempt(QuizAttempt $attempt, array $answers): QuizAttempt
    {
        $attempt = DB::transaction(function () use ($attempt, $answers) {
            $attempt = QuizAttempt::whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            if ($attempt->status !== 'in_progress') {
                throw ValidationException::withMessages(['attempt' => 'Attempt is already finished.']);
            }
            $now = now();

            // Check Server-Authoritative Timer
            if ($attempt->expires_at && $now->greaterThanOrEqualTo($attempt->expires_at)) {
                $attempt->status = 'expired';
            } else {
                $attempt->status = 'submitted';
            }

            $attempt->submitted_at = $now;
            $attempt->duration_seconds = (int) $attempt->started_at->diffInSeconds($now);

            $score = 0;
            $correctCount = 0;
            $wrongCount = 0;

            $maxPossibleScore = 0;

            $attemptQuestions = $attempt->attemptQuestions()->with('question.options')->get();

            foreach ($answers as $questionId => $selectedOptionId) {
                $attemptQuestion = $attemptQuestions->firstWhere('question_id', $questionId);
                if (! $attemptQuestion || ($selectedOptionId !== null && ! $attemptQuestion->question->options->contains('id', $selectedOptionId))) {
                    throw ValidationException::withMessages(['answers' => 'Pilihan jawaban tidak sesuai dengan soal pada ujian ini.']);
                }
            }

            foreach ($attemptQuestions as $attemptQuestion) {
                $question = $attemptQuestion->question;

                // Determine points for this question from quiz_questions table
                $quizQuestion = $attempt->quiz->quizQuestions()->where('question_id', $question->id)->first();
                $points = $quizQuestion ? $quizQuestion->points : 10;

                $maxPossibleScore += $points;

                $selectedOptionId = $answers[$question->id] ?? null;
                $isCorrect = false;
                $pointsEarned = 0;

                if ($selectedOptionId) {
                    $selectedOption = $question->options->where('id', $selectedOptionId)->first();
                    if ($selectedOption && $selectedOption->is_correct) {
                        $isCorrect = true;
                        $pointsEarned = $points;
                        $score += $points;
                        $correctCount++;
                    } else {
                        $wrongCount++;
                    }
                } else {
                    $wrongCount++;
                }

                QuizAnswer::updateOrCreate(
                    [
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                    ],
                    [
                        'selected_option_id' => $selectedOptionId,
                        'is_correct' => $isCorrect,
                        'points_earned' => $pointsEarned,
                        'answered_at' => $now,
                    ]
                );
            }

            $percentage = $maxPossibleScore > 0 ? ($score / $maxPossibleScore) * 100 : 0;

            $attempt->score = $score;
            $attempt->percentage = $percentage;
            $attempt->correct_count = $correctCount;
            $attempt->wrong_count = $wrongCount;

            $attempt->save();

            return $attempt->fresh(['quiz.course', 'answers.question.learningConcepts']);
        });

        if ($attempt->quiz?->contributesToLearnerMastery()) {
            $this->conceptMasteryService->updateFromAttempt($attempt);
        }

        return $attempt->fresh();
    }
}
