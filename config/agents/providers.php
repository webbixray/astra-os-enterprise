<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'enabled' => env('OPENAI_ENABLED', true),
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'models' => [
            'gpt-4o' => [
                'model' => 'gpt-4o',
                'max_tokens' => 16384,
                'temperature' => 0.7,
                'cost_per_1k_input' => 0.01,
                'cost_per_1k_output' => 0.03,
            ],
            'gpt-4o-mini' => [
                'model' => 'gpt-4o-mini',
                'max_tokens' => 8192,
                'temperature' => 0.5,
                'cost_per_1k_input' => 0.0015,
                'cost_per_1k_output' => 0.006,
            ],
            'o1-mini' => [
                'model' => 'o1-mini',
                'max_tokens' => 32768,
                'temperature' => 1.0,
                'cost_per_1k_input' => 0.003,
                'cost_per_1k_output' => 0.012,
            ],
        ],
        'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
        'timeout' => env('OPENAI_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Anthropic Configuration
    |--------------------------------------------------------------------------
    */
    'anthropic' => [
        'enabled' => env('ANTHROPIC_ENABLED', false),
        'api_key' => env('ANTHROPIC_API_KEY'),
        'models' => [
            'claude-3-opus' => [
                'model' => 'claude-3-opus-20240229',
                'max_tokens' => 8192,
                'temperature' => 0.7,
            ],
            'claude-3-sonnet' => [
                'model' => 'claude-3-sonnet-20240229',
                'max_tokens' => 8192,
                'temperature' => 0.5,
            ],
            'claude-3-haiku' => [
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 4096,
                'temperature' => 0.5,
            ],
        ],
        'default_model' => env('ANTHROPIC_DEFAULT_MODEL', 'claude-3-sonnet'),
        'timeout' => env('ANTHROPIC_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google AI Configuration
    |--------------------------------------------------------------------------
    */
    'google' => [
        'enabled' => env('GOOGLE_AI_ENABLED', false),
        'api_key' => env('GOOGLE_AI_API_KEY'),
        'models' => [
            'gemini-pro' => [
                'model' => 'gemini-pro',
                'max_tokens' => 8192,
                'temperature' => 0.7,
            ],
            'gemini-pro-vision' => [
                'model' => 'gemini-pro-vision',
                'max_tokens' => 8192,
                'temperature' => 0.5,
            ],
        ],
        'default_model' => env('GOOGLE_AI_DEFAULT_MODEL', 'gemini-pro'),
        'timeout' => env('GOOGLE_AI_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Chain
    |--------------------------------------------------------------------------
    | The order in which AI providers are tried when a request fails.
    */
    'fallback_chain' => [
        'openai',
        'anthropic',
        'google',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'max_requests_per_minute' => env('AI_RATE_LIMIT_PER_MINUTE', 60),
        'max_tokens_per_minute' => env('AI_TOKEN_LIMIT_PER_MINUTE', 100000),
    ],
];
