<?php

namespace Tests\Feature;

use App\Models\AIProcessingLog;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIQuestionExtractionStatusTest extends TestCase
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

    public function test_review_reports_queued_state_instead_of_false_verified_message(): void
    {
        $bank = $this->bank();

        $this->actingAs($this->instructor)
            ->get(route('instructor.question-banks.review', [
                'question_bank' => $bank,
                'processing' => 1,
                'started_at' => now()->timestamp,
            ]))
            ->assertOk()
            ->assertSee('Menunggu Queue Worker')
            ->assertDontSee('Semua Soal Telah Diverifikasi');
    }

    public function test_completed_bank_without_manual_review_redirects_to_actual_bank(): void
    {
        $bank = $this->bank();

        Question::factory()->create([
            'question_bank_id' => $bank->id,
            'needs_review' => false,
            'status' => Question::STATUS_APPROVED,
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.question-banks.review', $bank))
            ->assertRedirect(route('instructor.question-banks.show', $bank));
    }

    public function test_questions_that_need_review_still_open_review_workspace(): void
    {
        $bank = $this->bank();

        Question::factory()->create([
            'question_bank_id' => $bank->id,
            'needs_review' => true,
            'status' => Question::STATUS_REVIEW,
            'answer_source' => Question::SOURCE_INFERRED,
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.question-banks.review', $bank))
            ->assertOk()
            ->assertSee('Review Soal Hasil Ekstraksi AI')
            ->assertSee('Terdapat')
            ->assertSee('1 soal');
    }

    public function test_failed_extraction_is_reported_as_failure(): void
    {
        $bank = $this->bank();
        $startedAt = now()->timestamp;

        AIProcessingLog::create([
            'process_type' => AIProcessingLog::PROCESS_QUESTION_EXTRACTION,
            'source_type' => QuestionBank::class,
            'source_id' => $bank->id,
            'provider' => 'mock',
            'model' => 'mock',
            'prompt_version' => 'v1.0',
            'input_hash' => hash('sha256', 'failed-extraction'),
            'status' => AIProcessingLog::STATUS_FAILED,
            'error_code' => 'processing_error',
            'error_message' => 'Proses AI gagal. Silakan coba kembali.',
            'attempt_count' => 1,
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.question-banks.review', [
                'question_bank' => $bank,
                'processing' => 1,
                'started_at' => $startedAt,
            ]))
            ->assertOk()
            ->assertSee('Ekstraksi AI Gagal')
            ->assertDontSee('Semua Soal Telah Diverifikasi');
    }

    private function bank(): QuestionBank
    {
        return QuestionBank::factory()->create([
            'instructor_id' => $this->instructor->id,
        ]);
    }
}
