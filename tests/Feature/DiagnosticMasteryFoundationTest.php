<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\MasteryHistory;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptQuestion;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Quiz\QuizAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticMasteryFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostic_submission_creates_explainable_mastery_per_concept(): void
    {
        [$student, $course, $bank] = $this->researchContext();

        $conceptA = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C01',
            'name' => 'Variabel dan Tipe Data',
        ]);
        $conceptB = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C02',
            'name' => 'Operator dan Ekspresi',
        ]);

        [$questionA, $correctA] = $this->questionWithOptions($bank, 'Soal C01');
        [$questionB, $correctB, $wrongB] = $this->questionWithOptions($bank, 'Soal C02');

        $questionA->learningConcepts()->attach($conceptA->id, [
            'weight' => 1.00,
            'difficulty_level' => Question::DIFFICULTY_MEDIUM,
        ]);
        $questionB->learningConcepts()->attach($conceptB->id, [
            'weight' => 1.00,
            'difficulty_level' => Question::DIFFICULTY_MEDIUM,
        ]);

        $quiz = $this->quizWithQuestions($course, [$questionA, $questionB], Quiz::ASSESSMENT_DIAGNOSTIC);
        $attempt = $this->attemptFor($quiz, $student, [$questionA, $questionB]);

        app(QuizAttemptService::class)->submitAttempt($attempt, [
            $questionA->id => $correctA->id,
            $questionB->id => $wrongB->id,
        ]);

        $masteryA = LearnerMastery::where('student_id', $student->id)->where('concept_id', $conceptA->id)->firstOrFail();
        $masteryB = LearnerMastery::where('student_id', $student->id)->where('concept_id', $conceptB->id)->firstOrFail();

        $this->assertSame(100.0, $masteryA->mastery_score);
        $this->assertSame(0.0, $masteryB->mastery_score);
        $this->assertSame(1, $masteryA->evidence_count);
        $this->assertSame(1, $masteryB->evidence_count);
        $this->assertSame('observed', $masteryA->status);
        $this->assertSame('observed', $masteryB->status);

        $this->assertDatabaseCount('mastery_history', 2);
        $history = MasteryHistory::where('student_id', $student->id)
            ->where('concept_id', $conceptA->id)
            ->firstOrFail();

        $this->assertSame('diagnostic_quiz_attempt', $history->evidence_type);
        $this->assertSame($attempt->id, $history->evidence_id);
        $this->assertSame('diagnostic-formative-weighted-v2', $history->model_version);
        $this->assertSame('weighted_correctness', $history->evidence_payload['calculation']);
        $this->assertSame($quiz->id, $history->evidence_payload['quiz_id']);

        $this->assertNotSame($correctB->id, $wrongB->id);
    }

    public function test_formative_submission_without_diagnostic_baseline_does_not_create_learner_mastery(): void
    {
        [$student, $course, $bank] = $this->researchContext();
        $concept = LearningConcept::factory()->create(['course_id' => $course->id, 'code' => 'C01']);
        [$question, $correct] = $this->questionWithOptions($bank, 'Soal formatif');

        $question->learningConcepts()->attach($concept->id, [
            'weight' => 1.00,
            'difficulty_level' => Question::DIFFICULTY_MEDIUM,
        ]);

        $quiz = $this->quizWithQuestions($course, [$question], Quiz::ASSESSMENT_FORMATIVE);
        $attempt = $this->attemptFor($quiz, $student, [$question]);

        app(QuizAttemptService::class)->submitAttempt($attempt, [
            $question->id => $correct->id,
        ]);

        $this->assertDatabaseCount('learner_mastery', 0);
        $this->assertDatabaseCount('mastery_history', 0);
    }

    /** @return array{User, Course, QuestionBank} */
    private function researchContext(): array
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);

        return [$student, $course, $bank];
    }

    /** @return array{Question, QuestionOption, QuestionOption} */
    private function questionWithOptions(QuestionBank $bank, string $text): array
    {
        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => $text,
            'difficulty' => Question::DIFFICULTY_MEDIUM,
            'points' => 10,
            'status' => Question::STATUS_APPROVED,
            'answer_source' => Question::SOURCE_EXPLICIT,
            'needs_review' => false,
        ]);

        $correct = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Jawaban benar',
            'is_correct' => true,
            'order' => 1,
        ]);
        $wrong = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Jawaban salah',
            'is_correct' => false,
            'order' => 2,
        ]);

        return [$question, $correct, $wrong];
    }

    /** @param list<Question> $questions */
    private function quizWithQuestions(Course $course, array $questions, string $assessmentType): Quiz
    {
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'assessment_type' => $assessmentType,
            'status' => 'published',
            'total_questions' => count($questions),
            'max_attempts' => 2,
            'randomize_questions' => false,
            'randomize_options' => false,
        ]);

        foreach ($questions as $index => $question) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'question_id' => $question->id,
                'order' => $index + 1,
                'points' => 10,
            ]);
        }

        return $quiz;
    }

    /** @param list<Question> $questions */
    private function attemptFor(Quiz $quiz, User $student, array $questions): QuizAttempt
    {
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
            'status' => 'in_progress',
        ]);

        foreach ($questions as $index => $question) {
            QuizAttemptQuestion::create([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'order' => $index + 1,
            ]);
        }

        return $attempt;
    }
}
