<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorMaterialPreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();

        $this->instructor = User::factory()->create([
            'role_id' => $instructorRole->id,
            'is_active' => true,
        ]);
    }

    public function test_material_preview_with_slidebook_opens_student_renderer_directly(): void
    {
        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'status' => Course::STATUS_DRAFT,
        ]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id]);

        $slidebook = Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Preview Algoritma',
            'status' => Slidebook::STATUS_DRAFT,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.materials.preview', $material))
            ->assertRedirect(route('instructor.slidebooks.preview', $slidebook));

        $this->actingAs($this->instructor)
            ->get(route('instructor.slidebooks.preview', $slidebook))
            ->assertOk()
            ->assertSee('Preview Siswa')
            ->assertDontSee('Dashboard Guru')
            ->assertDontSee('Kembali ke Beranda');
    }
}
