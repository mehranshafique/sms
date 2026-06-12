<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Master Switch
    |--------------------------------------------------------------------------
    | When false, the entire AI layer is disabled platform-wide regardless of
    | plans. This guarantees AI can be turned off without touching any other
    | part of the application. Existing features are never affected.
    */
    'enabled' => filter_var(env('AI_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Provider (OpenAI-compatible Chat Completions API)
    |--------------------------------------------------------------------------
    | base_url + api_key + model are the platform defaults. A school on an
    | Enterprise plan may override the key/model with their own (BYO key),
    | stored encrypted in institution_settings (group "ai").
    */
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'api_key'  => env('AI_API_KEY', null),
    'model'    => env('AI_MODEL', 'gpt-4o-mini'),

    // A cheaper/faster model for low-value tasks (classification, translation, drafts)
    'model_light' => env('AI_MODEL_LIGHT', 'gpt-4o-mini'),

    'max_tokens'  => (int) env('AI_MAX_TOKENS', 800),
    'temperature' => (float) env('AI_TEMPERATURE', 0.7),
    'timeout'     => (int) env('AI_TIMEOUT', 45),

    /*
    |--------------------------------------------------------------------------
    | Default monthly request quota
    |--------------------------------------------------------------------------
    | Used when a plan enables AI but does not set its own limit. Enterprise
    | plans set ai_unlimited = true to bypass this entirely.
    */
    'default_monthly_limit' => (int) env('AI_DEFAULT_MONTHLY_LIMIT', 100),
];
