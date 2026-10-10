<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisualRichSlidebookTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_slidebook_persists_visual_layout_choices(): void
    {
        config(['ai.provider' => 'mock']);
        Http::preventStrayRequests();
        $this->seed(RoleSeeder::class);

        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $instructor = User::factory()->create([
            'role_id' => $instructorRole->id,
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Visual Learning',
            'slug' => 'visual-learning',
        ]);

        $course = Course::create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => 'Visual Learning Course',
            'slug' => 'visual-learning-course',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $section = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'Dasar',
            'order' => 1,
        ]);

        $material = LearningMaterial::create([
            'section_id' => $section->id,
            'title' => 'Materi Visual',
            'slug' => 'materi-visual',
            'status' => 'draft',
            'duration_minutes' => 10,
            'order' => 1,
            'content' => "Konsep utama pembelajaran digital dan penerapannya.\n\nMateri membahas struktur, proses, dan evaluasi pembelajaran secara bertahap.",
        ]);

        $this->actingAs($instructor)
            ->post(route('instructor.materials.ai.slidebook', $material))
            ->assertRedirect(route('instructor.materials.slidebook.review', $material));

        $slidebook = Slidebook::where('material_id', $material->id)->firstOrFail();
        $slides = $slidebook->slides()->orderBy('order')->get();

        $this->assertGreaterThanOrEqual(3, $slides->count());
        $this->assertSame('cover', $slides->first()->layout);
        $this->assertSame('key-points', $slides->get(1)->layout);
        $this->assertSame('summary', $slides->last()->layout);
        $this->assertFalse($slides->contains(fn ($slide): bool => empty($slide->layout)));
    }
}
