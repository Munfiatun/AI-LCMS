<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearningConcept;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuestionBankManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor1;

    protected User $instructor2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();

        $this->instructor1 = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        $this->instructor2 = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
    }

    public function test_instructor_can_create_question_bank(): void
    {
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->instructor1)->post(route('instructor.question-banks.store'), [
            'title' => 'Ujian Akhir Semester',
            'description' => 'Soal untuk UAS 2026',
        ]);

        $bank = QuestionBank::first();

        $response->assertRedirect(route('instructor.question-banks.show', $bank));

        $this->assertDatabaseHas('question_banks', [
            'title' => 'Ujian Akhir Semester',
            'instructor_id' => $this->instructor1->id,
        ]);
    }

    public function test_instructor_can_move_global_question_bank_to_owned_course(): void
    {
        $course = Course::factory()->create([
            'instructor_id' => $this->instructor1->id,
            'title' => 'PEMOGRAMAN DASAR',
        ]);
        $bank = QuestionBank::create([
            'title' => 'Uji Coba R6',
            'instructor_id' => $this->instructor1->id,
            'course_id' => null,
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->instructor1)
            ->get(route('instructor.question-banks.show', $bank))
            ->assertOk()
            ->assertSee('Global')
            ->assertSee('PEMOGRAMAN DASAR');

        $response = $this->actingAs($this->instructor1)->put(route('instructor.question-banks.update', $bank), [
            'title' => 'Uji Coba R6',
            'course_id' => $course->id,
            'description' => 'Formative C01',
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('question_banks', [
            'id' => $bank->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_instructor_cannot_assign_question_bank_to_another_instructors_course(): void
    {
        $foreignCourse = Course::factory()->create([
            'instructor_id' => $this->instructor2->id,
        ]);
        $bank = QuestionBank::create([
            'title' => 'Bank Soal A',
            'instructor_id' => $this->instructor1->id,
            'course_id' => null,
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->instructor1)->put(route('instructor.question-banks.update', $bank), [
            'title' => 'Bank Soal A',
            'course_id' => $foreignCourse->id,
            'description' => null,
            'status' => QuestionBank::STATUS_ACTIVE,
        ])->assertForbidden();

        $this->assertNull($bank->fresh()->course_id);
    }

    public function test_instructor_cannot_access_other_instructor_question_bank(): void
    {
        $bank = QuestionBank::create([
            'title' => 'Bank Soal A',
            'instructor_id' => $this->instructor1->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->instructor2)->get(route('instructor.question-banks.show', $bank));

        $response->assertStatus(403);
    }

    public function test_instructor_can_add_manual_question(): void
    {
        $bank = QuestionBank::create([
            'title' => 'Bank Soal A',
            'instructor_id' => $this->instructor1->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->instructor1)->post(route('instructor.question-banks.questions.store', $bank), [
            'question_text' => 'Apa ibukota Indonesia?',
            'correct_option' => 1,
            'options' => [
                0 => 'Surabaya',
                1 => 'Jakarta',
                2 => 'Bandung',
                3 => 'Medan',
            ],
            'difficulty' => 'easy',
            'points' => 15,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'question_bank_id' => $bank->id,
            'question_text' => 'Apa ibukota Indonesia?',
            'difficulty' => 'easy',
            'points' => 15,
        ]);

        $question = Question::where('question_text', 'Apa ibukota Indonesia?')->first();

        $this->assertDatabaseHas('question_options', [
            'question_id' => $question->id,
            'option_text' => 'Jakarta',
            'is_correct' => true,
        ]);

        $this->assertDatabaseHas('question_options', [
            'question_id' => $question->id,
            'option_text' => 'Surabaya',
            'is_correct' => false,
        ]);
    }

    public function test_instructor_can_prepare_formative_quiz_automatically_from_verified_bank(): void
    {
        $course = Course::factory()->create([
            'instructor_id' => $this->instructor1->id,
            'title' => 'PEMOGRAMAN DASAR',
        ]);
        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C01',
            'name' => 'Variabel dan Tipe Data',
            'order' => 1,
            'is_active' => true,
        ]);
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $this->instructor1->id,
            'course_id' => $course->id,
            'title' => 'Uji Coba R4.6',
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);

        $questions = Question::factory()->count(2)->create([
            'question_bank_id' => $bank->id,
            'status' => Question::STATUS_APPROVED,
            'needs_review' => false,
            'difficulty' => Question::DIFFICULTY_MEDIUM,
            'points' => 10,
        ]);

        foreach ($questions as $question) {
            QuestionOption::factory()->create([
                'question_id' => $question->id,
                'option_text' => 'Benar',
                'is_correct' => true,
            ]);
            QuestionOption::factory()->create([
                'question_id' => $question->id,
                'option_text' => 'Salah',
                'is_correct' => false,
            ]);
        }

        $response = $this->actingAs($this->instructor1)->post(
            route('instructor.question-banks.create-formative', $bank),
            ['learning_concept_id' => $concept->id]
        );

        $quiz = Quiz::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('instructor.quizzes.show', $quiz));
        $this->assertSame(Quiz::ASSESSMENT_FORMATIVE, $quiz->assessment_type);
        $this->assertSame('draft', $quiz->status);
        $this->assertSame(2, $quiz->total_questions);
        $this->assertSame(2, $quiz->quizQuestions()->count());

        foreach ($questions as $question) {
            $this->assertDatabaseHas('question_concepts', [
                'question_id' => $question->id,
                'learning_concept_id' => $concept->id,
            ]);
            $this->assertDatabaseHas('quiz_questions', [
                'quiz_id' => $quiz->id,
                'question_id' => $question->id,
            ]);
        }
    }

    public function test_instructor_can_upload_document_and_dispatch_job(): void
    {
        Storage::fake('private');
        Queue::fake();

        $bank = QuestionBank::create([
            'title' => 'Bank Soal A',
            'instructor_id' => $this->instructor1->id,
            'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('naskah_soal.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $uploadResponse = $this->actingAs($this->instructor1)->post(route('instructor.question-banks.upload-document', $bank), [
            'document' => $file,
        ]);

        $uploadResponse->assertRedirect();

        $this->assertDatabaseHas('question_documents', [
            'question_bank_id' => $bank->id,
            'original_name' => 'naskah-soal.docx',
        ]);
    }

    public function test_instructor_can_approve_needs_review_question(): void
    {
        $bank = QuestionBank::create([
            'title' => 'Bank Soal A',
            'instructor_id' => $this->instructor1->id,
            'status' => 'active',
        ]);

        $question = Question::create([
            'question_bank_id' => $bank->id,
            'question_text' => 'Berapa hasil 1+1?',
            'needs_review' => true,
            'answer_source' => 'inferred',
            'status' => 'review',
        ]);

        $response = $this->actingAs($this->instructor1)->post(route('instructor.questions.approve', $question));

        $response->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'needs_review' => false,
            'status' => 'approved',
        ]);
    }
}
