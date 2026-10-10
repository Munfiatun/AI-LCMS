# AI Reliability & Recovery

R4 makes provider failures recoverable without creating partial learning content or uncontrolled duplicate paid calls.

## Retry policy

The shared AI content service allows a small bounded retry window. The default is two attempts and the hard maximum is three attempts per processing log.

Automatically retryable conditions:

- connection / transport failures;
- HTTP 408 Request Timeout;
- HTTP 425 Too Early;
- HTTP 429 Too Many Requests;
- HTTP 502 Bad Gateway;
- HTTP 503 Service Unavailable;
- HTTP 504 Gateway Timeout;
- malformed structured output that may succeed on one regeneration.

Failures that are not repeated automatically:

- missing provider credentials/configuration;
- generic HTTP 500 responses;
- ordinary 4xx request errors;
- output truncation caused by the current token budget.

This distinction prevents repeating the exact same request when another attempt is unlikely to change the outcome.

## Audit behavior

A recovery sequence uses one `ai_processing_logs` record. `attempt_count` reflects actual provider calls. A successful recovery creates exactly one processing result. An exhausted failure records only a safe error classification such as `connection_error` or `provider_http_503`.

Provider response bodies, API keys, private diagnostics, and raw exception messages are never copied into the user-facing `error_message`.

## Idempotency

Completed processing remains idempotent under the existing prompt-version/input-hash rules. Retrying a transient failure happens inside the same processing operation rather than creating duplicate content records.

## Operational configuration

```env
AI_MAX_ATTEMPTS=2
AI_RETRY_BACKOFF_MS=250
```

Keep `AI_MAX_ATTEMPTS` small. The application clamps the configured value to a maximum of three attempts.
