<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Exceptions\AIProviderException;
use Illuminate\Support\Facades\Http;

class OpenAIProvider implements AIProviderInterface
{
    protected string $apiKey;

    protected string $model;

    protected int $timeout;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.providers.openai.api_key');
        $this->model = (string) config('ai.providers.openai.model', 'gpt-4o-mini');
        $this->timeout = (int) config('ai.providers.openai.timeout', 60);
        $this->baseUrl = rtrim((string) config('ai.providers.openai.base_url', 'https://api.openai.com/v1'), '/');
    }

    public function generateStructuredData(string $systemPrompt, string $userContent, array $schemaDefinition, ?int $maxOutputTokens = null): array
    {
        if (empty($this->apiKey)) {
            throw AIProviderException::configuration('OpenAI');
        }

        $systemPromptWithJson = $systemPrompt."\nIMPORTANT: You must respond with pure, valid JSON strictly adhering to the requested schema. Do not enclose JSON in markdown code fences.";

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPromptWithJson],
                    ['role' => 'user', 'content' => $userContent],
                ],
                'response_format' => ($schemaDefinition['title'] ?? null) === 'quiz_generation'
                    ? ['type' => 'json_schema', 'json_schema' => ['name' => 'quiz_generation', 'strict' => true, 'schema' => $schemaDefinition]]
                    : ['type' => 'json_object'],
            ]);

        if (! $response->successful()) {
            throw AIProviderException::fromHttp('OpenAI', $response->status());
        }

        $responseData = $response->json();
        $content = $responseData['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $responseData['usage']['total_tokens'] ?? null;

        $parsed = json_decode($content, true);
        if (! is_array($parsed)) {
            throw AIProviderException::malformed('OpenAI');
        }

        return [
            'raw_response' => $content,
            'parsed_data' => $parsed,
            'tokens_used' => $tokensUsed,
        ];
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function getModelName(): string
    {
        return $this->model;
    }
}
