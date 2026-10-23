<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Slidebook;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LmsDemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecoveryStabilizationTest extends TestCase
{
    use RefreshDatabase;

    private function seedDemo(): void
    {
        config(['auth.demo_user_password' => 'demo-test-password']);
        $this->seed(RoleSeeder::class);
        $this->seed(UserSeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(LmsDemoSeeder::class);
    }

    public function test_demo_quick_login_is_only_shown_when_demo_password_is_configured(): void
    {
        config(['auth.demo_user_password' => null]);
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('Akun Demo Cepat');

        config(['auth.demo_user_password' => 'demo-test-password']);
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Akun Demo Cepat')
            ->assertSee('fillDemoCredentials')
            ->assertDontSee("fillCredentials('instructor@example.com', 'password')", false);
    }

    public function test_demo_seeder_creates_domain_valid_showcase_and_is_idempotent(): void
    {
        $this->seedDemo();
        $this->seed(LmsDemoSeeder::class);

        $course = Course::where('slug', 'lms-feature-showcase')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebooks = $material->slidebooks()->orderBy('version')->get();

        $this->assertCount(2, $slidebooks);
        $this->assertSame(Slidebook::STATUS_PUBLISHED, $slidebooks[0]->status);
        $this->assertContains($slidebooks[1]->status, [Slidebook::STATUS_DRAFT, Slidebook::STATUS_REVIEW]);
        $this->assertSame(18, $slidebooks[0]->slides()->count());
        $this->assertSame(18, $slidebooks[1]->slides()->count());

        $quiz = Quiz::where('course_id', $course->id)->where('title', 'Web Fundamentals Quiz')->firstOrFail();
        $this->assertSame('published', $quiz->status);
        $this->assertSame(3, $quiz->total_questions);
        $this->assertSame(3, $quiz->questions()->count());

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('status', 'submitted')->firstOrFail();
        $this->assertTrue($attempt->isPassed());

        $this->assertSame(1, Course::where('slug', 'lms-feature-showcase')->count());
        $this->assertSame(1, User::where('email', 'instructor@example.com')->count());
        $this->assertSame(1, User::where('email', 'student@example.com')->count());
    }

    public function test_instructor_dashboard_uses_real_demo_counts(): void
    {
        $this->seedDemo();
        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();

        $response = $this->actingAs($instructor)->get(route('instructor.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['total_courses'] > 0
                && $stats['total_materials'] > 0
                && $stats['total_slidebooks'] > 0
                && $stats['total_question_banks'] > 0
                && $stats['total_quizzes'] > 0
                && $stats['total_students'] > 0;
        });
    }

    public function test_material_instructor_preview_uses_student_renderer_without_instructor_chrome(): void
    {
        $this->seedDemo();
        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $slidebook = $material->slidebook()->firstOrFail();

        $this->actingAs($instructor)
            ->get(route('instructor.materials.preview', $material))
            ->assertRedirect(route('instructor.slidebooks.preview', $slidebook));

        $this->actingAs($instructor)
            ->get(route('instructor.slidebooks.preview', $slidebook))
            ->assertOk()
            ->assertSee('Preview Siswa')
            ->assertDontSee('Dashboard Guru')
            ->assertDontSee('Kembali ke Beranda')
            ->assertDontSee('Tandai Selesai');
    }

    public function test_review_page_prefers_latest_editable_revision(): void
    {
        $this->seedDemo();
        $instructor = User::where('email', 'instructor@example.com')->firstOrFail();
        $material = LearningMaterial::where('slug', 'slidebook-layout-gallery')->firstOrFail();
        $latest = $material->slidebooks()->latest('version')->firstOrFail();

        $this->actingAs($instructor)
            ->get(route('instructor.materials.slidebook.review', $material))
            ->assertOk()
            ->assertSee('Slidebook v'.$latest->version)
            ->assertSee('Preview Siswa');
    }
}
