<?php

namespace Tests\Feature;

use App\Models\QuestionBank;
use App\Models\QuestionDocument;
use App\Models\Role;
use App\Models\User;
use App\Services\Document\DocumentService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Mockery\MockInterface;
use Tests\TestCase;

class AIQuestionAuthoringDocumentSourceTest extends TestCase
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

    public function test_teacher_can_upload_pdf_as_grounding_for_question_draft(): void
    {
        $bank = $this->bank();
        $document = $this->document($bank, 'modul-percabangan.pdf');

        $this->mock(DocumentService::class, function (MockInterface $mock) use ($document): void {
            $mock->shouldReceive('storeQuestionDocument')->once()->andReturn($document);
            $mock->shouldReceive('extractTextFromQuestionDocument')->once()->with($document)->andReturn(
                'Percabangan if-else memilih satu dari dua alur berdasarkan nilai boolean kondisi.'
            );
        });

        $response = $this->actingAs($this->instructor)
            ->post(route('instructor.question-banks.ai-authoring.generate', $bank), [
                'topic' => 'Percabangan Java',
                'total_questions' => 3,
                'difficulty' => 'medium',
                'instructions' => 'Buat soal penerapan untuk siswa kelas X RPL.',
                'source_document' => UploadedFile::fake()->create('modul-percabangan.pdf', 50, 'application/pdf'),
            ]);

        $response->assertRedirect(route('instructor.question-banks.ai-authoring.review', $bank));
        $response->assertSessionHas('ai_question_authoring.'.$bank->id.'.source_document.name', 'modul-percabangan.pdf');
        $response->assertSessionHas('ai_question_authoring.'.$bank->id.'.settings.source_document_id', $document->id);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_document_from_another_question_bank_cannot_be_used_as_grounding(): void
    {
        $bank = $this->bank();
        $otherBank = $this->bank();
        $foreignDocument = $this->document($otherBank, 'rahasia-kursus-lain.docx');

        $this->actingAs($this->instructor)
            ->post(route('instructor.question-banks.ai-authoring.generate', $bank), [
                'topic' => 'Percabangan Java',
                'total_questions' => 3,
                'difficulty' => 'medium',
                'source_document_id' => $foreignDocument->id,
            ])
            ->assertNotFound();
    }

    public function test_teacher_must_choose_only_one_grounding_source(): void
    {
        $bank = $this->bank();
        $document = $this->document($bank, 'modul.docx');

        $this->actingAs($this->instructor)
            ->from(route('instructor.question-banks.ai-authoring.create', $bank))
            ->post(route('instructor.question-banks.ai-authoring.generate', $bank), [
                'topic' => 'Percabangan Java',
                'total_questions' => 3,
                'difficulty' => 'medium',
                'source_document_id' => $document->id,
                'source_document' => UploadedFile::fake()->create('modul-baru.pdf', 50, 'application/pdf'),
            ])
            ->assertRedirect(route('instructor.question-banks.ai-authoring.create', $bank))
            ->assertSessionHasErrors('source');
    }

    private function bank(): QuestionBank
    {
        return QuestionBank::factory()->create([
            'instructor_id' => $this->instructor->id,
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);
    }

    private function document(QuestionBank $bank, string $name): QuestionDocument
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);

        return QuestionDocument::create([
            'question_bank_id' => $bank->id,
            'original_name' => $name,
            'stored_name' => 'stored-'.$name,
            'disk' => 'private',
            'path' => 'question_banks/'.$bank->id.'/stored-'.$name,
            'mime_type' => $extension === 'pdf'
                ? 'application/pdf'
                : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'extension' => $extension,
            'size' => 1024,
            'uploaded_by' => $this->instructor->id,
        ]);
    }
}
