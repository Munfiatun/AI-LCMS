<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\QuestionBank;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\AIMaterialRevisionService;
use App\Services\AI\AIQuestionRevisionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class AIAuthoringRevisionTest extends TestCase
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
    }

    public function test_question_revision_replaces_session_draft_without_saving_questions(): void
    {
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $this->instructor->id,
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);
        $questions = $this->questions();
        $revised = $questions;
        $revised[0]['question_text'] = 'Manakah tujuan utama struktur if-else pada Java?';
        $instruction = 'Buat Soal 1 lebih mudah dan pertahankan soal lainnya.';

        $this->mock(AIQuestionRevisionService::class, function (MockInterface $mock) use ($revised): void {
            $mock->shouldReceive('reviseDraft')->once()->andReturn([
                'questions' => $revised,
                'provider' => 'groq',
                'model' => 'openai/gpt-oss-20b',
            ]);
        });

        $response = $this->actingAs($this->instructor)
            ->withSession([
                'ai_question_authoring.'.$bank->id => [
                    'questions' => $questions,
                    'settings' => ['topic' => 'Percabangan Java'],
                    'provider' => 'groq',
                    'model' => 'openai/gpt-oss-20b',
                    'revision_count' => 0,
                ],
            ])
            ->post(route('instructor.question-banks.ai-authoring.revise', $bank), [
                'revision_instruction' => $instruction,
                'questions' => $questions,
            ]);

        $response->assertRedirect(route('instructor.question-banks.ai-authoring.review', $bank));
        $response->assertSessionHas('ai_question_authoring.'.$bank->id.'.questions.0.question_text', $revised[0]['question_text']);
        $response->assertSessionHas('ai_question_authoring.'.$bank->id.'.revision_count', 1);
        $response->assertSessionHas('ai_question_authoring.'.$bank->id.'.last_revision', $instruction);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_material_revision_replaces_session_draft_without_saving_material(): void
    {
        $course = Course::factory()->create(['instructor_id' => $this->instructor->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = $this->material();
        $revised = $material;
        $revised['summary'] = 'If-else membantu program memilih alur berdasarkan hasil evaluasi kondisi.';
        $instruction = 'Ringkas rangkuman dan pertahankan bagian lainnya.';

        $this->mock(AIMaterialRevisionService::class, function (MockInterface $mock) use ($revised): void {
            $mock->shouldReceive('reviseDraft')->once()->andReturn([
                'material' => $revised,
                'provider' => 'groq',
                'model' => 'openai/gpt-oss-20b',
            ]);
        });

        $response = $this->actingAs($this->instructor)
            ->withSession([
                'ai_material_authoring.'.$section->id => [
                    'material' => $material,
                    'settings' => ['topic' => 'Percabangan Java'],
                    'provider' => 'groq',
                    'model' => 'openai/gpt-oss-20b',
                    'revision_count' => 0,
                ],
            ])
            ->post(route('instructor.materials.ai-authoring.revise', $section), [
                'revision_instruction' => $instruction,
                'material' => $material,
            ]);

        $response->assertRedirect(route('instructor.materials.ai-authoring.review', $section));
        $response->assertSessionHas('ai_material_authoring.'.$section->id.'.material.summary', $revised['summary']);
        $response->assertSessionHas('ai_material_authoring.'.$section->id.'.revision_count', 1);
        $response->assertSessionHas('ai_material_authoring.'.$section->id.'.last_revision', $instruction);
        $this->assertDatabaseCount('learning_materials', 0);
    }

    /** @return list<array<string,mixed>> */
    private function questions(): array
    {
        return [
            [
                'question_text' => 'Apa fungsi utama percabangan if-else pada Java?',
                'topic' => 'Percabangan Java',
                'difficulty' => 'medium',
                'explanation' => 'If-else memilih alur berdasarkan kondisi.',
                'points' => 10,
                'options' => ['Memilih alur berdasarkan kondisi', 'Mengulang blok tanpa kondisi', 'Membuat class baru', 'Menghapus variabel'],
                'correct_option' => 0,
            ],
            [
                'question_text' => 'Kapan blok else dijalankan pada struktur if-else?',
                'topic' => 'Percabangan Java',
                'difficulty' => 'medium',
                'explanation' => 'Else dijalankan ketika kondisi if bernilai false.',
                'points' => 10,
                'options' => ['Saat kondisi if false', 'Saat kondisi if true', 'Saat compiler berhenti', 'Saat class dibuat'],
                'correct_option' => 0,
            ],
        ];
    }

    /** @return array<string,mixed> */
    private function material(): array
    {
        return [
            'title' => 'Percabangan Java',
            'description' => 'Materi dasar percabangan untuk siswa kelas X RPL.',
            'duration_minutes' => 30,
            'learning_objectives' => [
                'Menjelaskan fungsi percabangan.',
                'Menerapkan if-else pada kasus sederhana.',
            ],
            'sections' => [
                [
                    'heading' => 'Konsep If-Else',
                    'content' => 'If-else digunakan untuk memilih satu dari dua alur berdasarkan kondisi boolean.',
                    'example' => "if (nilai >= 75) {\n    System.out.println(\"Lulus\");\n}",
                ],
            ],
            'summary' => 'Percabangan digunakan untuk menentukan alur program berdasarkan kondisi.',
            'practice' => [
                'Apa fungsi kondisi pada if?',
                'Buat contoh if-else sederhana.',
            ],
        ];
    }
}
