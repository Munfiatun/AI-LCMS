<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\AIContentService;
use App\Services\AI\AIQuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Phase3CAssessmentRefinementTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('unsafeExtractionCases')]
    public function test_ai_question_extraction_rejects_unsafe_answer_metadata_before_persisting(string $case): void
    {
        $teacher = $this->teacher();
        $bank = QuestionBank::factory()->create(['instructor_id' => $teacher->id]);
        $questions = $this->validExtractedQuestions();

        switch ($case) {
            case 'missing review flag':
                unset($questions[0]['needs_review']);
                break;
            case 'inferred without review':
                $questions[1]['needs_review'] = false;
                break;
            case 'multiple correct answers':
                $questions[0]['options'][1]['is_correct'] = true;
                break;
            case 'duplicate options':
                $questions[0]['options'][1]['option_text'] = $questions[0]['options'][0]['option_text'];
                break;
            case 'manual source from ai':
                $questions[0]['answer_source'] = Question::SOURCE_MANUAL;
                break;
        }

        $service = new AIQuestionService($this->aiReturning($questions));

        try {
            $service->extractQuestions($bank, 'Naskah soal valid untuk diekstraksi.');
            $this->fail('Unsafe AI extraction output must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('questions', 0);
            $this->assertDatabaseCount('question_options', 0);
        }
    }

    public static function unsafeExtractionCases(): array
    {
        return [
            ['missing review flag'],
            ['inferred without review'],
            ['multiple correct answers'],
            ['duplicate options'],
            ['manual source from ai'],
        ];
    }

    public function test_valid_explicit_and_inferred_ai_answers_keep_safe_review_states(): void
    {
        $teacher = $this->teacher();
        $bank = QuestionBank::factory()->create(['instructor_id' => $teacher->id]);
        $service = new AIQuestionService($this->aiReturning($this->validExtractedQuestions()));

        $created = $service->extractQuestions($bank, 'Naskah soal valid untuk diekstraksi.');

        $this->assertCount(2, $created);
        $explicit = $created->firstWhere('answer_source', Question::SOURCE_EXPLICIT);
        $inferred = $created->firstWhere('answer_source', Question::SOURCE_INFERRED);

        $this->assertNotNull($explicit);
        $this->assertFalse($explicit->needs_review);
        $this->assertSame(Question::STATUS_APPROVED, $explicit->status);
        $this->assertSame(4, $explicit->options()->count());
        $this->assertSame(1, $explicit->options()->where('is_correct', true)->count());

        $this->assertNotNull($inferred);
        $this->assertTrue($inferred->needs_review);
        $this->assertSame(Question::STATUS_REVIEW, $inferred->status);
        $this->assertSame(4, $inferred->options()->count());
        $this->assertSame(1, $inferred->options()->where('is_correct', true)->count());
    }

    public function test_teacher_correction_changes_answer_source_to_manual_and_verifies_question(): void
    {
        $teacher = $this->teacher();
        $bank = QuestionBank::factory()->create(['instructor_id' => $teacher->id]);
        $question = $bank->questions()->create([
            'question_text' => 'Planet terdekat dari Matahari?',
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'topic' => 'Tata Surya',
            'difficulty' => Question::DIFFICULTY_EASY,
            'explanation' => 'AI memperkirakan jawaban dari materi.',
            'points' => 10,
            'order' => 1,
            'needs_review' => true,
            'answer_source' => Question::SOURCE_INFERRED,
            'status' => Question::STATUS_REVIEW,
        ]);
        $question->options()->createMany([
            ['option_text' => 'Venus', 'is_correct' => true, 'order' => 1],
            ['option_text' => 'Merkurius', 'is_correct' => false, 'order' => 2],
            ['option_text' => 'Bumi', 'is_correct' => false, 'order' => 3],
            ['option_text' => 'Mars', 'is_correct' => false, 'order' => 4],
        ]);

        $this->actingAs($teacher)->put(route('instructor.questions.update', $question), [
            'question_text' => 'Planet terdekat dari Matahari?',
            'topic' => 'Tata Surya',
            'difficulty' => 'easy',
            'points' => 10,
            'explanation' => 'Merkurius adalah planet terdekat dari Matahari.',
            'options' => ['Venus', 'Merkurius', 'Bumi', 'Mars'],
            'correct_option' => 1,
        ])->assertSessionHas('success');

        $question->refresh();
        $this->assertFalse($question->needs_review);
        $this->assertSame(Question::SOURCE_MANUAL, $question->answer_source);
        $this->assertSame(Question::STATUS_APPROVED, $question->status);
        $this->assertSame('Merkurius', $question->correctOption()->firstOrFail()->option_text);
    }

    public function test_quiz_review_exposes_quality_gate_and_answer_provenance(): void
    {
        $teacher = $this->teacher();
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $bank = QuestionBank::factory()->create(['instructor_id' => $teacher->id, 'course_id' => $course->id]);
        $question = $bank->questions()->create([
            'question_text' => 'Planet terdekat dari Matahari?',
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'topic' => 'Source: Slide 1',
            'difficulty' => Question::DIFFICULTY_EASY,
            'explanation' => 'Merkurius paling dekat dengan Matahari.',
            'points' => 10,
            'order' => 1,
            'needs_review' => true,
            'answer_source' => Question::SOURCE_INFERRED,
            'status' => Question::STATUS_REVIEW,
        ]);
        $question->options()->createMany([
            ['option_text' => 'Merkurius', 'is_correct' => true, 'order' => 1],
            ['option_text' => 'Venus', 'is_correct' => false, 'order' => 2],
            ['option_text' => 'Bumi', 'is_correct' => false, 'order' => 3],
            ['option_text' => 'Mars', 'is_correct' => false, 'order' => 4],
        ]);
        $quiz = Quiz::factory()->create([
            'course_id' => $course->id,
            'status' => 'draft',
            'total_questions' => 1,
        ]);
        $quiz->quizQuestions()->create([
            'question_id' => $question->id,
            'points' => 10,
            'order' => 1,
        ]);

        $this->actingAs($teacher)
            ->get(route('instructor.quizzes.show', $quiz))
            ->assertOk()
            ->assertSee('Review & Verifikasi Soal', false)
            ->assertSee('1 soal masih perlu diverifikasi guru.')
            ->assertSee('Kunci hasil inferensi AI')
            ->assertSee('Source: Slide 1');
    }

    private function teacher(): User
    {
        $role = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);

        return User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    private function aiReturning(array $questions): AIContentService
    {
        $ai = Mockery::mock(AIContentService::class);
        $ai->shouldReceive('process')
            ->once()
            ->andReturn([
                'parsed_data' => [
                    'title' => 'Ekstraksi Assessment',
                    'questions' => $questions,
                ],
            ]);

        return $ai;
    }

    /** @return list<array<string, mixed>> */
    private function validExtractedQuestions(): array
    {
        return [
            [
                'order' => 1,
                'question_text' => 'Planet terdekat dari Matahari adalah?',
                'topic' => 'Tata Surya',
                'difficulty' => 'easy',
                'explanation' => 'Kunci tertulis eksplisit pada dokumen sumber.',
                'points' => 10,
                'answer_source' => Question::SOURCE_EXPLICIT,
                'needs_review' => false,
                'options' => [
                    ['order' => 1, 'option_text' => 'Merkurius', 'is_correct' => true],
                    ['order' => 2, 'option_text' => 'Venus', 'is_correct' => false],
                    ['order' => 3, 'option_text' => 'Bumi', 'is_correct' => false],
                    ['order' => 4, 'option_text' => 'Mars', 'is_correct' => false],
                ],
            ],
            [
                'order' => 2,
                'question_text' => 'Planet yang dikenal sebagai planet merah adalah?',
                'topic' => 'Tata Surya',
                'difficulty' => 'easy',
                'explanation' => 'AI menginferensikan jawaban berdasarkan konteks dokumen.',
                'points' => 10,
                'answer_source' => Question::SOURCE_INFERRED,
                'needs_review' => true,
                'options' => [
                    ['order' => 1, 'option_text' => 'Venus', 'is_correct' => false],
                    ['order' => 2, 'option_text' => 'Mars', 'is_correct' => true],
                    ['order' => 3, 'option_text' => 'Saturnus', 'is_correct' => false],
                    ['order' => 4, 'option_text' => 'Neptunus', 'is_correct' => false],
                ],
            ],
        ];
    }
}
