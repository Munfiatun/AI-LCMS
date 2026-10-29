<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\QuestionBank;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AIQuestionAuthoringSourceMaterialTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $role = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $this->instructor = User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        Config::set('ai.provider', 'mock');
    }

    public function test_question_authoring_lists_only_materials_from_same_course(): void
    {
        [$bank, $course] = $this->bankWithCourse();
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'title' => 'Materi Percabangan Internal',
            'content' => '<p>if-else memilih alur berdasarkan kondisi.</p>',
        ]);

        $otherCourse = Course::factory()->create(['instructor_id' => $this->instructor->id]);
        $otherSection = CourseSection::factory()->create(['course_id' => $otherCourse->id]);
        LearningMaterial::factory()->create([
            'section_id' => $otherSection->id,
            'title' => 'Materi Kursus Lain',
            'content' => '<p>Konten lain.</p>',
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.question-banks.ai-authoring.create', $bank))
            ->assertOk()
            ->assertSee('Sumber materi')
            ->assertSee($material->title)
            ->assertDontSee('Materi Kursus Lain');
    }

    public function test_generate_can_ground_questions_in_material_from_same_course(): void
    {
        [$bank, $course] = $this->bankWithCourse();
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'title' => 'Percabangan Java',
            'description' => 'Materi dasar percabangan.',
            'content' => '<p>Struktur if-else memilih satu dari dua alur berdasarkan nilai kondisi boolean.</p>',
        ]);

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.question-banks.ai-authoring.generate', $bank), [
                'topic' => 'Percabangan Java',
                'total_questions' => 3,
                'difficulty' => 'medium',
                'instructions' => 'Buat soal penerapan berdasarkan materi sumber.',
                'source_material_id' => $material->id,
            ]);

        $response->assertRedirect(route('instructor.question-banks.ai-authoring.review', $bank));
        $response->assertSessionHas('ai_question_authoring.'.$bank->id.'.source_material.id', $material->id);
        $response->assertSessionHas('ai_question_authoring.'.$bank->id.'.settings.source_material_id', $material->id);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_generate_rejects_material_from_another_course(): void
    {
        [$bank] = $this->bankWithCourse();

        $otherCourse = Course::factory()->create(['instructor_id' => $this->instructor->id]);
        $otherSection = CourseSection::factory()->create(['course_id' => $otherCourse->id]);
        $otherMaterial = LearningMaterial::factory()->create([
            'section_id' => $otherSection->id,
            'content' => '<p>Materi yang tidak boleh dipakai oleh bank ini.</p>',
        ]);

        $response = $this->actingAs($this->instructor)
            ->from(route('instructor.question-banks.ai-authoring.create', $bank))
            ->post(route('instructor.question-banks.ai-authoring.generate', $bank), [
                'topic' => 'Percabangan Java',
                'total_questions' => 3,
                'difficulty' => 'medium',
                'source_material_id' => $otherMaterial->id,
            ]);

        $response->assertRedirect(route('instructor.question-banks.ai-authoring.create', $bank));
        $response->assertSessionHasErrors('source_material_id');
        $this->assertDatabaseCount('questions', 0);
    }

    /** @return array{QuestionBank, Course} */
    private function bankWithCourse(): array
    {
        $course = Course::factory()->create(['instructor_id' => $this->instructor->id]);
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $this->instructor->id,
            'course_id' => $course->id,
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);

        return [$bank, $course];
    }
}
