<?php

namespace Tests\Feature;

use App\Models\AIProcessingLog;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\MaterialDocument;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionDocument;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\AI\AIContentService;
use App\Services\AI\AISlidebookService;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\Document\DocumentService;
use App\Services\EnrollmentService;
use App\Services\Quiz\QuizAttemptService;
use App\Services\Quiz\QuizService;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PhaseZeroSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('registrationRoles')]
    public function test_registration_always_creates_student(?string $role): void
    {
        $this->seed(RoleSeeder::class);
        $password = Str::random(24);
        $payload = [
            'name' => 'Student', 'email' => 'new@example.test',
            'password' => $password, 'password_confirmation' => $password,
            'role_id' => Role::where('name', 'admin')->sole()->id,
            'admin_code' => Str::random(24), 'is_active' => false,
        ];
        if ($role !== null) {
            $payload['role'] = $role;
        }

        $this->post(route('register'), $payload)->assertRedirect(route('student.dashboard'));

        $user = User::where('email', 'new@example.test')->sole();
        $this->assertTrue($user->isStudent());
        $this->assertTrue($user->is_active);
        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.dashboard'))->assertRedirect(route('student.dashboard'));
        $this->get(route('instructor.dashboard'))->assertRedirect(route('student.dashboard'));
    }

    public static function registrationRoles(): array
    {
        return ['no role' => [null], 'student' => ['student'], 'admin injection' => ['admin'], 'instructor injection' => ['instructor']];
    }

    public function test_public_admin_registration_is_unavailable(): void
    {
        $this->get(route('register'))->assertOk()->assertDontSee('name="role"', false)->assertDontSee('admin_code');
        $this->post('/admin/register')->assertNotFound();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_admin_can_activate_existing_student_as_instructor(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = $this->user('admin');
        $student = $this->user('student');
        $student->update(['is_active' => false]);

        $this->actingAs($admin)->post(route('admin.users.activate-instructor', $student))->assertRedirect();

        $this->assertTrue($student->fresh()->isInstructor());
        $this->assertTrue($student->fresh()->is_active);
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_non_admin_cannot_activate_instructor(string $role): void
    {
        $actor = $this->user($role);
        $student = $this->user('student');

        $this->actingAs($actor)->post(route('admin.users.activate-instructor', $student))->assertRedirect();

        $this->assertTrue($student->fresh()->isStudent());
    }

    public static function unprivilegedRoles(): array
    {
        return [['student'], ['instructor']];
    }

    public function test_demo_seeder_is_opt_in_and_does_not_overwrite_existing_users(): void
    {
        $this->seed(RoleSeeder::class);
        config(['auth.demo_user_password' => null]);
        $this->seed(UserSeeder::class);
        $this->assertDatabaseCount('users', 0);

        $student = $this->user('student');
        $student->update(['email' => 'admin@example.com']);
        $originalPassword = $student->password;
        config(['auth.demo_user_password' => Str::random(24)]);
        $this->seed(UserSeeder::class);

        $this->assertTrue($student->fresh()->isStudent());
        $this->assertSame($originalPassword, $student->fresh()->password);
    }

    public function test_demo_seeder_does_not_create_users_in_production(): void
    {
        config(['auth.demo_user_password' => Str::random(24)]);
        $this->app->instance('env', 'production');

        (new UserSeeder)->run();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_enrolled_student_can_open_published_slidebook_and_document(): void
    {
        [$course, $material, $book, $document] = $this->content();
        $student = $this->user('student');
        $this->enroll($course, $student);

        $this->actingAs($student)->get(route('student.slidebooks.show', $book))->assertOk()->assertSee($book->title);
        $this->get(route('documents.download', $document))->assertOk()->assertDownload('lesson.pdf');
    }

    #[DataProvider('deniedContentStates')]
    public function test_student_content_access_returns_403_without_valid_active_enrollment(string $state): void
    {
        [$course, $material, $book, $document] = $this->content();
        $student = $this->user('student');
        if ($state === 'other course') {
            $otherCourse = Course::factory()->create(['instructor_id' => $course->instructor_id]);
            $this->enroll($otherCourse, $student);
        } elseif ($state !== 'no enrollment') {
            $enrollment = $this->enroll($course, $student);
            if (in_array($state, ['dropped', 'completed'], true)) {
                $enrollment->update(['status' => $state]);
            } elseif ($state === 'draft course') {
                $course->update(['status' => 'draft']);
            } elseif ($state === 'draft material') {
                $material->update(['status' => 'draft']);
            } elseif ($state === 'deleted material') {
                $material->delete();
            } elseif ($state === 'deleted course') {
                $course->delete();
            } elseif ($state === 'inactive user') {
                $student->update(['is_active' => false]);
            }
        }

        if ($state !== 'inactive user') {
            $this->actingAs($student)->get(route('student.slidebooks.show', $book))->assertForbidden();
        }
        $this->actingAs($student)->get(route('documents.download', $document))->assertForbidden();
    }

    public static function deniedContentStates(): array
    {
        return array_combine(
            $states = ['no enrollment', 'other course', 'dropped', 'completed', 'draft course', 'draft material', 'deleted material', 'deleted course', 'inactive user'],
            array_map(fn (string $state): array => [$state], $states)
        );
    }

    public function test_enrollment_does_not_allow_unpublished_slidebook(): void
    {
        [$course, $material, $book] = $this->content();
        $student = $this->user('student');
        $this->enroll($course, $student);
        $book->update(['status' => 'draft']);

        $this->actingAs($student)->get(route('student.slidebooks.show', $book))->assertForbidden();
    }

    public function test_other_instructor_cannot_view_or_modify_published_slidebook(): void
    {
        [$course, $material, $book, $document] = $this->content();
        $otherInstructor = $this->user('instructor');

        $this->actingAs($otherInstructor)->get(route('instructor.slidebooks.preview', $book))->assertForbidden();
        $this->post(route('instructor.slidebooks.slides.store', $book), ['title' => 'Unauthorized'])->assertForbidden();
        $this->post(route('instructor.slidebooks.publish', $book))->assertForbidden();
        $this->get(route('documents.download', $document))->assertForbidden();

        $this->assertDatabaseCount('slides', 0);
        $this->assertTrue(Gate::forUser($course->instructor)->allows('view', $book));
        $this->assertTrue(Gate::forUser($this->user('admin'))->allows('view', $book));
    }

    public function test_foreign_option_is_rejected_with_422_without_saving_answers(): void
    {
        [$student, $quiz, $attempt, $question, $option] = $this->attempt();
        $foreignQuestion = Question::factory()->create(['question_bank_id' => $question->question_bank_id]);
        $foreignOption = QuestionOption::factory()->create(['question_id' => $foreignQuestion->id]);

        $this->actingAs($student)->postJson(route('student.quizzes.submit', [$quiz, $attempt]), [
            'answers' => [$question->id => $foreignOption->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('answers');

        $this->assertDatabaseCount('quiz_answers', 0);
        $this->assertSame('in_progress', $attempt->fresh()->status);
    }

    public function test_foreign_question_is_rejected_with_422(): void
    {
        [$student, $quiz, $attempt, $question, $option] = $this->attempt();
        $foreignQuestion = Question::factory()->create(['question_bank_id' => $question->question_bank_id]);

        $this->actingAs($student)->postJson(route('student.quizzes.submit', [$quiz, $attempt]), [
            'answers' => [$foreignQuestion->id => $option->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('answers');

        $this->assertDatabaseCount('quiz_answers', 0);
    }

    #[DataProvider('submissionTimes')]
    public function test_only_on_time_submitted_attempts_pass_and_complete_course(int $seconds, string $status, bool $passed): void
    {
        $this->freezeTime();
        [$student, $quiz, $attempt, $question, $option] = $this->attempt();
        $this->travel($seconds)->seconds();

        $this->actingAs($student)->post(route('student.quizzes.submit', [$quiz, $attempt]), [
            'answers' => [$question->id => $option->id],
        ])->assertRedirect(route('student.quizzes.result', [$quiz, $attempt]));

        $attempt->refresh();
        $this->assertSame($status, $attempt->status);
        $this->assertSame(100.0, $attempt->percentage);
        $this->assertSame($passed, $attempt->isPassed());
        $this->assertSame($seconds, $attempt->duration_seconds);
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $quiz->course_id, 'student_id' => $student->id,
            'progress_percentage' => $passed ? 100 : 0, 'status' => $passed ? 'completed' : 'active',
        ]);
        $result = $this->get(route('student.quizzes.result', [$quiz, $attempt]))->assertOk();
        if ($passed) {
            $result->assertSee('SELAMAT, ANDA LULUS!');
        } else {
            $result->assertDontSee('SELAMAT, ANDA LULUS!')->assertSee('Waktu ujian telah habis');
        }
    }

    public static function submissionTimes(): array
    {
        return ['before deadline' => [59, 'submitted', true], 'exact deadline' => [60, 'expired', false], 'after deadline' => [61, 'expired', false]];
    }

    public function test_expired_attempt_is_finalized_on_reopening(): void
    {
        $this->freezeTime();
        [$student, $quiz, $attempt] = $this->attempt();
        $this->travel(61)->seconds();

        $this->actingAs($student)->get(route('student.quizzes.take', [$quiz, $attempt]))
            ->assertRedirect(route('student.quizzes.result', [$quiz, $attempt]));

        $this->assertSame('expired', $attempt->fresh()->status);
        $this->assertFalse($attempt->fresh()->isPassed());
    }

    public function test_historical_expired_and_in_progress_scores_do_not_complete_course(): void
    {
        [$student, $quiz, $attempt] = $this->attempt();
        $attempt->update(['percentage' => 100]);
        app(EnrollmentService::class)->updateProgress($quiz->course, $student);
        $this->assertDatabaseHas('course_enrollments', ['student_id' => $student->id, 'progress_percentage' => 0]);
        $attempt->update(['status' => 'expired']);
        $quiz->course->enrollments()->update(['status' => 'completed', 'progress_percentage' => 100, 'completed_at' => now()]);

        app(EnrollmentService::class)->updateProgress($quiz->course, $student);

        $this->assertDatabaseHas('course_enrollments', ['student_id' => $student->id, 'status' => 'active', 'progress_percentage' => 0, 'completed_at' => null]);
        $this->assertSame(100.0, $attempt->fresh()->percentage);
        $this->assertSame('expired', $attempt->fresh()->status);
    }

    public function test_quiz_creation_rejects_section_from_other_course_with_422(): void
    {
        $instructor = $this->user('instructor');
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $otherCourse = Course::factory()->create(['instructor_id' => $instructor->id]);
        $section = CourseSection::factory()->create(['course_id' => $otherCourse->id]);

        $this->actingAs($instructor)->postJson(route('instructor.quizzes.store'), [
            'course_id' => $course->id, 'section_id' => $section->id, 'title' => 'Quiz',
            'passing_score' => 70, 'total_questions' => 1, 'max_attempts' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('section_id');

        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_quiz_update_rejects_course_change_with_old_section(): void
    {
        $instructor = $this->user('instructor');
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $quiz = Quiz::factory()->create(['course_id' => $course->id, 'section_id' => $section->id]);
        $otherCourse = Course::factory()->create(['instructor_id' => $instructor->id]);

        try {
            app(QuizService::class)->updateQuiz($quiz, ['course_id' => $otherCourse->id]);
            $this->fail('A mismatched section must not be saved.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('section_id', $exception->errors());
        }

        $this->assertSame($course->id, $quiz->fresh()->course_id);
    }

    public function test_quiz_creation_accepts_section_of_same_course(): void
    {
        $instructor = $this->user('instructor');
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);

        $this->actingAs($instructor)->post(route('instructor.quizzes.store'), [
            'course_id' => $course->id, 'section_id' => $section->id, 'title' => 'Quiz',
            'passing_score' => 70, 'total_questions' => 1, 'max_attempts' => 1,
        ])->assertRedirect(route('instructor.quizzes.index'));

        $this->assertDatabaseHas('quizzes', ['course_id' => $course->id, 'section_id' => $section->id]);
    }

    public function test_ai_slidebook_error_does_not_expose_or_log_exception_message(): void
    {
        [$course, $material] = $this->content();
        $sensitiveMessage = 'provider credential '.Str::random(32);
        $this->mock(AISlidebookService::class)->shouldReceive('generateSlidebookForMaterial')->once()->andThrow(new RuntimeException($sensitiveMessage));
        Log::spy();

        $this->actingAs($course->instructor)->post(route('instructor.materials.ai.slidebook', $material))
            ->assertSessionHas('error', 'Proses AI gagal. Silakan coba kembali.');

        Log::shouldHaveReceived('error')->once()->with('AI slidebook processing failed', [
            'exception_type' => RuntimeException::class, 'material_id' => $material->id,
        ]);
    }

    public function test_instructor_results_distinguish_expired_from_submitted_scores(): void
    {
        [$student, $quiz, $attempt] = $this->attempt();
        $attempt->update(['status' => 'expired', 'percentage' => 100]);
        $quiz->attempts()->create([
            'student_id' => $student->id, 'status' => 'submitted', 'percentage' => 100,
            'started_at' => now(), 'expires_at' => now()->addMinute(),
        ]);

        $this->actingAs($quiz->course->instructor)->get(route('instructor.quizzes.results', $quiz))
            ->assertOk()->assertSee('Expired')->assertSee('GAGAL')->assertSee('LULUS');
    }

    public function test_expired_quiz_list_shows_expired_status_and_result_link(): void
    {
        [$student, $quiz, $attempt] = $this->attempt();
        $attempt->update(['status' => 'expired', 'percentage' => 100]);

        $this->actingAs($student)->get(route('student.quizzes.index'))
            ->assertOk()->assertSee('Waktu Habis')->assertSee(route('student.quizzes.result', [$quiz, $attempt]));
    }

    public function test_start_does_not_resume_expired_attempt_or_bypass_attempt_limit(): void
    {
        $this->freezeTime();
        [$student, $quiz, $attempt] = $this->attempt();
        $this->travel(61)->seconds();

        $this->actingAs($student)->postJson(route('student.quizzes.start', $quiz))
            ->assertUnprocessable()->assertJsonValidationErrors('quiz');

        $this->assertSame('expired', $attempt->fresh()->status);
        $this->assertSame(1, $quiz->attempts()->count());
    }

    public function test_question_extraction_returns_safe_error_and_logs_only_safe_context(): void
    {
        $instructor = $this->user('instructor');
        $bank = QuestionBank::factory()->create(['instructor_id' => $instructor->id]);
        $document = QuestionDocument::create([
            'question_bank_id' => $bank->id, 'original_name' => 'questions.docx',
            'stored_name' => 'questions.docx', 'path' => 'questions.docx', 'disk' => 'private',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'extension' => 'docx', 'size' => 100, 'uploaded_by' => $instructor->id,
        ]);
        $this->mock(DocumentService::class)->shouldReceive('extractTextFromQuestionDocument')
            ->once()->andThrow(new RuntimeException('sensitive provider detail '.Str::random(24)));
        Log::spy();

        $this->actingAs($instructor)->post(route('instructor.question-banks.extract', $bank), ['document_id' => $document->id])
            ->assertSessionHas('error', 'Proses AI gagal. Silakan coba kembali.');

        Log::shouldHaveReceived('error')->once()->with('AI question extraction failed', [
            'exception_type' => RuntimeException::class, 'document_id' => $document->id,
        ]);
    }

    public function test_ai_provider_failure_does_not_persist_or_rethrow_sensitive_message(): void
    {
        [$course, $material] = $this->content();
        $provider = \Mockery::mock(AIProviderInterface::class);
        $provider->shouldReceive('getProviderName')->andReturn('mock');
        $provider->shouldReceive('getModelName')->andReturn('mock');
        $provider->shouldReceive('generateStructuredData')->once()->andThrow(new RuntimeException('credential '.Str::random(32)));
        $service = \Mockery::mock(AIContentService::class)->makePartial();
        $service->shouldReceive('getProvider')->once()->andReturn($provider);
        Log::spy();

        try {
            $service->process('slidebook_generation', $material, 'Prompt', 'Content', []);
            $this->fail('Provider failure must be reported.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Proses AI gagal. Silakan coba kembali.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }

        $log = AIProcessingLog::sole();
        $this->assertSame('failed', $log->status);
        $this->assertSame('Proses AI gagal. Silakan coba kembali.', $log->error_message);
        Log::shouldHaveReceived('error')->once()->with('AI provider processing failed', [
            'exception_type' => RuntimeException::class, 'processing_log_id' => $log->id,
            'provider' => 'mock',
        ]);
    }

    private function user(string $role): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role], ['label' => ucfirst($role)]);

        return User::factory()->create(['role_id' => $roleModel->id, 'is_active' => true]);
    }

    private function enroll(Course $course, User $student): CourseEnrollment
    {
        return $course->enrollments()->create(['student_id' => $student->id, 'status' => 'active', 'progress_percentage' => 0]);
    }

    /** @return array{Course, LearningMaterial, Slidebook, MaterialDocument} */
    private function content(): array
    {
        $instructor = $this->user('instructor');
        $course = Course::factory()->create(['instructor_id' => $instructor->id, 'status' => 'published']);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id, 'status' => 'published']);
        $book = $material->slidebooks()->create(['title' => 'Published lesson', 'status' => 'published', 'version' => 1, 'created_by' => $instructor->id]);
        Storage::fake('private');
        Storage::disk('private')->put('lesson.pdf', '%PDF-1.4 lesson');
        $document = $material->documents()->create([
            'original_name' => 'lesson.pdf', 'stored_name' => 'lesson.pdf', 'path' => 'lesson.pdf',
            'disk' => 'private', 'mime_type' => 'application/pdf', 'extension' => 'pdf', 'size' => 15, 'uploaded_by' => $instructor->id,
        ]);

        return [$course, $material, $book, $document];
    }

    /** @return array{User, Quiz, QuizAttempt, Question, QuestionOption} */
    private function attempt(): array
    {
        $instructor = $this->user('instructor');
        $student = $this->user('student');
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->enroll($course, $student);
        $quiz = Quiz::factory()->create(['course_id' => $course->id, 'status' => 'published', 'duration_minutes' => 1, 'total_questions' => 1]);
        $bank = QuestionBank::factory()->create(['instructor_id' => $instructor->id]);
        $question = Question::factory()->create(['question_bank_id' => $bank->id]);
        $option = QuestionOption::factory()->create(['question_id' => $question->id, 'is_correct' => true]);
        $quiz->quizQuestions()->create(['question_id' => $question->id, 'points' => 10, 'order' => 1]);
        $attempt = app(QuizAttemptService::class)->startAttempt($quiz, $student);

        return [$student, $quiz, $attempt, $question, $option];
    }
}
