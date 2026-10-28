<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Quiz\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QuizDraftRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_quiz_with_only_unfinished_attempt_can_return_to_draft(): void
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'status' => 'in_progress',
        ]);

        app(QuizService::class)->unpublishQuiz($quiz);

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $attempt->id]);
    }

    public function test_published_quiz_with_completed_attempt_remains_locked(): void
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'started_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
            'submitted_at' => now(),
            'status' => 'submitted',
            'score' => 10,
            'percentage' => 100,
            'correct_count' => 1,
            'wrong_count' => 0,
            'duration_seconds' => 60,
        ]);

        try {
            app(QuizService::class)->unpublishQuiz($quiz);
            $this->fail('Expected completed quiz attempt to keep the quiz locked.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('sudah memiliki attempt yang selesai', $exception->getMessage());
        }

        $this->assertDatabaseHas('quizzes', [
            'id' => $quiz->id,
            'status' => 'published',
        ]);
        $this->assertDatabaseCount('quiz_attempts', 1);
    }
}
