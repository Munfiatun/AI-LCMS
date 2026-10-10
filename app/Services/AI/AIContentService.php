<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\AIProcessingResult;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Exceptions\AIProviderException;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\GroqProvider;
use App\Services\AI\Providers\MockAIProvider;
use App\Services\AI\Providers\OpenAIProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AIContentService
{
    /**
     * Resolve the active AI provider instance based on configuration.
     */
    public function getProvider(?string $providerName = null): AIProviderInterface
    {
        $provider = $providerName ?? config('ai.provider', 'mock');

        return match ($provider) {
            'mock' => new MockAIProvider,
            'openai' => new OpenAIProvider,
            'gemini' => new GeminiProvider,
            'groq' => new GroqProvider,
            default => throw new InvalidArgumentException("Unsupported AI provider: {$provider}"),
        };
    }

    /**
     * Process content with AI, enforcing idempotency tracking, audit logging,
     * bounded transient retry, and source fidelity.
     *
     * @param  array<string, mixed>  $schemaDefinition
     * @return array{
     *     log: AIProcessingLog,
     *     result: AIProcessingResult,
     *     parsed_data: array<string, mixed>
     * }
     */
    public function process(
        string $processType,
        Model $sourceModel,
        string $systemPrompt,
        string $userContent,
        array $schemaDefinition,
        ?string $providerOverride = null,
        ?int $maxOutputTokens = null,
        bool $forceRegenerate = false
    ): array {
        $provider = $this->getProvider($providerOverride);
        $promptVersion = (string) config('ai.prompt_version', 'v1.0');
        $inputHash = hash('sha256', $promptVersion.':'.$userContent);

        $existingLog = AIProcessingLog::where('input_hash', $inputHash)
            ->where('prompt_version', $promptVersion)
            ->where('status', AIProcessingLog::STATUS_COMPLETED)
            ->where('process_type', $processType)
            ->where('source_type', get_class($sourceModel))
            ->where('source_id', $sourceModel->getKey())
            ->latest()
            ->first();

        if (! $forceRegenerate && $existingLog && $existingLog->result) {
            return [
                'log' => $existingLog,
                'result' => $existingLog->result,
                'parsed_data' => $existingLog->result->parsed_data,
            ];
        }

        $maxAttempts = max(1, min(3, (int) config('ai.reliability.max_attempts', 2)));
        $backoffMs = max(0, min(2000, (int) config('ai.reliability.retry_backoff_ms', 250)));

        $log = AIProcessingLog::create([
            'process_type' => $processType,
            'source_type' => get_class($sourceModel),
            'source_id' => $sourceModel->getKey(),
            'provider' => $provider->getProviderName(),
            'model' => $provider->getModelName(),
            'prompt_version' => $promptVersion,
            'input_hash' => $inputHash,
            'status' => AIProcessingLog::STATUS_PROCESSING,
            'attempt_count' => 1,
            'processing_started_at' => now(),
        ]);

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($attempt !== $log->attempt_count) {
                $log->update(['attempt_count' => $attempt]);
            }

            try {
                $generated = $provider->generateStructuredData($systemPrompt, $userContent, $schemaDefinition, $maxOutputTokens);

                $result = AIProcessingResult::create([
                    'log_id' => $log->id,
                    'raw_response' => $generated['raw_response'],
                    'parsed_data' => $generated['parsed_data'],
                    'tokens_used' => $generated['tokens_used'],
                ]);

                $log->update([
                    'status' => AIProcessingLog::STATUS_COMPLETED,
                    'processing_completed_at' => now(),
                ]);

                return [
                    'log' => $log->fresh(),
                    'result' => $result,
                    'parsed_data' => $generated['parsed_data'],
                ];
            } catch (Throwable $exception) {
                $retryable = $this->isRetryable($exception);

                if ($retryable && $attempt < $maxAttempts) {
                    Log::warning('AI provider transient failure; retrying', [
                        'processing_log_id' => $log->id,
                        'provider' => $provider->getProviderName(),
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'exception_type' => $exception::class,
                    ]);

                    $this->backoff($backoffMs, $attempt);

                    continue;
                }

                $log->update([
                    'status' => AIProcessingLog::STATUS_FAILED,
                    'error_code' => $this->safeErrorCode($exception),
                    'error_message' => $retryable && $maxAttempts > 1
                        ? 'Proses AI gagal setelah percobaan ulang. Silakan coba kembali.'
                        : 'Proses AI gagal. Silakan coba kembali.',
                    'processing_completed_at' => now(),
                ]);

                Log::error('AI provider processing failed', [
                    'exception_type' => $exception::class,
                    'processing_log_id' => $log->id,
                    'provider' => $provider->getProviderName(),
                    'attempt_count' => $attempt,
                    'retryable' => $retryable,
                ]);

                if ($exception instanceof ConnectionException) {
                    throw new ConnectionException('Proses AI gagal. Silakan coba kembali.');
                }

                throw new RuntimeException('Proses AI gagal. Silakan coba kembali.');
            }
        }

        // Defensive fallback; every path in the loop either returns or throws.
        throw new RuntimeException('Proses AI gagal. Silakan coba kembali.');
    }

    private function isRetryable(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof AIProviderException && $exception->isRetryable();
    }

    private function safeErrorCode(Throwable $exception): string
    {
        if ($exception instanceof ConnectionException) {
            return 'connection_error';
        }

        if ($exception instanceof AIProviderException) {
            if ($exception->httpStatus() !== null) {
                return 'provider_http_'.$exception->httpStatus();
            }

            return $exception->isRetryable() ? 'provider_response_retry_exhausted' : 'provider_request_error';
        }

        return 'processing_error';
    }

    private function backoff(int $baseMilliseconds, int $attempt): void
    {
        if ($baseMilliseconds <= 0) {
            return;
        }

        $milliseconds = min(2000, $baseMilliseconds * $attempt);
        usleep($milliseconds * 1000);
    }
}
