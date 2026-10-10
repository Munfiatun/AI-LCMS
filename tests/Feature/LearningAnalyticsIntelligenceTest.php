<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\User;
use App\Services\LearningAnalyticsInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningAnalyticsIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);
        $this->teacher = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $this->course = Course::factory()->create(['instructor_id' => $this->teacher->id, 'status' => Course::STATUS_PUBLISHED]);
    }

    public function test_item_analysis_identifies_question_with_sufficient_low_correct_rate(): void
    {
        [$quiz, $question, $option] = $this->assessment();
        $students = $this->students(4);

        foreach ($students as $index => $student) {
            $attempt = $this->attempt($quiz, $student, $index === 0 ? 100 : 0, now()->addSeconds($index));
            $this->answer($attempt, $question, $option, $index === 0);
        }

        $insights = app(LearningAnalyticsInsightService::class)->summarize($this->course, [
            'totalStudents' => 4,
            'quizParticipants' => 4,
            'totalQuizzes' => 1,
            'atRiskStudents' => 0,
            'averageProgress' => 80.0,
        ]);

        $this->assertSame('adequate', $insights['evidence']['level']);
        $this->assertSame(4, $insights['evidence']['representative_attempts']);
        $this->assertSame(25.0, $insights['evidence']['latest_attempt_pass_rate']);
        $this->assertCount(1, $insights['question_insights']);
        $this->assertCount(1, $insights['priority_questions']);
        $this->assertSame(4, $insights['priority_questions'][0]['responses']);
        $this->assertSame(25.0, $insights['priority_questions'][0]['correct_rate']);
        $this->assertSame('review', $insights['priority_questions'][0]['signal']);
        $this->assertArrayNotHasKey('correct_option', $insights['priority_questions'][0]);
        $this->assertTrue(collect($insights['learning_signals'])->contains(
            fn (array $signal): bool => str_contains($signal['title'], 'Ketuntasan assessment terbaru rendah')
        ));
        $this->assertTrue(collect($insights['learning_signals'])->contains(
            fn (array $signal): bool => str_contains($signal['title'], 'butir perlu ditinjau')
        ));
    }

    public function test_repeated_attempts_use_latest_submission_per_student_quiz_for_item_evidence(): void
    {
        [$quiz, $question, $option] = $this->assessment();
        $students = $this->students(3);

        $oldAttempt = $this->attempt($quiz, $students[0], 0, now()->subHour());
        $this->answer($oldAttempt, $question, $option, false);

        foreach ($students as $index => $student) {
            $latest = $this->attempt($quiz, $student, 100, now()->addSeconds($index));
            $this->answer($latest, $question, $option, true);
        }

        $insights = app(LearningAnalyticsInsightService::class)->summarize($this->course, [
            'totalStudents' => 3,
            'quizParticipants' => 3,
            'totalQuizzes' => 1,
            'atRiskStudents' => 0,
            'averageProgress' => 100.0,
        ]);

        $questionInsight = $insights['question_insights'][0];
        $this->assertSame(3, $questionInsight['responses']);
        $this->assertSame(3, $questionInsight['correct_count']);
        $this->assertSame(100.0, $questionInsight['correct_rate']);
        $this->assertSame('healthy', $questionInsight['signal']);
        $this->assertSame(3, $insights['evidence']['representative_attempts']);
        $this->assertSame(100.0, $insights['evidence']['latest_attempt_pass_rate']);
        $this->assertCount(0, $insights['priority_questions']);
    }

    public function test_item_with_less_than_three_responses_is_explicitly_marked_insufficient(): void
    {
        [$quiz, $question, $option] = $this->assessment();
        $students = $this->students(2);

        foreach ($students as $student) {
            $attempt = $this->attempt($quiz, $student, 0, now());
            $this->answer($attempt, $question, $option, false);
        }

        $insights = app(LearningAnalyticsInsightService::class)->summarize($this->course, [
            'totalStudents' => 2,
            'quizParticipants' => 2,
            'totalQuizzes' => 1,
            'atRiskStudents' => 0,
            'averageProgress' => 60.0,
        ]);

        $this->assertSame('limited', $insights['evidence']['level']);
        $this->assertFalse($insights['question_insights'][0]['sample_sufficient']);
        $this->assertSame('insufficient', $insights['question_insights'][0]['signal']);
        $this->assertCount(0, $insights['priority_questions']);
    }

    public function test_insight_json_endpoint_remains_private_to_course_owner(): void
    {
        $studentRole = Role::firstOrCreate(['name' => Role::ROLE_STUDENT], ['label' => 'Student']);
        $student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        CourseEnrollment::create([
            'course_id' => $this->course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        $this->actingAs($this->teacher)
            ->getJson(route('instructor.courses.analytics', $this->course).'?insights=1')
            ->assertOk()
            ->assertJsonStructure([
                'evidence' => [
                    'level',
                    'label',
                    'participant_coverage_percent',
                    'representative_attempts',
                    'question_responses',
                    'latest_attempt_pass_rate',
                    'minimum_item_responses',
                ],
                'learning_signals',
                'question_insights',
                'priority_questions',
            ]);

        $otherTeacher = User::factory()->create([
            'role_id' => $this->teacher->role_id,
            'is_active' => true,
        ]);

        $this->actingAs($otherTeacher)
            ->getJson(route('instructor.courses.analytics', $this->course).'?insights=1')
            ->assertForbidden();
    }

    public function test_frontend_contract_explains_that_learning_signals_are_not_automatic_diagnosis(): void
    {
        $script = file_get_contents(resource_path('js/analytics/learning-signals.js'));
        $app = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('Sinyal Pembelajaran Berbasis Data', $script);
        $this->assertStringContainsString('bukan diagnosis otomatis', $script);
        $this->assertStringContainsString('minimal ${data.evidence.minimum_item_responses} respons', $script);
        $this->assertStringContainsString("import { enhanceLearningAnalytics } from './analytics/learning-signals'", $app);
        $this->assertStringContainsString('enhanceLearningAnalytics();', $app);
    }

    /** @return array{Quiz, Question, QuestionOption} */
    private function assessment(): array
    {
        $quiz = Quiz::factory()->create([
            'course_id' => $this->course->id,
            'title' => 'Quiz Analitik',
            'passing_score' => 70,
            'total_questions' => 1,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $this->teacher->id,
            'course_id' => $this->course->id,
        ]);
        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Komponen manakah yang menangani logika bisnis pada aplikasi web?',
            'difficulty' => 'medium',
            'topic' => 'Backend',
        ]);
        $option = QuestionOption::factory()->create([
            'question_id' => $question->id,
            'option_text' => 'Backend',
            'is_correct' => true,
            'order' => 1,
        ]);
        $quiz->quizQuestions()->create([
            'question_id' => $question->id,
            'points' => 10,
            'order' => 1,
        ]);

        return [$quiz, $question, $option];
    }

    /** @return list<User> */
    private function students(int $count): array
    {
        $role = Role::firstOrCreate(['name' => Role::ROLE_STUDENT], ['label' => 'Student']);

        return User::factory()->count($count)->create([
            'role_id' => $role->id,
            'is_active' => true,
        ])->all();
    }

    private function attempt(Quiz $quiz, User $student, float $percentage, $submittedAt): QuizAttempt
    {
        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => $submittedAt->copy()->subMinutes(5),
            'submitted_at' => $submittedAt,
            'status' => 'submitted',
            'score' => $percentage >= 70 ? 10 : 0,
            'percentage' => $percentage,
            'correct_count' => $percentage >= 70 ? 1 : 0,
            'wrong_count' => $percentage >= 70 ? 0 : 1,
            'duration_seconds' => 300,
        ]);
    }

    private function answer(QuizAttempt $attempt, Question $question, QuestionOption $option, bool $correct): void
    {
        QuizAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'selected_option_id' => $option->id,
            'is_correct' => $correct,
            'points_earned' => $correct ? 10 : 0,
            'answered_at' => $attempt->submitted_at,
        ]);
    }
}
