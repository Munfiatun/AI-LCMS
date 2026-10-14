<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\LearningAnalyticsInsightService;
use App\Services\SlidebookQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceStressTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_dashboard_keeps_large_attempt_and_enrollment_histories_as_aggregates(): void
    {
        [$teacher, $studentRole] = $this->rolesAndTeacher();
        $students = User::factory()->count(12)->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);

        $course = Course::factory()->create([
            'instructor_id' => $teacher->id,
            'title' => 'Kursus Skala Besar',
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);
        Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Slidebook Skala Besar',
            'status' => Slidebook::STATUS_PUBLISHED,
            'version' => 1,
            'created_by' => $teacher->id,
            'published_at' => now(),
        ]);
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        foreach ($students as $student) {
            CourseEnrollment::create([
                'course_id' => $course->id,
                'student_id' => $student->id,
                'status' => 'active',
                'progress_percentage' => 50,
            ]);
        }

        for ($index = 0; $index < 120; $index++) {
            $submittedAt = now()->subMinutes(120 - $index);
            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $students[$index % $students->count()]->id,
                'started_at' => $submittedAt->copy()->subMinutes(5),
                'expires_at' => $submittedAt->copy()->addMinutes(55),
                'submitted_at' => $submittedAt,
                'status' => 'submitted',
                'score' => 10,
                'percentage' => 100,
                'correct_count' => 1,
                'wrong_count' => 0,
                'duration_seconds' => 300,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($teacher)->get(route('instructor.dashboard'));

        $response->assertOk()->assertSee('Kursus Skala Besar');
        $readiness = $response->viewData('courseReadiness')->first();
        $loadedCourse = $readiness['course'];

        $this->assertSame(12, $readiness['counts']['students']);
        $this->assertSame(120, $readiness['counts']['submitted_attempts']);
        $this->assertFalse($loadedCourse->relationLoaded('enrollments'));
        foreach ($loadedCourse->quizzes as $loadedQuiz) {
            $this->assertFalse($loadedQuiz->relationLoaded('attempts'));
        }

        $queries = collect(DB::getQueryLog());
        $fullAttemptLoads = $queries->filter(fn (array $query): bool => (bool) preg_match('/select\s+(?:\*|"quiz_attempts"\.\*)\s+from\s+"quiz_attempts"/i', $query['query']));
        $fullEnrollmentLoads = $queries->filter(fn (array $query): bool => (bool) preg_match('/select\s+(?:\*|"course_enrollments"\.\*)\s+from\s+"course_enrollments"/i', $query['query']));

        $this->assertCount(0, $fullAttemptLoads, 'Dashboard must aggregate attempts instead of materializing attempt history.');
        $this->assertCount(0, $fullEnrollmentLoads, 'Dashboard must aggregate enrollments instead of materializing enrollment history.');
        $this->assertLessThanOrEqual(18, $queries->count(), 'Dashboard query budget should stay bounded for large histories.');
    }

    public function test_learning_signals_select_latest_attempt_in_database_even_with_many_retries(): void
    {
        [$teacher, $studentRole] = $this->rolesAndTeacher();
        $student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'passing_score' => 70,
            'status' => 'published',
            'published_at' => now(),
        ]);

        for ($index = 0; $index < 80; $index++) {
            $submittedAt = now()->subMinutes(80 - $index);
            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'started_at' => $submittedAt->copy()->subMinutes(2),
                'expires_at' => $submittedAt->copy()->addMinutes(58),
                'submitted_at' => $submittedAt,
                'status' => 'submitted',
                'score' => $index === 79 ? 10 : 0,
                'percentage' => $index === 79 ? 100 : 0,
                'correct_count' => $index === 79 ? 1 : 0,
                'wrong_count' => $index === 79 ? 0 : 1,
                'duration_seconds' => 120,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $insights = app(LearningAnalyticsInsightService::class)->summarize($course, [
            'totalStudents' => 1,
            'quizParticipants' => 1,
            'totalQuizzes' => 1,
            'atRiskStudents' => 0,
            'averageProgress' => 100,
        ]);

        $this->assertSame(1, $insights['evidence']['representative_attempts']);
        $this->assertSame(100.0, $insights['evidence']['latest_attempt_pass_rate']);

        $queries = collect(DB::getQueryLog());
        $attemptQuery = $queries->first(fn (array $query): bool => str_contains($query['query'], 'from "quiz_attempts"'));
        $this->assertNotNull($attemptQuery);
        $this->assertStringContainsString('latest_attempt', $attemptQuery['query']);
        $this->assertLessThanOrEqual(5, $queries->count(), 'Learning Signals query count should depend on relations, not retry history length.');
    }

    public function test_slidebook_quality_check_keeps_query_budget_constant_for_one_hundred_slides(): void
    {
        [$teacher] = $this->rolesAndTeacher();
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'content' => 'Materi sumber berisi konsep pembelajaran, contoh, proses, data, dan rangkuman.',
        ]);
        $slidebook = Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Stress Slidebook',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $teacher->id,
            'design_settings' => ['preset' => 'indigo-dark', 'audience' => 'sma-smk'],
        ]);

        $layouts = ['concept', 'key-points', 'comparison', 'process', 'summary'];
        for ($index = 1; $index <= 100; $index++) {
            $slidebook->slides()->create([
                'title' => 'Slide '.$index,
                'content' => 'Penjelasan ringkas konsep pembelajaran nomor '.$index.' dengan isi yang tetap mudah dibaca siswa.',
                'layout' => $layouts[($index - 1) % count($layouts)],
                'order' => $index,
                'status' => 'active',
                'needs_review' => false,
                'source_reference' => ['manual' => true],
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $report = app(SlidebookQualityService::class)->evaluate($slidebook);

        $this->assertSame(100, $report['summary']['slides']);
        $this->assertSame(0, $report['summary']['critical_count']);
        $this->assertGreaterThanOrEqual(5, $report['summary']['unique_layouts']);
        $this->assertLessThanOrEqual(6, count(DB::getQueryLog()), 'Slide quality queries must not grow per slide.');
    }

    /** @return array{User, Role} */
    private function rolesAndTeacher(): array
    {
        $teacherRole = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);
        $studentRole = Role::firstOrCreate(['name' => Role::ROLE_STUDENT], ['label' => 'Student']);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id, 'is_active' => true]);

        return [$teacher, $studentRole];
    }
}
