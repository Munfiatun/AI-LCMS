<?php

namespace Tests\Unit;

use App\Models\AIProcessingLog;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\User;
use App\Services\AI\AIContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AIReliabilityRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.provider' => 'openai',
            'ai.providers.openai.api_key' => 'test-key',
            'ai.providers.openai.base_url' => 'https://api.openai.com/v1',
            'ai.providers.groq.api_key' => 'test-key',
            'ai.providers.groq.base_url' => 'https://api.groq.com/openai/v1',
            'ai.reliability.max_attempts' => 2,
            'ai.reliability.retry_backoff_ms' => 0,
        ]);
        Http::preventStrayRequests();

        $role = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);
        $teacher = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $this->material = LearningMaterial::factory()->create(['section_id' => $section->id]);
    }

    public function test_rate_limit_is_retried_and_single_log_records_recovery_attempts(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::sequence()
                ->push(['error' => ['message' => 'rate limited']], 429)
                ->push($this->openAiResponse(['status' => 'recovered'])),
        ]);

        $output = $this->process('rate-limit-recovery');

        $this->assertSame('recovered', $output['parsed_data']['status']);
        $this->assertSame(2, $output['log']->attempt_count);
        $this->assertSame(AIProcessingLog::STATUS_COMPLETED, $output['log']->status);
        $this->assertDatabaseCount('ai_processing_logs', 1);
        $this->assertDatabaseCount('ai_processing_results', 1);
        Http::assertSentCount(2);
    }

    public function test_malformed_structured_response_is_retried_once_and_can_recover(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::sequence()
                ->push(['choices' => [['message' => ['content' => 'not-json']]]])
                ->push($this->openAiResponse(['status' => 'valid-json'])),
        ]);

        $output = $this->process('malformed-recovery');

        $this->assertSame('valid-json', $output['parsed_data']['status']);
        $this->assertSame(2, $output['log']->attempt_count);
        Http::assertSentCount(2);
    }

    public function test_generic_http_500_fails_fast_instead_of_repeating_paid_request(): void
    {
        config(['ai.reliability.max_attempts' => 3]);
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'error' => ['message' => 'private provider diagnostic'],
            ], 500),
        ]);

        try {
            $this->process('server-error');
            $this->fail('Expected safe runtime failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Proses AI gagal. Silakan coba kembali.', $exception->getMessage());
        }

        $log = AIProcessingLog::sole();
        $this->assertSame(1, $log->attempt_count);
        $this->assertSame(AIProcessingLog::STATUS_FAILED, $log->status);
        $this->assertSame('provider_http_500', $log->error_code);
        $this->assertStringNotContainsString('private provider diagnostic', (string) $log->error_message);
        Http::assertSentCount(1);
    }

    public function test_connection_failure_retries_then_returns_safe_timeout_classification(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::failedConnection(),
        ]);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Proses AI gagal. Silakan coba kembali.');

        try {
            $this->process('connection-failure');
        } finally {
            $log = AIProcessingLog::sole();
            $this->assertSame(2, $log->attempt_count);
            $this->assertSame(AIProcessingLog::STATUS_FAILED, $log->status);
            $this->assertSame('connection_error', $log->error_code);
            Http::assertSentCount(2);
        }
    }

    public function test_groq_truncation_is_not_retried_with_identical_token_budget(): void
    {
        config(['ai.provider' => 'groq', 'ai.reliability.max_attempts' => 3]);
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode(['status' => 'partial'])],
                    'finish_reason' => 'length',
                ]],
                'usage' => ['total_tokens' => 100],
            ]),
        ]);

        try {
            $this->process('groq-truncated', 'groq');
            $this->fail('Expected truncation failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Proses AI gagal. Silakan coba kembali.', $exception->getMessage());
        }

        $log = AIProcessingLog::sole();
        $this->assertSame(1, $log->attempt_count);
        $this->assertSame('provider_request_error', $log->error_code);
        Http::assertSentCount(1);
    }

    public function test_retry_attempts_are_hard_capped_at_three(): void
    {
        config(['ai.reliability.max_attempts' => 99]);
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(['error' => ['message' => 'busy']], 503),
        ]);

        try {
            $this->process('bounded-retry');
            $this->fail('Expected exhausted retry failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Proses AI gagal. Silakan coba kembali.', $exception->getMessage());
        }

        $log = AIProcessingLog::sole();
        $this->assertSame(3, $log->attempt_count);
        $this->assertSame('provider_http_503', $log->error_code);
        $this->assertSame('Proses AI gagal setelah percobaan ulang. Silakan coba kembali.', $log->error_message);
        Http::assertSentCount(3);
    }

    /** @return array<string, mixed> */
    private function process(string $content, ?string $provider = null): array
    {
        return (new AIContentService)->process(
            processType: AIProcessingLog::PROCESS_SLIDEBOOK_GENERATION,
            sourceModel: $this->material,
            systemPrompt: 'Return structured JSON.',
            userContent: $content,
            schemaDefinition: ['type' => 'object'],
            providerOverride: $provider,
        );
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function openAiResponse(array $payload): array
    {
        return [
            'choices' => [['message' => ['content' => json_encode($payload)]]],
            'usage' => ['total_tokens' => 42],
        ];
    }
}
