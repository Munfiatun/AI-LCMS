<?php

namespace App\Services\AI\Exceptions;

use RuntimeException;
use Throwable;

class AIProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly bool $retryable = false,
        private readonly ?int $httpStatus = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus ?? 0, $previous);
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    public function httpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public static function fromHttp(string $provider, int $status): self
    {
        return new self(
            message: $provider.' API request failed with HTTP '.$status.'.',
            retryable: in_array($status, [408, 425, 429, 502, 503, 504], true),
            httpStatus: $status,
        );
    }

    public static function malformed(string $provider): self
    {
        return new self(
            message: $provider.' returned malformed structured data.',
            retryable: true,
        );
    }

    public static function configuration(string $provider): self
    {
        return new self(
            message: $provider.' provider configuration is incomplete.',
            retryable: false,
        );
    }

    public static function truncated(string $provider): self
    {
        return new self(
            message: $provider.' response was truncated before structured output completed.',
            retryable: false,
        );
    }
}
