<?php

namespace Tests\Feature;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptQuestion;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveRecommendationService;
use App\Services\Quiz\QuizAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomaticReadaptationTest extends TestCase
{
    use RefreshDatabase;

    public function test_formative_mastery_update_supersedes_old_recommendation_and_creates_new_active_decision(): void
    {
        [$student, $course, $bank, $concept] = $this->context();
        [$d1, , $d1Wrong] = $this->question($bank, $concept, 'Diagnostic 1');
        [$d2, , $d2Wrong] = $this->question($bank, $concept, 'Diagnostic 2');

        $diagnostic = $this->quiz($course, [$d1, $d2], Quiz::ASSESSMENT_DIAGNOSTIC, 'Diagnostic C01');
        app(QuizAttemptService::class)->submitAttempt(
            $this->attempt($diagnostic, $student, [$d1, $d2]),
            [
                $d1->id => $d1Wrong->id,
                $d2->id => $d2Wrong->id,
            ],
        );

        $baselineMastery = LearnerMastery::where('student_id', $student->id)
            ->where('concept_id', $concept->id)
            ->firstOrFail();
        $this->assertSame(0.0, $baselineMastery->mastery_score);
        $this->assertSame(2, $baselineMastery->evidence_count);

        $initial = app(AdaptiveRecommendationService::class)
            ->generateForCourse($student, $course)
            ->sole();

        $this->assertSame('remediation', $initial->action);
        $this->assertSame(AdaptiveRecommendation::STATUS_ACTIVE, $initial->status);

        [$f1, $f1Correct] = $this->question($bank, $concept, 'Formative 1');
        [$f2, $f2Correct] = $this->question($bank, $concept, 'Formative 2');
        $formative = $this->quiz($course, [$f1, $f2], Quiz::ASSESSMENT_FORMATIVE, 'Uji Pemahaman C01');
        $formativeAttempt = $this->attempt($formative, $student, [$f1, $f2]);

        app(QuizAttemptService::class)->submitAttempt($formativeAttempt, [
            $f1->id => $f1Correct->id,
            $f2->id => $f2Correct->id,
        ]);

        $mastery = LearnerMastery::where('student_id', $student->id)
            ->where('concept_id', $concept->id)
            ->firstOrFail();
        $this->assertSame(50.0, $mastery->mastery_score);
        $this->assertSame(4, $mastery->evidence_count);

        $old = $initial->fresh();
        $this->assertSame(AdaptiveRecommendation::STATUS_SUPERSEDED, $old->status);
        $this->assertNotNull($old->superseded_at);

        $active = AdaptiveRecommendation::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('source_concept_id', $concept->id)
            ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
            ->sole();

        $this->assertNotSame($initial->id, $active->id);
        $this->assertSame('reinforcement', $active->action);
        $this->assertSame('mastery_medium', $active->reason_code);
        $this->assertSame('formative_reassessment', $active->metadata['adaptation_context']['trigger']);
        $this->assertSame($formative->id, $active->metadata['adaptation_context']['quiz_id']);
        $this->assertSame($formativeAttempt->id, $active->metadata['adaptation_context']['attempt_id']);
        $this->assertSame([$concept->id], $active->metadata['adaptation_context']['updated_concept_ids']);
        $this->assertSame('diagnostic-formative-weighted-v2', $active->metadata['adaptation_context']['mastery_model_version']);
    }

    public function test_diagnostic_update_does_not_trigger_readaptation_cycle_by_itself(): void
    {
        [$student, $course, $bank, $concept] = $this->context();
        [$question, $correct] = $this->question($bank, $concept, 'Diagnostic');
        $diagnostic = $this->quiz($course, [$question], Quiz::ASSESSMENT_DIAGNOSTIC, 'Diagnostic');

        app(QuizAttemptService::class)->submitAttempt(
            $this->attempt($diagnostic, $student, [$question]),
            [$question->id => $correct->id],
        );

        $this->assertDatabaseCount('learner_mastery', 1);
        $this->assertDatabaseCount('adaptive_recommendations', 0);
    }

    /** @return array{User, Course, QuestionBank, LearningConcept} */
    private function context(): array
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C01',
            'name' => 'Variabel dan Tipe Data',
            'order' => 1,
            'is_active' => true,
        ]);

        return [$student, $course, $bank, $concept];
    }

    /** @return array{Question, QuestionOption, QuestionOption} */
    private function question(QuestionBank $bank, LearningConcept $concept, string $text): array
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
        $question->learningConcepts()->attach($concept->id, [
            'weight' => 1.0,
            'difficulty_level' => Question::DIFFICULTY_MEDIUM,
        ]);

        $correct = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Benar',
            'is_correct' => true,
            'order' => 1,
        ]);
        $wrong = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Salah',
            'is_correct' => false,
            'order' => 2,
        ]);

        return [$question, $correct, $wrong];
    }

    /** @param list<Question> $questions */
    private function quiz(Course $course, array $questions, string $assessmentType, string $title): Quiz
    {
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'title' => $title,
            'assessment_type' => $assessmentType,
            'status' => 'published',
            'published_at' => now(),
            'total_questions' => count($questions),
            'max_attempts' => 3,
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
    private function attempt(Quiz $quiz, User $student, array $questions): QuizAttempt
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
