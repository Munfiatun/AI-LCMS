<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "mock", "openai", "groq", "gemini"
    | Default is "mock" for cost-free, deterministic offline testing and local demo.
    |
    */
    'provider' => env('AI_PROVIDER', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'mock' => [
            'model' => 'mock-educational-v1',
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 60),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],

        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
            'timeout' => (int) env('GROQ_TIMEOUT', 60),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            // Upper bound for max_completion_tokens (reasoning + JSON). openai/gpt-oss-20b allows up to 65,536.
            'max_completion_tokens' => (int) env('GROQ_MAX_COMPLETION_TOKENS', 16384),
            // Only sent to reasoning models (openai/gpt-oss-*). Reasoning tokens consume the completion budget.
            'reasoning_effort' => env('GROQ_REASONING_EFFORT', 'low'),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 60),
            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reliability & Recovery
    |--------------------------------------------------------------------------
    |
    | A provider gets a small bounded retry budget first. When fallback is
    | enabled and the primary provider still fails at the provider/network layer,
    | AIContentService can continue through the configured provider chain.
    |
    | Mock is intentionally never used as an automatic fallback for real AI
    | requests: synthetic output must not silently enter production content.
    |
    */
    'reliability' => [
        'max_attempts' => (int) env('AI_MAX_ATTEMPTS', 2),
        'retry_backoff_ms' => (int) env('AI_RETRY_BACKOFF_MS', 250),
        'fallback_enabled' => filter_var(env('AI_FALLBACK_ENABLED', false), FILTER_VALIDATE_BOOL),
        'fallback_providers' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('AI_FALLBACK_PROVIDERS', 'gemini'))
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Versioning & Source Fidelity Defaults
    |--------------------------------------------------------------------------
    */
    'prompt_version' => env('AI_PROMPT_VERSION', 'v1.0'),
    'allow_external_knowledge' => env('AI_ALLOW_EXTERNAL_KNOWLEDGE', false),

    /*
    |--------------------------------------------------------------------------
    | Quiz AI Prompt – Extra Instructions (optional)
    |--------------------------------------------------------------------------
    |
    | Global extra text appended to every AI Quiz generation prompt.
    | Teachers can also add per-request instructions via the UI.
    | Set via .env or leave empty to use the default prompt only.
    |
    */
    'quiz_prompt_extra' => env('AI_QUIZ_PROMPT_EXTRA', ''),
];
