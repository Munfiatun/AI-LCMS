<?php

namespace App\Services\AI\Providers;

use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Exceptions\AIProviderException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqProvider implements AIProviderInterface
{
    protected string $apiKey;

    protected string $model;

    protected int $timeout;

    protected string $baseUrl;

    protected int $maxCompletionTokens;

    protected ?string $reasoningEffort;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.providers.groq.api_key');
        $this->model = (string) config('ai.providers.groq.model', 'openai/gpt-oss-20b');
        $this->timeout = (int) config('ai.providers.groq.timeout', 60);
        $this->baseUrl = rtrim((string) config('ai.providers.groq.base_url', 'https://api.groq.com/openai/v1'), '/');
        $this->maxCompletionTokens = max(1024, (int) config('ai.providers.groq.max_completion_tokens', 16384));
        $this->reasoningEffort = config('ai.providers.groq.reasoning_effort', 'low') ?: null;
    }

    public function generateStructuredData(string $systemPrompt, string $userContent, array $schemaDefinition, ?int $maxOutputTokens = null): array
    {
        if (empty($this->apiKey)) {
            throw AIProviderException::configuration('Groq');
        }

        $strictSchemaSupported = str_starts_with($this->model, 'openai/gpt-oss')
            && $this->isStrictSchemaCompatible($schemaDefinition);

        if ($strictSchemaSupported) {
            $payload = [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userContent],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'ai_lcms_structured_response',
                        'strict' => true,
                        'schema' => $this->sanitizeStrictSchema($schemaDefinition),
                    ],
                ],
                'max_completion_tokens' => $maxOutputTokens !== null
                    ? min($maxOutputTokens, $this->maxCompletionTokens)
                    : $this->maxCompletionTokens,
            ];
        } else {
            $schemaJson = json_encode($schemaDefinition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $payload = [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt."\n\nReturn ONLY one valid JSON object matching this JSON Schema. No markdown, no code fences, no text before or after the JSON.\n".$schemaJson],
                    ['role' => 'user', 'content' => $userContent],
                ],
                'response_format' => ['type' => 'json_object'],
                'max_completion_tokens' => $maxOutputTokens !== null
                    ? min($maxOutputTokens, $this->maxCompletionTokens)
                    : $this->maxCompletionTokens,
            ];
        }

        if ($this->reasoningEffort !== null && str_starts_with($this->model, 'openai/gpt-oss')) {
            $payload['reasoning_effort'] = $this->reasoningEffort;
        }

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        if (! $response->successful()) {
            Log::warning('Groq API request failed', [
                'provider' => 'groq',
                'status' => $response->status(),
                'structured_output' => $strictSchemaSupported ? 'strict' : 'json_object',
            ]);

            throw AIProviderException::fromHttp('Groq', $response->status());
        }

        $responseData = $response->json();
        $content = $responseData['choices'][0]['message']['content'] ?? '';
        $tokensUsed = $responseData['usage']['total_tokens'] ?? null;

        if (($responseData['choices'][0]['finish_reason'] ?? null) === 'length') {
            throw AIProviderException::truncated('Groq');
        }

        $parsed = json_decode($content, true);
        if (! is_array($parsed)) {
            throw AIProviderException::malformed('Groq');
        }

        return [
            'raw_response' => $content,
            'parsed_data' => $parsed,
            'tokens_used' => $tokensUsed,
        ];
    }

    /**
     * Groq strict structured output requires every object property to be required
     * and every object to be closed with additionalProperties=false.
     *
     * @param array<string, mixed> $schema
     */
    private function isStrictSchemaCompatible(array $schema): bool
    {
        $type = $schema['type'] ?? null;

        if ($type === 'object') {
            $properties = $schema['properties'] ?? null;
            $required = $schema['required'] ?? null;

            if (! is_array($properties)
                || ! is_array($required)
                || ($schema['additionalProperties'] ?? null) !== false
                || array_values(array_keys($properties)) !== array_values($required)) {
                return false;
            }

            foreach ($properties as $property) {
                if (is_array($property) && ! $this->isStrictSchemaCompatible($property)) {
                    return false;
                }
            }
        }

        if ($type === 'array' && isset($schema['items']) && is_array($schema['items'])) {
            return $this->isStrictSchemaCompatible($schema['items']);
        }

        return true;
    }

    /**
     * Keep the strict schema to Groq's supported structural subset. Application
     * validation still enforces collection sizes after generation.
     *
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function sanitizeStrictSchema(array $schema): array
    {
        unset($schema['minItems'], $schema['maxItems'], $schema['minLength'], $schema['maxLength']);

        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $key => $property) {
                if (is_array($property)) {
                    $schema['properties'][$key] = $this->sanitizeStrictSchema($property);
                }
            }
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->sanitizeStrictSchema($schema['items']);
        }

        return $schema;
    }

    public function getProviderName(): string
    {
        return 'groq';
    }

    public function getModelName(): string
    {
        return $this->model;
    }
}
