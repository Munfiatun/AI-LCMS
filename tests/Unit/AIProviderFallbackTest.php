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
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AIProviderFallbackTest extends TestCase
{
    use RefreshDatabase;

    private LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.provider' => 'groq',
            'ai.providers.groq.api_key' => 'groq-test-key',
            'ai.providers.groq.model' => 'openai/gpt-oss-20b',
            'ai.providers.groq.base_url' => 'https://api.groq.com/openai/v1',
            'ai.providers.gemini.api_key' => 'gemini-test-key',
            'ai.providers.gemini.model' => 'gemini-3.5-flash-lite',
            'ai.providers.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'ai.reliability.max_attempts' => 1,
            'ai.reliability.retry_backoff_ms' => 0,
            'ai.reliability.fallback_enabled' => true,
            'ai.reliability.fallback_providers' => ['gemini'],
        ]);

        Http::preventStrayRequests();

        $role = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);
        $teacher = User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
        $course = Course::factory()->create(['instructor_id' => $teacher->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $this->material = LearningMaterial::factory()->create(['section_id' => $section->id]);
    }

    public function test_groq_failure_falls_back_to_gemini_and_preserves_provider_audit_logs(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'error' => ['message' => 'temporarily unavailable'],
            ], 503),
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode(['status' => 'gemini-recovered']),
                        ]],
                    ],
                ]],
                'usageMetadata' => ['totalTokenCount' => 51],
            ]),
        ]);

        $output = $this->process('fallback-success');

        $this->assertSame('gemini-recovered', $output['parsed_data']['status']);
        $this->assertSame('gemini', $output['log']->provider);
        $this->assertSame('gemini-3.5-flash-lite', $output['log']->model);

        $logs = AIProcessingLog::orderBy('id')->get();
        $this->assertCount(2, $logs);
        $this->assertSame('groq', $logs[0]->provider);
        $this->assertSame(AIProcessingLog::STATUS_FAILED, $logs[0]->status);
        $this->assertSame('provider_http_503', $logs[0]->error_code);
        $this->assertSame('gemini', $logs[1]->provider);
        $this->assertSame(AIProcessingLog::STATUS_COMPLETED, $logs[1]->status);

        Http::assertSentCount(2);
    }

    public function test_explicit_provider_override_does_not_fan_out_to_fallback(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'error' => ['message' => 'temporarily unavailable'],
            ], 503),
        ]);

        try {
            $this->process('explicit-groq-only', 'groq');
            $this->fail('Expected safe provider failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Proses AI gagal. Silakan coba kembali.', $exception->getMessage());
        }

        $this->assertDatabaseCount('ai_processing_logs', 1);
        $this->assertSame('groq', AIProcessingLog::sole()->provider);
        Http::assertSentCount(1);
    }

    public function test_mock_is_never_used_as_automatic_fallback(): void
    {
        config([
            'ai.reliability.fallback_providers' => ['mock'],
        ]);

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'error' => ['message' => 'temporarily unavailable'],
            ], 503),
        ]);

        try {
            $this->process('no-mock-fallback');
            $this->fail('Expected safe provider failure was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Proses AI gagal. Silakan coba kembali.', $exception->getMessage());
        }

        $this->assertDatabaseCount('ai_processing_logs', 1);
        $this->assertSame('groq', AIProcessingLog::sole()->provider);
        Http::assertSentCount(1);
    }

    /** @return array<string, mixed> */
    private function process(string $content, ?string $providerOverride = null): array
    {
        return (new AIContentService)->process(
            processType: AIProcessingLog::PROCESS_SLIDEBOOK_GENERATION,
            sourceModel: $this->material,
            systemPrompt: 'Return structured JSON.',
            userContent: $content,
            schemaDefinition: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'status' => ['type' => 'string'],
                ],
                'required' => ['status'],
            ],
            providerOverride: $providerOverride,
            maxOutputTokens: 512,
        );
    }
}
