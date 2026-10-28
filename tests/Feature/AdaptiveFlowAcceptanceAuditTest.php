<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\LearningConcept;
use App\Models\LearningMaterial;
use App\Models\MaterialProgress;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptQuestion;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveFlowAcceptanceAuditService;
use App\Services\Learning\Adaptive\AdaptiveRecommendationService;
use App\Services\Quiz\QuizAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdaptiveFlowAcceptanceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_remains_pending_before_a_formative_readaptation_cycle_exists(): void
    {
        [$student, $course, $bank, $concept] = $this->context();
        [$d1, , $d1Wrong] = $this->question($bank, $concept, 'Diagnostic 1');
        [$d2, , $d2Wrong] = $this->question($bank, $concept, 'Diagnostic 2');
        $diagnostic = $this->quiz($course, [$d1, $d2], Quiz::ASSESSMENT_DIAGNOSTIC, 'Diagnostic C01');

        app(QuizAttemptService::class)->submitAttempt(
            $this->attempt($diagnostic, $student, [$d1, $d2]),
            [$d1->id => $d1Wrong->id, $d2->id => $d2Wrong->id],
        );
        app(AdaptiveRecommendationService::class)->generateForCourse($student, $course);

        $result = app(AdaptiveFlowAcceptanceAuditService::class)->audit($student, $course);

        $this->assertFalse($result['summary']['passed']);
        $this->assertSame(3, $result['summary']['passed_gates']);
        $this->assertSame(6, $result['summary']['total_gates']);
        $this->assertFalse($result['gates'][3]['passed']);
        $this->assertFalse($result['gates'][4]['passed']);
        $this->assertFalse($result['gates'][5]['passed']);
    }

    public function test_audit_passes_after_material_formative_and_automatic_readaptation_complete_the_loop(): void
    {
        [$student, $course, $bank, $concept] = $this->context();
        [$d1, , $d1Wrong] = $this->question($bank, $concept, 'Diagnostic 1');
        [$d2, , $d2Wrong] = $this->question($bank, $concept, 'Diagnostic 2');
        $diagnostic = $this->quiz($course, [$d1, $d2], Quiz::ASSESSMENT_DIAGNOSTIC, 'Diagnostic C01');

        app(QuizAttemptService::class)->submitAttempt(
            $this->attempt($diagnostic, $student, [$d1, $d2]),
            [$d1->id => $d1Wrong->id, $d2->id => $d2Wrong->id],
        );
        app(AdaptiveRecommendationService::class)->generateForCourse($student, $course);

        $section = CourseSection::factory()->create([
            'course_id' => $course->id,
            'title' => 'Materi Inti',
            'order' => 1,
            'status' => 'active',
        ]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'title' => 'Remediasi C01',
            'order' => 1,
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $material->learningConcepts()->attach($concept->id, ['relevance_weight' => 1.0]);
        MaterialProgress::create([
            'student_id' => $student->id,
            'learning_material_id' => $material->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        [$f1, $f1Correct] = $this->question($bank, $concept, 'Formative 1');
        [$f2, $f2Correct] = $this->question($bank, $concept, 'Formative 2');
        $formative = $this->quiz($course, [$f1, $f2], Quiz::ASSESSMENT_FORMATIVE, 'Uji Pemahaman C01');
        app(QuizAttemptService::class)->submitAttempt(
            $this->attempt($formative, $student, [$f1, $f2]),
            [$f1->id => $f1Correct->id, $f2->id => $f2Correct->id],
        );

        $result = app(AdaptiveFlowAcceptanceAuditService::class)->audit($student, $course);

        $this->assertTrue($result['summary']['passed']);
        $this->assertSame(6, $result['summary']['passed_gates']);
        $this->assertSame(6, $result['summary']['total_gates']);
        $this->assertTrue(collect($result['gates'])->every(fn (array $gate): bool => $gate['passed']));
        $this->assertSame('C01', $result['last_intervention']['concept']->code);
        $this->assertSame('Remediasi C01', $result['last_intervention']['material']->title);
        $this->assertSame('Uji Pemahaman C01', $result['last_intervention']['formative_attempt']->quiz->title);
        $this->assertSame('formative_reassessment', $result['rows']->first()['adaptation_trigger']);

        $this->artisan('research:r4-acceptance-audit', [
            'course' => $course->id,
            'student' => $student->id,
        ])
            ->expectsOutputToContain('R4.6 Adaptive Learning Flow Acceptance Audit')
            ->expectsOutputToContain('PASS — Adaptive Learning Flow M4 siap (6/6 gate)')
            ->assertExitCode(0);
    }

    /** @return array{User, Course, QuestionBank, LearningConcept} */
    private function context(): array
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create(['name' => 'Ruby']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'PEMOGRAMAN DASAR',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
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
