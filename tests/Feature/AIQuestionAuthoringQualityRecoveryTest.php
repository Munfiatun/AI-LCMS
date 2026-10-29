<?php

namespace Tests\Feature;

use App\Models\QuestionBank;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\AIQuestionAuthoringService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class AIQuestionAuthoringQualityRecoveryTest extends TestCase
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

    public function test_quality_gate_failure_is_regenerated_once_before_showing_error(): void
    {
        $bank = $this->bank();

        $this->mock(AIQuestionAuthoringService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateDraft')
                ->once()
                ->withArgs(fn ($bank, $settings, $force) => $force === false)
                ->andThrow(new RuntimeException('AI menghasilkan pilihan jawaban yang tidak valid.'));

            $mock->shouldReceive('generateDraft')
                ->once()
                ->withArgs(fn ($bank, $settings, $force) => $force === true)
                ->andReturn($this->draft());
        });

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.question-banks.ai-authoring.generate', $bank), [
                'topic' => 'Percabangan Java',
                'total_questions' => 2,
                'difficulty' => 'medium',
                'source_mode' => 'prompt',
            ]);

        $response->assertRedirect(route('instructor.question-banks.ai-authoring.review', $bank));
        $response->assertSessionHas('ai_question_authoring.'.$bank->id.'.questions');
    }

    public function test_second_quality_gate_failure_is_shown_as_safe_specific_error(): void
    {
        $bank = $this->bank();

        $this->mock(AIQuestionAuthoringService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generateDraft')
                ->twice()
                ->andThrow(new RuntimeException('AI menghasilkan pilihan jawaban yang tidak valid.'));
        });

        $response = $this->actingAs($this->instructor)
            ->from(route('instructor.question-banks.ai-authoring.create', $bank))
            ->post(route('instructor.question-banks.ai-authoring.generate', $bank), [
                'topic' => 'Percabangan Java',
                'total_questions' => 2,
                'difficulty' => 'medium',
                'source_mode' => 'prompt',
            ]);

        $response->assertRedirect(route('instructor.question-banks.ai-authoring.create', $bank));
        $response->assertSessionHasErrors([
            'ai' => 'AI menghasilkan pilihan jawaban yang tidak valid. Draft belum disimpan. Silakan generate ulang atau ubah instruksi.',
        ]);
    }

    private function bank(): QuestionBank
    {
        return QuestionBank::factory()->create([
            'instructor_id' => $this->instructor->id,
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);
    }

    /** @return array<string,mixed> */
    private function draft(): array
    {
        return [
            'questions' => [
                [
                    'question_text' => 'Kapan blok else dijalankan pada struktur if-else?',
                    'topic' => 'Percabangan Java',
                    'difficulty' => 'medium',
                    'explanation' => 'Else dijalankan ketika kondisi if bernilai false.',
                    'points' => 10,
                    'options' => ['Saat kondisi false', 'Saat kondisi true', 'Saat compile', 'Saat class dibuat'],
                    'correct_option' => 0,
                ],
                [
                    'question_text' => 'Apa fungsi utama struktur if pada Java?',
                    'topic' => 'Percabangan Java',
                    'difficulty' => 'medium',
                    'explanation' => 'If memilih eksekusi berdasarkan kondisi boolean.',
                    'points' => 10,
                    'options' => ['Memilih alur', 'Menghapus variabel', 'Membuat database', 'Menutup compiler'],
                    'correct_option' => 0,
                ],
            ],
            'provider' => 'groq',
            'model' => 'openai/gpt-oss-20b',
            'source_material' => null,
            'source_document' => null,
        ];
    }
}
