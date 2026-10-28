<?php

namespace Tests\Feature;

use App\Models\AIProcessingLog;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AIQuestionAuthoringTest extends TestCase
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

        Config::set('ai.provider', 'mock');
    }

    public function test_instructor_can_open_ai_question_authoring_for_own_bank(): void
    {
        $bank = $this->bank($this->instructor);

        $this->actingAs($this->instructor)
            ->get(route('instructor.question-banks.ai-authoring.create', $bank))
            ->assertOk()
            ->assertSee('Buat Soal dengan AI')
            ->assertSee('AI Authoring Assistant');
    }

    public function test_instructor_cannot_open_ai_question_authoring_for_other_bank(): void
    {
        $bank = $this->bank($this->otherInstructor);

        $this->actingAs($this->instructor)
            ->get(route('instructor.question-banks.ai-authoring.create', $bank))
            ->assertForbidden();
    }

    public function test_generate_creates_review_draft_without_persisting_questions(): void
    {
        $bank = $this->bank($this->instructor);

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.question-banks.ai-authoring.generate', $bank), [
                'topic' => 'Percabangan Java',
                'total_questions' => 3,
                'difficulty' => 'medium',
                'instructions' => 'Gunakan konteks siswa kelas X RPL.',
            ]);

        $response->assertRedirect(route('instructor.question-banks.ai-authoring.review', $bank));
        $response->assertSessionHas('ai_question_authoring.'.$bank->id);
        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseHas('ai_processing_logs', [
            'process_type' => AIProcessingLog::PROCESS_QUESTION_AUTHORING,
            'source_type' => QuestionBank::class,
            'source_id' => $bank->id,
            'status' => AIProcessingLog::STATUS_COMPLETED,
        ]);
    }

    public function test_teacher_can_review_and_save_ai_draft_to_question_bank(): void
    {
        $bank = $this->bank($this->instructor);

        $this->actingAs($this->instructor)
            ->post(route('instructor.question-banks.ai-authoring.generate', $bank), [
                'topic' => 'Percabangan Java',
                'total_questions' => 3,
                'difficulty' => 'medium',
                'instructions' => 'Gunakan konteks sederhana.',
            ])
            ->assertRedirect(route('instructor.question-banks.ai-authoring.review', $bank));

        $this->actingAs($this->instructor)
            ->get(route('instructor.question-banks.ai-authoring.review', $bank))
            ->assertOk()
            ->assertSee('Review Draft Soal AI')
            ->assertSee('AI Draft');

        $questions = [
            [
                'question_text' => 'Apa tujuan utama penggunaan percabangan dalam program?',
                'topic' => 'Percabangan Java',
                'difficulty' => 'medium',
                'explanation' => 'Percabangan menentukan blok instruksi yang dijalankan berdasarkan kondisi.',
                'points' => 10,
                'options' => ['Memilih alur berdasarkan kondisi', 'Menghapus semua variabel', 'Membuat database', 'Mengganti compiler'],
                'correct_option' => 0,
            ],
            [
                'question_text' => 'Kapan blok else pada struktur if-else dijalankan?',
                'topic' => 'Percabangan Java',
                'difficulty' => 'medium',
                'explanation' => 'Blok else dijalankan ketika kondisi pada if bernilai false.',
                'points' => 10,
                'options' => ['Saat kondisi if false', 'Saat program dikompilasi', 'Saat variabel dibuat', 'Saat class dihapus'],
                'correct_option' => 0,
            ],
            [
                'question_text' => 'Struktur mana yang tepat untuk memilih satu dari beberapa kondisi berurutan?',
                'topic' => 'Percabangan Java',
                'difficulty' => 'medium',
                'explanation' => 'Rangkaian if-else if dapat mengevaluasi beberapa kondisi secara berurutan.',
                'points' => 10,
                'options' => ['if-else if', 'for', 'while', 'do-while'],
                'correct_option' => 0,
            ],
        ];

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.question-banks.ai-authoring.store', $bank), [
                'questions' => $questions,
            ]);

        $response->assertRedirect(route('instructor.question-banks.show', $bank));
        $response->assertSessionMissing('ai_question_authoring.'.$bank->id);
        $this->assertDatabaseCount('questions', 3);
        $this->assertDatabaseHas('questions', [
            'question_bank_id' => $bank->id,
            'question_text' => $questions[0]['question_text'],
            'answer_source' => Question::SOURCE_INFERRED,
            'needs_review' => false,
            'status' => Question::STATUS_APPROVED,
        ]);

        $saved = Question::where('question_bank_id', $bank->id)->firstOrFail();
        $this->assertCount(4, $saved->options);
        $this->assertSame(1, $saved->options->where('is_correct', true)->count());
    }

    public function test_discard_draft_does_not_create_questions(): void
    {
        $bank = $this->bank($this->instructor);
        $sessionKey = 'ai_question_authoring.'.$bank->id;

        $response = $this->actingAs($this->instructor)
            ->withSession([$sessionKey => [
                'questions' => [['question_text' => 'Temporary draft']],
                'settings' => [],
            ]])
            ->delete(route('instructor.question-banks.ai-authoring.destroy', $bank));

        $response->assertRedirect(route('instructor.question-banks.show', $bank));
        $response->assertSessionMissing($sessionKey);
        $this->assertDatabaseCount('questions', 0);
    }

    private function bank(User $instructor): QuestionBank
    {
        return QuestionBank::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);
    }
}
