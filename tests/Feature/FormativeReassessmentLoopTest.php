<?php

namespace Tests\Feature;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\LearningMaterial;
use App\Models\MasteryHistory;
use App\Models\MaterialProgress;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptQuestion;
use App\Models\QuizQuestion;
use App\Models\Role;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use App\Services\Learning\Adaptive\AdaptiveLearningResourceResolver;
use App\Services\Quiz\QuizAttemptService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormativeReassessmentLoopTest extends TestCase
{
    use RefreshDatabase;

    public function test_formative_reassessment_updates_existing_diagnostic_mastery_cumulatively(): void
    {
        [$student, $course, $bank] = $this->context();
        $concept = $this->concept($course);
        [$diagnosticQuestion, , $diagnosticWrong] = $this->question($bank, $concept, 'Diagnostic C01');
        [$formativeQuestion, $formativeCorrect] = $this->question($bank, $concept, 'Formative C01');

        $diagnostic = $this->quiz($course, [$diagnosticQuestion], Quiz::ASSESSMENT_DIAGNOSTIC, 'Diagnostic C01');
        $diagnosticAttempt = $this->attempt($diagnostic, $student, [$diagnosticQuestion]);
        app(QuizAttemptService::class)->submitAttempt($diagnosticAttempt, [
            $diagnosticQuestion->id => $diagnosticWrong->id,
        ]);

        $baseline = LearnerMastery::where('student_id', $student->id)
            ->where('concept_id', $concept->id)
            ->firstOrFail();
        $this->assertSame(0.0, $baseline->mastery_score);
        $this->assertSame(1, $baseline->evidence_count);

        $formative = $this->quiz($course, [$formativeQuestion], Quiz::ASSESSMENT_FORMATIVE, 'Uji Pemahaman C01');
        $formativeAttempt = $this->attempt($formative, $student, [$formativeQuestion]);
        app(QuizAttemptService::class)->submitAttempt($formativeAttempt, [
            $formativeQuestion->id => $formativeCorrect->id,
        ]);

        $mastery = LearnerMastery::where('student_id', $student->id)
            ->where('concept_id', $concept->id)
            ->firstOrFail();

        $this->assertSame(50.0, $mastery->mastery_score);
        $this->assertSame(2, $mastery->evidence_count);

        $history = MasteryHistory::where('student_id', $student->id)
            ->where('concept_id', $concept->id)
            ->where('evidence_type', 'formative_quiz_attempt')
            ->firstOrFail();

        $this->assertSame(0.0, $history->before_score);
        $this->assertSame(50.0, $history->after_score);
        $this->assertSame('diagnostic-formative-weighted-v2', $history->model_version);
        $this->assertSame('formative', $history->evidence_payload['assessment_type']);
        $this->assertSame(1, $history->evidence_payload['assessment_evidence_counts']['diagnostic']);
        $this->assertSame(1, $history->evidence_payload['assessment_evidence_counts']['formative']);
    }

    public function test_summative_attempt_does_not_change_learner_mastery(): void
    {
        [$student, $course, $bank] = $this->context();
        $concept = $this->concept($course);
        [$diagnosticQuestion, $diagnosticCorrect] = $this->question($bank, $concept, 'Diagnostic C01');
        [$summativeQuestion, , $summativeWrong] = $this->question($bank, $concept, 'Summative C01');

        $diagnostic = $this->quiz($course, [$diagnosticQuestion], Quiz::ASSESSMENT_DIAGNOSTIC, 'Diagnostic');
        app(QuizAttemptService::class)->submitAttempt(
            $this->attempt($diagnostic, $student, [$diagnosticQuestion]),
            [$diagnosticQuestion->id => $diagnosticCorrect->id],
        );

        $before = LearnerMastery::where('student_id', $student->id)->where('concept_id', $concept->id)->firstOrFail();
        $historyCount = MasteryHistory::count();

        $summative = $this->quiz($course, [$summativeQuestion], Quiz::ASSESSMENT_SUMMATIVE, 'Summative');
        app(QuizAttemptService::class)->submitAttempt(
            $this->attempt($summative, $student, [$summativeQuestion]),
            [$summativeQuestion->id => $summativeWrong->id],
        );

        $after = LearnerMastery::where('student_id', $student->id)->where('concept_id', $concept->id)->firstOrFail();
        $this->assertSame($before->mastery_score, $after->mastery_score);
        $this->assertSame($before->evidence_count, $after->evidence_count);
        $this->assertSame($historyCount, MasteryHistory::count());
    }

    public function test_resolver_prefers_published_formative_quiz_with_highest_target_concept_coverage(): void
    {
        [, $course, $bank] = $this->context();
        $concept = $this->concept($course);
        [$q1] = $this->question($bank, $concept, 'C01-1');
        [$q2] = $this->question($bank, $concept, 'C01-2');

        $lowCoverage = $this->quiz($course, [$q1], Quiz::ASSESSMENT_FORMATIVE, 'Formative Ringkas');
        $highCoverage = $this->quiz($course, [$q1, $q2], Quiz::ASSESSMENT_FORMATIVE, 'Formative Lengkap');
        $this->quiz($course, [$q1, $q2], Quiz::ASSESSMENT_DIAGNOSTIC, 'Diagnostic Bukan Reassessment');
        $draft = $this->quiz($course, [$q1, $q2], Quiz::ASSESSMENT_FORMATIVE, 'Draft Formative');
        $draft->update(['status' => 'draft', 'published_at' => null]);

        $recommendation = new AdaptiveRecommendation([
            'course_id' => $course->id,
            'target_concept_id' => $concept->id,
            'action' => 'remediation',
        ]);

        $resolved = app(AdaptiveLearningResourceResolver::class)->formativeQuizFor($recommendation);

        $this->assertNotNull($resolved);
        $this->assertSame($highCoverage->id, $resolved->id);
        $this->assertNotSame($lowCoverage->id, $resolved->id);
        $this->assertSame(2, $resolved->target_concept_question_count);
    }

    public function test_workspace_unlocks_formative_reassessment_after_recommended_material_is_completed(): void
    {
        $this->seed(RoleSeeder::class);
        $instructor = User::factory()->create([
            'role_id' => Role::where('name', Role::ROLE_INSTRUCTOR)->value('id'),
            'is_active' => true,
        ]);
        $student = User::factory()->create([
            'role_id' => Role::where('name', Role::ROLE_STUDENT)->value('id'),
            'is_active' => true,
            'name' => 'Ruby',
        ]);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => Course::STATUS_PUBLISHED,
        ]);
        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        $concept = $this->concept($course);
        $section = CourseSection::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
            'order' => 1,
        ]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'title' => 'Materi C01',
            'status' => LearningMaterial::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $material->learningConcepts()->attach($concept->id, ['relevance_weight' => 1.0]);

        $bank = QuestionBank::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        [$question] = $this->question($bank, $concept, 'Formative C01');
        $formative = $this->quiz($course, [$question], Quiz::ASSESSMENT_FORMATIVE, 'Uji Pemahaman C01');

        $baseline = app(AdaptiveAlgorithmRegistry::class)->baseline();
        AdaptiveRecommendation::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'source_concept_id' => $concept->id,
            'target_concept_id' => $concept->id,
            'algorithm_version_id' => $baseline->id,
            'action' => 'remediation',
            'reason_code' => 'mastery_low',
            'decision_fingerprint' => str_repeat('f', 64),
            'status' => AdaptiveRecommendation::STATUS_ACTIVE,
            'inputs' => ['mastery_score' => 30, 'evidence_count' => 3],
            'metadata' => [],
            'generated_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.adaptive-recommendations.index'))
            ->assertOk()
            ->assertSee('Uji Pemahaman C01')
            ->assertSee('Belum aktif')
            ->assertDontSee('>Uji Pemahaman<', false);

        MaterialProgress::create([
            'student_id' => $student->id,
            'learning_material_id' => $material->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.adaptive-recommendations.index'))
            ->assertOk()
            ->assertSee('>Uji Pemahaman<', false)
            ->assertSee(route('student.quizzes.show', $formative), false);
    }

    /** @return array{User, Course, QuestionBank} */
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

        return [$student, $course, $bank];
    }

    private function concept(Course $course): LearningConcept
    {
        return LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C01',
            'name' => 'Variabel dan Tipe Data',
            'order' => 1,
            'is_active' => true,
        ]);
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
