<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Exceptions\AIProviderException;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AIProviderInterface
{
    protected string $apiKey;

    protected string $model;

    protected int $timeout;

    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.providers.gemini.api_key');
        $this->model = (string) config('ai.providers.gemini.model', 'gemini-3.5-flash-lite');
        $this->timeout = (int) config('ai.providers.gemini.timeout', 60);
        $this->baseUrl = rtrim((string) config('ai.providers.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
    }

    public function generateStructuredData(string $systemPrompt, string $userContent, array $schemaDefinition, ?int $maxOutputTokens = null): array
    {
        if (empty($this->apiKey)) {
            throw AIProviderException::configuration('Gemini');
        }

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent";
        $combinedPrompt = $systemPrompt."\n\n=== SOURCE DOCUMENT CONTENT ===\n".$userContent;

        $generationConfig = [
            'responseMimeType' => 'application/json',
            'responseJsonSchema' => $schemaDefinition,
        ];

        if ($maxOutputTokens !== null) {
            $generationConfig['maxOutputTokens'] = max(256, $maxOutputTokens);
        }

        $response = Http::withHeaders(['x-goog-api-key' => $this->apiKey])
            ->timeout($this->timeout)
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $combinedPrompt],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ]);

        if (! $response->successful()) {
            throw AIProviderException::fromHttp('Gemini', $response->status());
        }

        $responseData = $response->json();
        $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $tokensUsed = $responseData['usageMetadata']['totalTokenCount'] ?? null;

        $parsed = json_decode($rawText, true);
        if (! is_array($parsed)) {
            throw AIProviderException::malformed('Gemini');
        }

        return [
            'raw_response' => $rawText,
            'parsed_data' => $parsed,
            'tokens_used' => $tokensUsed,
        ];
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }

    public function getModelName(): string
    {
        return $this->model;
    }
}
