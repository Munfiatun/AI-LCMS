<?php

namespace Tests\Unit;

use App\Services\AI\Providers\GroqProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GroqProviderStructuredOutputTest extends TestCase
{
    public function test_gpt_oss_uses_strict_json_schema_for_compatible_schema(): void
    {
        Config::set('ai.providers.groq.api_key', 'test-key');
        Config::set('ai.providers.groq.model', 'openai/gpt-oss-20b');
        Config::set('ai.providers.groq.base_url', 'https://api.groq.com/openai/v1');
        Config::set('ai.providers.groq.max_completion_tokens', 8192);
        Config::set('ai.providers.groq.reasoning_effort', 'low');

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => '{"title":"Materi Percabangan"}'],
                    'finish_reason' => 'stop',
                ]],
                'usage' => ['total_tokens' => 100],
            ], 200),
        ]);

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'title' => ['type' => 'string'],
            ],
            'required' => ['title'],
        ];

        $result = (new GroqProvider)->generateStructuredData(
            'Buat materi pembelajaran.',
            '{"topic":"Percabangan Java"}',
            $schema,
            4096,
        );

        $this->assertSame('Materi Percabangan', $result['parsed_data']['title']);

        Http::assertSent(function ($request): bool {
            $payload = $request->data();

            $this->assertSame('json_schema', $payload['response_format']['type'] ?? null);
            $this->assertTrue($payload['response_format']['json_schema']['strict'] ?? false);
            $this->assertSame('ai_lcms_structured_response', $payload['response_format']['json_schema']['name'] ?? null);
            $this->assertSame('openai/gpt-oss-20b', $payload['model'] ?? null);

            return true;
        });
    }
}
