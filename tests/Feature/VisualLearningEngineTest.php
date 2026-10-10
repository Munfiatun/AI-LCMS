<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\AI\AISlidebookService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisualLearningEngineTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.provider' => 'mock']);
        Http::preventStrayRequests();
        $this->seed(RoleSeeder::class);

        $role = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $this->instructor = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Informatika',
            'slug' => 'informatika-visual-learning-test',
        ]);

        $course = Course::create([
            'instructor_id' => $this->instructor->id,
            'category_id' => $category->id,
            'title' => 'Informatika Visual',
            'slug' => 'informatika-visual-learning-test',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $section = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'Internet dan Web',
            'order' => 1,
        ]);

        $this->material = LearningMaterial::create([
            'section_id' => $section->id,
            'title' => 'Cara Kerja Internet',
            'slug' => 'cara-kerja-internet-visual-learning-test',
            'status' => 'draft',
            'duration_minutes' => 15,
            'order' => 1,
            'content' => "Browser mengirim HTTP request kepada server.\nServer memproses request dan mengirim response.\nDatabase menyimpan data aplikasi.",
        ]);
    }

    public function test_teacher_can_save_smp_audience_mode_from_review_design_panel(): void
    {
        $slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Internet untuk Siswa',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'created_by' => $this->instructor->id,
        ]);
        $slidebook->slides()->create([
            'title' => 'Cara Kerja Internet',
            'content' => 'Browser mengirim request kepada server.',
            'layout' => 'concept',
            'order' => 1,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->instructor)
            ->put(route('instructor.slidebooks.design.update', $slidebook), [
                'preset' => 'creative-education',
                'audience' => 'smp',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $slidebook->refresh();
        $this->assertSame('creative-education', $slidebook->design_settings['preset']);
        $this->assertSame('smp', $slidebook->design_settings['audience']);

        $this->actingAs($this->instructor)
            ->get(route('instructor.materials.slidebook.review', $this->material))
            ->assertOk()
            ->assertSee('Target Siswa')
            ->assertSee('SMP — lebih visual & ringkas', false);
    }

    public function test_regeneration_preserves_teacher_audience_preference(): void
    {
        Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Versi Awal',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'design_settings' => [
                'preset' => 'fresh-learning',
                'audience' => 'smp',
            ],
            'created_by' => $this->instructor->id,
        ]);

        $generated = app(AISlidebookService::class)
            ->generateSlidebookForMaterial($this->material, $this->instructor, true);

        $this->assertSame(2, $generated->version);
        $this->assertSame('smp', $generated->design_settings['audience']);
        $this->assertSame('fresh-learning', $generated->design_settings['preset']);
        $this->assertSame('cover', $generated->slides()->orderBy('order')->first()->layout);
    }

    public function test_student_preview_renders_visual_learning_scene(): void
    {
        $slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Internet untuk Siswa',
            'status' => Slidebook::STATUS_REVIEW,
            'version' => 1,
            'design_settings' => [
                'preset' => 'creative-education',
                'audience' => 'smp',
            ],
            'created_by' => $this->instructor->id,
        ]);
        $slidebook->slides()->create([
            'title' => 'Cara Kerja Internet',
            'subtitle' => 'Dari browser menuju server',
            'content' => "Browser mengirim HTTP request.\nServer mengirim response kepada browser.",
            'layout' => 'process',
            'order' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.slidebooks.preview', $slidebook))
            ->assertOk()
            ->assertSee('audience-smp', false)
            ->assertSee('data-scene="web-flow"', false)
            ->assertSee('Alur browser, internet, server, dan data');
    }
}
