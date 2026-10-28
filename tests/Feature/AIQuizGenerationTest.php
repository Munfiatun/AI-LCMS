<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AIQuizGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.provider' => 'openai', 'ai.providers.openai.api_key' => 'test-key', 'ai.providers.openai.base_url' => 'https://api.openai.com/v1']);
        Http::preventStrayRequests();
    }

    public function test_generates_reviewable_draft_from_selected_slides_in_order_without_changing_source(): void
    {
        [$teacher, $slidebook] = $this->source();
        $before = $slidebook->fresh()->toArray();
        $slidesBefore = $slidebook->slides()->get()->toArray();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response($this->response($this->generatedQuestions()))]);

        $response = $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $this->payload($slidebook));

        $quiz = Quiz::sole();
        $response->assertRedirect(route('instructor.quizzes.show', $quiz));
        $this->assertSame('draft', $quiz->status);
        $this->assertNull($quiz->published_at);
        $question = $quiz->questions()->firstOrFail();
        $this->assertTrue($question->needs_review);
        $this->assertSame('review', $question->status);
        $this->assertSame('Source: Slide 1', $question->topic);
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());
        $this->assertSame($before, $slidebook->fresh()->toArray());
        $this->assertSame($slidesBefore, $slidebook->slides()->get()->toArray());
        Http::assertSent(function ($request): bool {
            $source = json_decode($request['messages'][1]['content'], true);

            return $source['title'] === 'Tata Surya'
                && array_column($source['slides'], 'number') === [1, 2]
                && str_contains($source['slides'][0]['text'], 'Merkurius paling dekat dengan Matahari.')
                && ! str_contains($request['messages'][1]['content'], 'MATERI DI LUAR SLIDEBOOK')
                && $request['response_format']['type'] === 'json_schema'
                && $request['response_format']['json_schema']['strict'] === true;
        });
        $this->get(route('instructor.quizzes.show', $quiz))->assertSee('AI Generated Draft')->assertSee('Review & Verifikasi Soal');
        $this->get(route('instructor.quizzes.index'))->assertSee('Buat Kuis dengan Bantuan AI');
    }

    public function test_repeated_request_creates_only_one_quiz(): void
    {
        [$teacher, $slidebook] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response($this->response($this->generatedQuestions()))]);
        $payload = $this->payload($slidebook);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $payload)->assertSessionHas('success');
        $this->post(route('instructor.quizzes.generate-ai'), $payload)->assertRedirect(route('instructor.quizzes.show', Quiz::sole()));
        $this->assertDatabaseCount('quizzes', 1);
        $this->assertDatabaseCount('question_banks', 1);
        Http::assertSentCount(1);
    }

    public function test_concurrent_generation_is_rejected(): void
    {
        [$teacher, $slidebook] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response([])]);
        $lock = Cache::lock('quiz-generation:slidebook:'.$slidebook->id, 180);
        $lock->get();
        try {
            $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $this->payload($slidebook))
                ->assertSessionHas('error', 'Quiz sedang dibuat. Tunggu hingga proses selesai.');
            $this->assertDatabaseCount('quizzes', 0);
            Http::assertNothingSent();
        } finally {
            $lock->release();
        }
    }

    #[DataProvider('invalidOutputs')]
    public function test_invalid_ai_output_never_creates_questions(string $field, mixed $value): void
    {
        [$teacher, $slidebook] = $this->source();
        $output = $this->generatedQuestions();
        data_set($output, $field, $value);
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response($this->response($output))]);
        $before = $slidebook->slides()->get()->toArray();

        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $this->payload($slidebook))->assertSessionHasErrors('quiz');

        $this->assertDatabaseCount('quizzes', 0);
        $this->assertDatabaseCount('question_banks', 0);
        $this->assertDatabaseCount('questions', 0);
        $this->assertSame($before, $slidebook->slides()->get()->toArray());
        Http::assertSentCount(2);
    }

    public static function invalidOutputs(): array
    {
        return [
            'wrong count' => ['questions', []],
            'empty text' => ['questions.0.question_text', '   '],
            'wrong difficulty' => ['questions.0.difficulty', 'hard'],
            'answer index out of range' => ['questions.0.correct_option', 4],
            'negative answer index' => ['questions.0.correct_option', -1],
            'non integer answer index' => ['questions.0.correct_option', '1'],
            'duplicate options' => ['questions.0.options.0', 'Merkurius'],
            'missing option' => ['questions.0.options.0', ''],
            'too few options' => ['questions.0.options', ['Venus', 'Merkurius', 'Bumi']],
            'wrong source' => ['questions.0.source_slide_number', 99],
        ];
    }

    public function test_invalid_output_is_retried_once_and_valid_retry_is_saved(): void
    {
        [$teacher, $slidebook] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::sequence()->push($this->response(['questions' => []]))->push($this->response($this->generatedQuestions()))]);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $this->payload($slidebook))->assertSessionHas('success');
        $this->assertDatabaseCount('questions', 2);
        Http::assertSentCount(2);
    }

    public function test_empty_slidebook_is_rejected_before_calling_ai(): void
    {
        [$teacher, $slidebook] = $this->source();
        $slidebook->slides()->delete();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response([])]);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $this->payload($slidebook))
            ->assertSessionHasErrors(['slidebook_id' => 'Materi Slidebook belum cukup untuk membuat Quiz.']);
        $this->assertDatabaseCount('quizzes', 0);
        Http::assertNothingSent();
    }

    public function test_provider_failure_preserves_slidebook_and_allows_retry(): void
    {
        [$teacher, $slidebook] = $this->source();
        $before = $slidebook->fresh()->toArray();
        $slidesBefore = $slidebook->slides()->get()->toArray();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::sequence()->push(['error' => 'private-provider-error'], 500)->push($this->response($this->generatedQuestions()))]);
        $payload = $this->payload($slidebook);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $payload)
            ->assertSessionHas('error');
        $this->assertDatabaseCount('quizzes', 0);
        $this->assertSame($before, $slidebook->fresh()->toArray());
        $this->assertSame($slidesBefore, $slidebook->slides()->get()->toArray());
        $this->post(route('instructor.quizzes.generate-ai'), $payload)->assertSessionHas('success');
        Http::assertSentCount(2);
    }

    public function test_timeout_returns_safe_message(): void
    {
        [$teacher, $slidebook] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::failedConnection()]);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $this->payload($slidebook))
            ->assertSessionHas('error', 'Proses pembuatan Quiz memerlukan waktu terlalu lama. Silakan coba lagi.');
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_generation_requires_owner_and_teacher_role(): void
    {
        [$teacher, $slidebook] = $this->source();
        $other = $this->user('instructor');
        $student = $this->user('student');
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response([])]);
        $payload = $this->payload($slidebook);
        $this->post(route('instructor.quizzes.generate-ai'), $payload)->assertRedirect(route('login'));
        $this->actingAs($other)->post(route('instructor.quizzes.generate-ai'), $payload)->assertForbidden();
        $this->actingAs($student)->post(route('instructor.quizzes.generate-ai'), $payload)->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseCount('quizzes', 0);
        Http::assertNothingSent();
    }

    public function test_teacher_reviews_edits_adds_and_removes_questions_before_finalizing(): void
    {
        [$teacher, $slidebook] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response($this->response($this->generatedQuestions()))]);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $this->payload($slidebook));
        $quiz = Quiz::sole();
        $question = $quiz->questions()->firstOrFail();
        $student = $this->user('student');
        CourseEnrollment::create(['course_id' => $quiz->course_id, 'student_id' => $student->id, 'status' => 'active', 'progress_percentage' => 0]);
        $this->actingAs($student)->get(route('student.quizzes.show', $quiz))->assertForbidden();
        $this->post(route('student.quizzes.start', $quiz))->assertForbidden();
        $this->actingAs($teacher)->post(route('instructor.quizzes.publish', $quiz))->assertSessionHasErrors('quiz');
        $edit = ['question_text' => 'Planet terdekat dari Matahari?', 'options' => ['Venus', 'Merkurius', 'Bumi', 'Mars'], 'correct_option' => 1, 'difficulty' => 'easy', 'points' => 10, 'explanation' => 'Pembahasan privat guru'];
        $this->put(route('instructor.questions.update', $question), $edit)->assertSessionHas('success');
        $this->assertFalse($question->fresh()->needs_review);
        $this->assertSame($edit['question_text'], $question->fresh()->question_text);
        $this->post(route('instructor.question-banks.questions.store', $question->question_bank_id), [...$edit, 'question_text' => 'Soal manual guru'])->assertSessionHas('success');
        $manual = Question::latest('id')->first();
        $this->post(route('instructor.quizzes.sync-questions', $quiz), ['questions' => [['id' => $manual->id, 'points' => 10, 'order' => 1], ['id' => $question->id, 'points' => 10, 'order' => 2]]])->assertSessionHas('success');
        $this->assertSame(2, $quiz->quizQuestions()->count());
        $this->get(route('instructor.quizzes.builder', $quiz))->assertOk();
        $this->post(route('instructor.quizzes.sync-questions', $quiz), ['questions' => [['id' => $question->id, 'points' => 10, 'order' => 1]]])->assertSessionHas('success');
        $this->assertSame(1, $quiz->quizQuestions()->count());
        $this->delete(route('instructor.questions.destroy', $manual))->assertSessionHas('success');
        $this->assertModelMissing($manual);
        $this->post(route('instructor.quizzes.publish', $quiz))->assertSessionHas('success');
        $this->assertSame('published', $quiz->fresh()->status);
        $this->actingAs($student)->get(route('student.quizzes.show', $quiz))->assertOk()->assertDontSee('Pembahasan privat guru');
        $this->post(route('student.quizzes.start', $quiz))->assertRedirect();
        $attempt = $quiz->attempts()->sole();
        $params = ['quiz' => $quiz, 'attempt' => $attempt];
        $this->get(route('student.quizzes.take', $params))->assertOk()->assertDontSee('Pembahasan privat guru')->assertDontSee('is_correct');
        $this->get(route('student.quizzes.result', $params))->assertForbidden();
        Http::assertSentCount(1);
    }

    public function test_invalid_manual_answer_is_rejected_and_invalid_quiz_cannot_publish(): void
    {
        [$teacher, $slidebook] = $this->source();
        $bank = QuestionBank::factory()->create(['instructor_id' => $teacher->id]);
        $question = Question::factory()->create(['question_bank_id' => $bank->id]);
        $question->options()->createMany([
            ['option_text' => 'A', 'is_correct' => false, 'order' => 1],
            ['option_text' => 'B', 'is_correct' => false, 'order' => 2],
        ]);
        $quiz = Quiz::factory()->create(['course_id' => $slidebook->material->section->course_id, 'status' => 'draft', 'total_questions' => 1]);
        $quiz->quizQuestions()->create(['question_id' => $question->id, 'points' => 10, 'order' => 1]);
        $this->actingAs($teacher)->put(route('instructor.questions.update', $question), ['question_text' => 'Test', 'options' => ['A', 'B'], 'correct_option' => 9, 'difficulty' => 'easy', 'points' => 10])->assertSessionHasErrors('correct_option');
        $this->post(route('instructor.quizzes.publish', $quiz))->assertSessionHasErrors('quiz');
        $this->assertSame('draft', $quiz->fresh()->status);
    }

    public function test_gemini_generates_true_false_with_structured_schema(): void
    {
        [$teacher, $slidebook] = $this->source();
        config(['ai.provider' => 'gemini', 'ai.providers.gemini.api_key' => 'test-key', 'ai.providers.gemini.model' => 'gemini-2.5-flash']);
        $output = $this->generatedQuestions();
        foreach ($output['questions'] as &$question) {
            $question['options'] = ['Benar', 'Salah'];
            $question['correct_option'] = 0;
        }
        unset($question);
        Http::fake(['https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode($output)]]]]]])]);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), [...$this->payload($slidebook), 'type' => 'true_false', 'difficulty' => 'mixed'])->assertSessionHas('success');
        $this->assertSame('true_false', Question::firstOrFail()->type);
        $this->assertSame(2, Question::firstOrFail()->options()->count());
        Http::assertSent(fn ($request): bool => $request['generationConfig']['responseJsonSchema']['title'] === 'quiz_generation');
    }

    public function test_duplicate_ai_questions_are_rejected(): void
    {
        [$teacher, $slidebook] = $this->source();
        $output = $this->generatedQuestions();
        $output['questions'][1] = $output['questions'][0];
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response($this->response($output))]);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), [...$this->payload($slidebook), 'total_questions' => 2])->assertSessionHasErrors('quiz');
        $this->assertDatabaseCount('questions', 0);
        Http::assertSentCount(2);
    }

    public function test_student_material_page_lists_only_published_quizzes(): void
    {
        [$teacher, $slidebook] = $this->source();
        $student = $this->user('student');
        $course = $slidebook->material->section->course;
        $course->enrollments()->create(['student_id' => $student->id, 'status' => 'active']);
        Quiz::factory()->create(['course_id' => $course->id, 'title' => 'Draft privat guru', 'status' => 'draft']);
        Quiz::factory()->create(['course_id' => $course->id, 'title' => 'Quiz final siswa', 'status' => 'published']);
        $this->actingAs($student)->get(route('student.materials.show', ['course' => $course, 'material' => $slidebook->material]))
            ->assertSee('Quiz final siswa')->assertDontSee('Draft privat guru');
    }

    public function test_teacher_cannot_attach_another_teachers_questions(): void
    {
        [$teacher, $slidebook] = $this->source();
        $other = $this->user('instructor');
        $bank = QuestionBank::factory()->create(['instructor_id' => $other->id]);
        $question = Question::factory()->create(['question_bank_id' => $bank->id]);
        $quiz = Quiz::factory()->create(['course_id' => $slidebook->material->section->course_id, 'status' => 'draft']);
        $this->actingAs($teacher)->post(route('instructor.quizzes.sync-questions', $quiz), ['questions' => [['id' => $question->id, 'order' => 1, 'points' => 10]]])->assertSessionHasErrors('questions.0.id');
        $this->assertSame(0, $quiz->quizQuestions()->count());
    }

    public function test_draft_attempts_and_mismatched_quiz_attempts_are_inaccessible(): void
    {
        [$teacher, $slidebook] = $this->source();
        $student = $this->user('student');
        CourseEnrollment::create(['course_id' => $slidebook->material->section->course_id, 'student_id' => $student->id, 'status' => 'active', 'progress_percentage' => 0]);
        $quiz = Quiz::factory()->create(['course_id' => $slidebook->material->section->course_id, 'status' => 'draft']);
        $otherQuiz = Quiz::factory()->create(['course_id' => $quiz->course_id, 'status' => 'published']);
        $attempt = $quiz->attempts()->create(['student_id' => $student->id, 'status' => 'in_progress', 'started_at' => now(), 'expires_at' => now()->addHour()]);
        $params = ['quiz' => $quiz, 'attempt' => $attempt];
        $this->actingAs($student)->get(route('student.quizzes.take', $params))->assertForbidden();
        $this->post(route('student.quizzes.submit', $params))->assertForbidden();
        $this->get(route('student.quizzes.result', $params))->assertForbidden();
        $params['quiz'] = $otherQuiz;
        $this->get(route('student.quizzes.take', $params))->assertNotFound();
        $this->post(route('student.quizzes.submit', $params))->assertNotFound();
        $this->get(route('student.quizzes.result', $params))->assertNotFound();
        $this->assertSame('in_progress', $attempt->fresh()->status);
    }

    public function test_generation_settings_and_request_token_are_validated(): void
    {
        [$teacher, $slidebook] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response([])]);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), [
            ...$this->payload($slidebook), 'total_questions' => 0, 'difficulty' => 'impossible', 'type' => 'essay', 'request_id' => 'invalid',
        ])->assertSessionHasErrors(['total_questions', 'difficulty', 'type', 'request_id']);
        $this->assertDatabaseCount('quizzes', 0);
        Http::assertNothingSent();
    }

    public function test_empty_teacher_prompt_is_rejected_without_calling_ai(): void
    {
        [$teacher, $slidebook] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response([])]);
        $payload = $this->payload($slidebook);
        $payload['custom_instructions'] = '';
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $payload)
            ->assertSessionHasErrors(['custom_instructions']);
        $this->assertDatabaseCount('quizzes', 0);
        Http::assertNothingSent();
    }

    #[DataProvider('validQuestionCounts')]
    public function test_requested_question_count_reaches_provider_and_is_saved_exactly(int $count, string $provider): void
    {
        [$teacher, $slidebook] = $this->source();
        config(['ai.provider' => $provider, 'ai.providers.groq.api_key' => 'test-key', 'ai.providers.gemini.api_key' => 'test-key', 'ai.providers.gemini.model' => 'gemini-2.5-flash']);
        $lessons = json_decode(file_get_contents(database_path('seeders/web_learning_questions.json')), true, flags: JSON_THROW_ON_ERROR);
        $items = array_slice($lessons[0]['questions'], 0, $count);
        $slidebook->slides()->where('order', 1)->update(['content' => implode("\n", array_column($items, 'explanation'))]);
        $questions = array_map(fn (array $item): array => [
            'question_text' => $item['question'], 'difficulty' => 'easy',
            'explanation' => $item['explanation'], 'source_slide_number' => 1,
            'options' => [$item['correct'], ...$item['distractors']],
            'correct_option' => 0,
        ], $items);
        $endpoint = match ($provider) {
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'gemini' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
            default => 'https://api.openai.com/v1/chat/completions',
        };
        $response = $provider === 'gemini'
            ? ['candidates' => [['content' => ['parts' => [['text' => json_encode(['questions' => $questions])]]]]]]
            : $this->response(['questions' => $questions]);
        Http::fake([$endpoint => Http::response($response)]);
        $instructions = 'Fokus pada konsep utama dan jangan membuat soal duplikat.';

        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), [
            ...$this->payload($slidebook), 'total_questions' => $count, 'custom_instructions' => $instructions,
        ])->assertRedirect(route('instructor.quizzes.show', Quiz::sole()));

        $quiz = Quiz::sole();
        $this->assertSame($count, $quiz->questions()->count());
        $this->assertSame($count, (int) $quiz->total_questions);
        $this->assertSame('draft', $quiz->status);
        $this->assertSame($count * 4, QuestionOption::count());
        Http::assertSent(function ($request) use ($provider, $count, $instructions): bool {
            $prompt = $provider === 'gemini' ? $request['contents'][0]['parts'][0]['text'] : $request['messages'][0]['content'];
            $source = $provider === 'gemini' ? $prompt : $request['messages'][1]['content'];

            return str_contains($prompt, "Generate exactly {$count} quiz questions.")
                && str_contains($prompt, $instructions)
                && str_contains($source, '"total_questions":'.$count);
        });
        Http::assertSentCount(1);
    }

    /** @return array<string, array{int, string}> */
    public static function validQuestionCounts(): array
    {
        return ['2 OpenAI' => [2, 'openai'], '5 OpenAI' => [5, 'openai'], '10 OpenAI' => [10, 'openai'], '20 OpenAI' => [20, 'openai'], '20 Groq' => [20, 'groq'], '20 Gemini' => [20, 'gemini']];
    }

    #[DataProvider('invalidQuestionCounts')]
    public function test_out_of_range_question_count_never_calls_provider(mixed $count): void
    {
        [$teacher, $slidebook] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response([])]);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), [...$this->payload($slidebook), 'total_questions' => $count])
            ->assertSessionHasErrors(['total_questions' => 'Jumlah soal harus antara 2 sampai 20.']);
        $this->assertDatabaseCount('quizzes', 0);
        Http::assertNothingSent();
    }

    /** @return array<string, array{mixed}> */
    public static function invalidQuestionCounts(): array
    {
        return ['zero' => [0], 'one' => [1], '21' => [21], '50' => [50], 'negative' => [-1], 'empty' => [''], 'missing' => [null], 'text' => ['abc'], 'decimal' => [2.5]];
    }

    public function test_twenty_requested_questions_are_not_silently_reduced_to_two(): void
    {
        [$teacher, $slidebook] = $this->source();
        Http::fake(['https://api.openai.com/v1/chat/completions' => Http::response($this->response($this->generatedQuestions()))]);
        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), [...$this->payload($slidebook), 'total_questions' => 20])
            ->assertSessionHasErrors('quiz');
        $this->assertDatabaseCount('questions', 0);
        $this->assertDatabaseCount('quizzes', 0);
        Http::assertSentCount(2);
    }

    public function test_groq_request_sizes_output_budget_by_question_count(): void
    {
        [$teacher, $slidebook] = $this->source();
        config(['ai.provider' => 'groq', 'ai.providers.groq.api_key' => 'test-key', 'ai.providers.groq.model' => 'openai/gpt-oss-20b', 'ai.providers.groq.max_completion_tokens' => 16384]);
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::response($this->response($this->generatedQuestions()))]);

        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), $this->payload($slidebook))->assertSessionHas('success');

        $this->assertSame(2, Quiz::sole()->questions()->count());
        $this->assertSame(['Venus', 'Merkurius', 'Bumi', 'Mars'], Quiz::sole()->questions()->orderBy('order')->first()->options()->orderBy('order')->pluck('option_text')->all());
        $this->assertSame('Merkurius', Quiz::sole()->questions()->orderBy('order')->first()->options()->where('is_correct', true)->value('option_text'));
        Http::assertSent(fn ($request): bool => $request['max_completion_tokens'] === 4096
            && $request['reasoning_effort'] === 'low'
            && $request['response_format']['type'] === 'json_object'
            && ! str_contains($request['messages'][0]['content'], 'source_quote'));
    }

    public function test_groq_truncated_json_shows_quiz_error_without_saving_dummy_questions(): void
    {
        [$teacher, $slidebook] = $this->source();
        config(['ai.provider' => 'groq', 'ai.providers.groq.api_key' => 'test-key']);
        Http::fake(['https://api.groq.com/openai/v1/chat/completions' => Http::response(['error' => [
            'message' => 'Failed to generate JSON.', 'type' => 'invalid_request_error', 'code' => 'json_validate_failed',
            'failed_generation' => 'max completion tokens reached before generating a valid document',
        ]], 400)]);

        $this->actingAs($teacher)->post(route('instructor.quizzes.generate-ai'), [...$this->payload($slidebook), 'total_questions' => 20])
            ->assertSessionHas('error', 'Gagal membuat quiz dengan AI. Silakan coba lagi.');

        $this->assertDatabaseCount('quizzes', 0);
        $this->assertDatabaseCount('questions', 0);
        Http::assertSent(fn ($request): bool => $request['max_completion_tokens'] === 20 * 250 + 3072);
    }

    /** @return array{User, Slidebook} */
    private function source(): array
    {
        $teacher = $this->user('instructor');
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id, 'content' => 'MATERI DI LUAR SLIDEBOOK']);
        $slidebook = Slidebook::create(['material_id' => $material->id, 'title' => 'Tata Surya', 'created_by' => $teacher->id, 'status' => 'published']);
        $slidebook->slides()->createMany([
            ['title' => 'Bumi', 'content' => 'Bumi mengelilingi Matahari.', 'order' => 2],
            ['title' => 'Planet', 'content' => '<p>Merkurius paling dekat dengan Matahari.</p>', 'order' => 1],
        ]);

        return [$teacher, $slidebook];
    }

    private function user(string $role): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role], ['label' => ucfirst($role)]);

        return User::factory()->create(['role_id' => $roleModel->id, 'is_active' => true]);
    }

    /** @return array<string, mixed> */
    private function payload(Slidebook $slidebook): array
    {
        return ['slidebook_id' => $slidebook->id, 'total_questions' => 2, 'difficulty' => 'easy', 'type' => 'multiple_choice', 'request_id' => (string) Str::uuid(), 'custom_instructions' => 'Buatkan 2 soal pilihan ganda tingkat mudah.'];
    }

    /** @return array<string, mixed> */
    private function generatedQuestions(): array
    {
        $output = ['questions' => [[
            'question_text' => 'Planet manakah yang paling dekat dengan Matahari?',
            'options' => ['Venus', 'Merkurius', 'Bumi', 'Mars'],
            'correct_option' => 1,
            'difficulty' => 'easy',
            'explanation' => 'Merkurius paling dekat dengan Matahari.',
            'source_slide_number' => 1,
        ]]];
        $output['questions'][] = [
            ...$output['questions'][0],
            'question_text' => 'Apa yang dikelilingi Bumi?',
            'options' => ['Matahari', 'Mars', 'Venus', 'Merkurius'],
            'correct_option' => 0,
            'explanation' => 'Bumi mengelilingi Matahari.',
            'source_slide_number' => 2,
        ];

        return $output;
    }

    /** @param array<string, mixed> $output
     * @return array<string, mixed>
     */
    private function response(array $output): array
    {
        return ['choices' => [['message' => ['content' => json_encode($output)]]], 'usage' => ['total_tokens' => 100]];
    }
}
