<?php

namespace Tests\Unit;

use App\Services\AI\Providers\GeminiProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiProviderStructuredOutputTest extends TestCase
{
    public function test_gemini_sends_json_schema_and_output_budget_for_structured_generation(): void
    {
        config([
            'ai.providers.gemini.api_key' => 'test-key',
            'ai.providers.gemini.model' => 'gemini-3.5-flash-lite',
            'ai.providers.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'ai.providers.gemini.timeout' => 60,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode(['status' => 'ok']),
                        ]],
                    ],
                ]],
                'usageMetadata' => ['totalTokenCount' => 12],
            ]),
        ]);

        $schema = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'status' => ['type' => 'string'],
            ],
            'required' => ['status'],
        ];

        $result = (new GeminiProvider)->generateStructuredData(
            'Return structured data.',
            'Teacher content.',
            $schema,
            2048,
        );

        $this->assertSame('ok', $result['parsed_data']['status']);

        Http::assertSent(function ($request) use ($schema): bool {
            $payload = $request->data();
            $config = $payload['generationConfig'] ?? [];

            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent'
                && $config['responseMimeType'] === 'application/json'
                && $config['responseJsonSchema'] === $schema
                && $config['maxOutputTokens'] === 2048;
        });
    }
}
