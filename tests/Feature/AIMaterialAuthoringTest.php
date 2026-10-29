<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\AIMaterialAuthoringService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AIMaterialAuthoringTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $otherInstructor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $role = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $this->instructor = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $this->otherInstructor = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_instructor_can_open_material_ai_authoring_for_own_section(): void
    {
        $section = $this->section($this->instructor);

        $this->actingAs($this->instructor)
            ->get(route('instructor.materials.ai-authoring.create', $section))
            ->assertOk()
            ->assertSee('Buat Materi dengan AI')
            ->assertSee('AI Authoring Assistant');
    }

    public function test_instructor_cannot_open_material_ai_authoring_for_other_section(): void
    {
        $section = $this->section($this->otherInstructor);

        $this->actingAs($this->instructor)
            ->get(route('instructor.materials.ai-authoring.create', $section))
            ->assertForbidden();
    }

    public function test_generate_stores_material_draft_in_session_without_creating_material(): void
    {
        $section = $this->section($this->instructor);

        $this->mock(AIMaterialAuthoringService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateDraft')->once()->andReturn([
                'draft' => $this->draft(),
                'provider' => 'groq',
                'model' => 'openai/gpt-oss-20b',
            ]);
        });

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.materials.ai-authoring.generate', $section), [
                'topic' => 'Percabangan Java',
                'audience' => 'Siswa kelas X RPL SMK',
                'depth' => 'standard',
                'instructions' => 'Gunakan contoh kode sederhana.',
            ]);

        $response->assertRedirect(route('instructor.materials.ai-authoring.review', $section));
        $response->assertSessionHas('ai_material_authoring.'.$section->id);
        $this->assertDatabaseCount('learning_materials', 0);
    }

    public function test_teacher_reviewed_material_is_saved_as_draft_with_escaped_ai_content(): void
    {
        $section = $this->section($this->instructor);
        $draft = $this->draft();
        $draft['sections'][0]['content'] = 'Gunakan kondisi dengan aman. <script>alert("x")</script>';

        $response = $this->actingAs($this->instructor)
            ->withSession([
                'ai_material_authoring.'.$section->id => [
                    'material' => $draft,
                    'settings' => [],
                    'provider' => 'groq',
                    'model' => 'openai/gpt-oss-20b',
                ],
            ])
            ->post(route('instructor.materials.ai-authoring.store', $section), [
                'material' => $draft,
            ]);

        $material = LearningMaterial::firstOrFail();

        $response->assertRedirect(route('instructor.materials.edit', $material));
        $this->assertSame(LearningMaterial::STATUS_DRAFT, $material->status);
        $this->assertStringContainsString('Tujuan Pembelajaran', $material->content);
        $this->assertStringContainsString('&lt;script&gt;', $material->content);
        $this->assertStringNotContainsString('<script>', $material->content);
    }

    public function test_discard_material_ai_draft_does_not_create_material(): void
    {
        $section = $this->section($this->instructor);

        $response = $this->actingAs($this->instructor)
            ->withSession(['ai_material_authoring.'.$section->id => ['material' => $this->draft()]])
            ->delete(route('instructor.materials.ai-authoring.destroy', $section));

        $response->assertRedirect(route('instructor.materials.create', $section));
        $response->assertSessionMissing('ai_material_authoring.'.$section->id);
        $this->assertDatabaseCount('learning_materials', 0);
    }

    private function section(User $instructor): CourseSection
    {
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);

        return CourseSection::factory()->create(['course_id' => $course->id]);
    }

    /** @return array<string,mixed> */
    private function draft(): array
    {
        return [
            'title' => 'Percabangan Java',
            'description' => 'Materi pengantar penggunaan kondisi pada Java.',
            'duration_minutes' => 30,
            'learning_objectives' => [
                'Menjelaskan fungsi percabangan.',
                'Menerapkan if-else pada kasus sederhana.',
            ],
            'sections' => [
                [
                    'heading' => 'Konsep Percabangan',
                    'content' => 'Percabangan mengatur alur program berdasarkan kondisi.',
                    'example' => "int nilai = 80;\nif (nilai >= 75) {\n    System.out.println(\"Lulus\");\n}",
                ],
                [
                    'heading' => 'If-Else',
                    'content' => 'If-else digunakan ketika program memiliki dua kemungkinan alur.',
                    'example' => '',
                ],
            ],
            'summary' => 'Percabangan membantu program mengambil keputusan berdasarkan kondisi.',
            'practice' => [
                'Apa fungsi kondisi pada struktur if?',
                'Buat contoh sederhana if-else untuk menentukan status kelulusan.',
            ],
        ];
    }
}
