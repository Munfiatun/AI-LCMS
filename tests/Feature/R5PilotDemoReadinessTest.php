<?php

namespace Tests\Feature;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\ExperimentAssignment;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\R5PilotDemoSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class R5PilotDemoReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_demo_provisions_teacher_and_two_isolated_student_journeys(): void
    {
        $this->seedPilot();
        $course = Course::query()->where('slug', 'lms-feature-showcase')->firstOrFail();
        $teacher = User::query()->where('email', 'instructor@example.com')->firstOrFail();
        $adaptive = User::query()->where('email', R5PilotDemoSeeder::ADAPTIVE_EMAIL)->firstOrFail();
        $control = User::query()->where('email', R5PilotDemoSeeder::CONTROL_EMAIL)->firstOrFail();

        $this->assertTrue($teacher->isInstructor());
        $this->assertTrue($adaptive->isStudent());
        $this->assertTrue($control->isStudent());
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id, 'student_id' => $adaptive->id, 'status' => 'active',
        ]);
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id, 'student_id' => $control->id, 'status' => 'active',
        ]);
        $this->assertDatabaseHas('experiment_assignments', [
            'course_id' => $course->id, 'student_id' => $adaptive->id, 'condition' => 'adaptive',
        ]);
        $this->assertDatabaseHas('experiment_assignments', [
            'course_id' => $course->id, 'student_id' => $control->id, 'condition' => 'non_adaptive',
        ]);
        $this->assertDatabaseCount('learner_mastery', 2);
        $this->assertSame(1, LearningConcept::query()->where('course_id', $course->id)
            ->where('code', 'R5-DEMO-HTTP-01')->count());

        $this->assertTrue(AdaptiveRecommendation::query()
            ->where('student_id', $adaptive->id)
            ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
            ->exists());
        $this->assertFalse(AdaptiveRecommendation::query()
            ->where('student_id', $control->id)
            ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
            ->exists());

        $this->actingAs($teacher)
            ->get(route('instructor.courses.research.show', $course))
            ->assertOk()
            ->assertSee('Siswa Demo Adaptif')
            ->assertSee('Siswa Demo Kontrol');

        $this->actingAs($teacher)
            ->get(route('instructor.mastery.index', ['course_id' => $course->id]))
            ->assertOk();

        $this->actingAs($adaptive)
            ->get(route('student.adaptive-recommendations.index'))
            ->assertOk()
            ->assertSee('Alur HTTP Request dan Response')
            ->assertSee('Web Request Lifecycle')
            ->assertSee('Mulai Belajar');

        $this->actingAs($control)
            ->get(route('student.adaptive-recommendations.index'))
            ->assertOk()
            ->assertSee('Belum ada rekomendasi adaptif aktif')
            ->assertDontSee('Alur HTTP Request dan Response')
            ->assertDontSee('Materi yang disarankan');

        $this->actingAs($control)
            ->get(route('student.courses.index'))
            ->assertOk()
            ->assertSee('LMS Feature Showcase');
    }

    public function test_rerunning_r5_demo_preserves_existing_assignments_and_learning_progress(): void
    {
        $this->seedPilot();
        $course = Course::query()->where('slug', 'lms-feature-showcase')->firstOrFail();
        $adaptive = User::query()->where('email', R5PilotDemoSeeder::ADAPTIVE_EMAIL)->firstOrFail();
        $control = User::query()->where('email', R5PilotDemoSeeder::CONTROL_EMAIL)->firstOrFail();

        $originalIds = ExperimentAssignment::query()->where('course_id', $course->id)
            ->orderBy('student_id')->pluck('id')->all();
        $mastery = LearnerMastery::query()->where('student_id', $adaptive->id)->sole();
        $mastery->update(['mastery_score' => 72, 'evidence_count' => 5]);
        CourseEnrollment::query()->where('student_id', $control->id)->where('course_id', $course->id)
            ->update(['progress_percentage' => 42.00]);
        $recommendationCount = AdaptiveRecommendation::query()->where('student_id', $adaptive->id)->count();

        $this->seed(R5PilotDemoSeeder::class);

        $this->assertSame($originalIds, ExperimentAssignment::query()
            ->where('course_id', $course->id)->orderBy('student_id')->pluck('id')->all());
        $this->assertSame(72.0, $mastery->fresh()->mastery_score);
        $this->assertSame(5, $mastery->fresh()->evidence_count);
        $this->assertSame('42.00', CourseEnrollment::query()->where('student_id', $control->id)
            ->where('course_id', $course->id)->firstOrFail()->progress_percentage);
        $this->assertSame($recommendationCount + 1, AdaptiveRecommendation::query()
            ->where('student_id', $adaptive->id)->count());
        $this->assertDatabaseCount('experiment_assignments', 2);
    }

    public function test_r5_demo_is_disabled_in_production(): void
    {
        config(['auth.demo_user_password' => 'synthetic-test-only']);
        $this->app['env'] = 'production';

        try {
            /** @var Seeder $seeder */
            $seeder = app(R5PilotDemoSeeder::class);
            $seeder->run();
        } finally {
            $this->app['env'] = 'testing';
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('experiment_assignments', 0);
    }

    private function seedPilot(): void
    {
        config(['auth.demo_user_password' => 'synthetic-test-only']);
        $this->seed(DatabaseSeeder::class);
    }
}
