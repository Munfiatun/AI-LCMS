<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\Course\CourseWorkflowReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UXWorkflowRefinementTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_dashboard_explains_complete_learning_creation_journey_in_teacher_language(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('Alur Kerja Sederhana')
            ->assertSee('Dari kursus sampai evaluasi')
            ->assertSee('Kursus')
            ->assertSee('Materi')
            ->assertSee('Bank Soal')
            ->assertSee('Kuis')
            ->assertSee('Pantau')
            ->assertSee('Buat Kursus Pertama')
            ->assertDontSee('AI Pipeline Ready');
    }

    public function test_dashboard_surfaces_missing_slidebook_and_unpublished_quiz_with_contextual_next_action(): void
    {
        $teacher = $this->teacher();
        $course = Course::factory()->create([
            'instructor_id' => $teacher->id,
            'title' => 'Pemrograman Web',
            'status' => Course::STATUS_DRAFT,
        ]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'title' => 'Komponen Aplikasi Web',
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);
        Quiz::factory()->create([
            'course_id' => $course->id,
            'section_id' => $section->id,
            'title' => 'Quiz Komponen Web',
            'status' => 'draft',
        ]);

        $this->actingAs($teacher)
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('Pemrograman Web')
            ->assertSee('1 materi belum memiliki Slidebook terbit.')
            ->assertSee('1 Quiz masih draft atau belum terbit.')
            ->assertSee('Kursus belum dipublikasikan ke siswa.')
            ->assertSee('Buat Slidebook dari Materi');
    }

    public function test_readiness_service_moves_teacher_to_quiz_after_published_slidebook_exists(): void
    {
        $teacher = $this->teacher();
        $course = Course::factory()->create(['instructor_id' => $teacher->id, 'status' => Course::STATUS_DRAFT]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);
        Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Slidebook Web',
            'created_by' => $teacher->id,
            'status' => Slidebook::STATUS_PUBLISHED,
            'version' => 1,
            'published_at' => now(),
        ]);

        $summary = app(CourseWorkflowReadinessService::class)->summarize($course);

        $this->assertSame(1, $summary['counts']['materials']);
        $this->assertSame(1, $summary['counts']['published_slidebooks']);
        $this->assertSame(0, $summary['counts']['quizzes']);
        $this->assertSame('Buat Quiz Pembelajaran', $summary['next_action']['label']);
        $this->assertStringContainsString(route('instructor.quizzes.index'), $summary['next_action']['url']);
    }

    private function teacher(): User
    {
        $role = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);

        return User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
