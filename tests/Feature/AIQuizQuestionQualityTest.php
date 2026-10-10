<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use App\Services\AI\AIQuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIQuizQuestionQualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ai.provider' => 'openai',
            'ai.providers.openai.api_key' => 'test-key',
            'ai.providers.openai.base_url' => 'https://api.openai.com/v1',
        ]);
        Http::preventStrayRequests();
    }

    public function test_source_framed_question_is_rejected_and_retry_saves_standalone_item(): void
    {
        [$teacher, $slidebook] = $this->source('smp');

        $bad = $this->validOutput();
        $bad['questions'][0]['question_text'] = 'Menurut slide 1, komponen apa yang menyimpan data secara terstruktur?';

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::sequence()
                ->push($this->response($bad))
                ->push($this->response($this->validOutput())),
        ]);

        $quiz = app(AIQuizService::class)->generate($slidebook, $teacher, [
            'total_questions' => 2,
            'difficulty' => 'medium',
            'type' => 'multiple_choice',
            'custom_instructions' => 'Uji pemahaman konsep aplikasi web.',
        ]);

        // The relation already orders by quiz_questions.order; adding an unqualified
        // `order` column here is ambiguous because both joined tables own that name.
        $texts = $quiz->questions()->pluck('question_text')->all();
        $this->assertSame('Komponen aplikasi web manakah yang menyimpan data secara terstruktur?', $texts[0]);
        $this->assertStringNotContainsStringIgnoringCase('slide', implode(' ', $texts));
        Http::assertSentCount(2);

        $requests = Http::recorded();
        $firstPrompt = $requests[0][0]['messages'][0]['content'];
        $secondSource = json_decode($requests[1][0]['messages'][1]['content'], true);

        $this->assertStringContainsString('Stem harus dapat dipahami dan dijawab secara mandiri', $firstPrompt);
        $this->assertStringContainsString('Target siswa SMP', $firstPrompt);
        $this->assertStringContainsString('hanya satu yang benar', $firstPrompt);
        $this->assertArrayHasKey('retry_instruction', $secondSource);
    }

    /** @return array{User, Slidebook} */
    private function source(string $audience): array
    {
        $role = Role::firstOrCreate(['name' => 'instructor'], ['label' => 'Instructor']);
        $teacher = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id]);
        $slidebook = Slidebook::create([
            'material_id' => $material->id,
            'title' => 'Komponen Aplikasi Web',
            'created_by' => $teacher->id,
            'status' => 'published',
            'design_settings' => ['preset' => 'creative-education', 'audience' => $audience],
        ]);
        $slidebook->slides()->createMany([
            [
                'title' => 'Komponen Utama',
                'content' => 'Frontend berinteraksi dengan pengguna. Backend menangani logika bisnis. Database menyimpan data secara terstruktur. Server menjalankan aplikasi.',
                'order' => 1,
            ],
            [
                'title' => 'Alur Web',
                'content' => 'Browser mengirim request kepada server. Server memproses request dan mengirim response kepada browser.',
                'order' => 2,
            ],
        ]);

        return [$teacher, $slidebook];
    }

    /** @return array<string, mixed> */
    private function validOutput(): array
    {
        return ['questions' => [
            [
                'question_text' => 'Komponen aplikasi web manakah yang menyimpan data secara terstruktur?',
                'options' => ['Frontend', 'Backend', 'Database', 'Server'],
                'correct_option' => 2,
                'difficulty' => 'medium',
                'explanation' => 'Database berfungsi menyimpan data aplikasi secara terstruktur.',
                'source_slide_number' => 1,
            ],
            [
                'question_text' => 'Komponen manakah yang memproses request sebelum response dikirim kembali ke browser?',
                'options' => ['Server', 'Frontend', 'Database', 'Antarmuka pengguna'],
                'correct_option' => 0,
                'difficulty' => 'medium',
                'explanation' => 'Server menerima dan memproses request sebelum mengirimkan response ke browser.',
                'source_slide_number' => 2,
            ],
        ]];
    }

    /** @param array<string, mixed> $output
     * @return array<string, mixed>
     */
    private function response(array $output): array
    {
        return [
            'choices' => [['message' => ['content' => json_encode($output)]]],
            'usage' => ['total_tokens' => 100],
        ];
    }
}
