<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Defines rate limit configurations for different API entry points.
    | Uses Laravel's built-in RateLimiter facade.
    |
    */

    'api' => [
        'name' => 'api',
        'max_attempts' => env('API_RATE_LIMIT_MAX', 60),
        'decay_minutes' => 1,
        'description' => 'General API rate limit (60 requests/minute)',
    ],

    'auth' => [
        'name' => 'auth',
        'max_attempts' => env('AUTH_RATE_LIMIT_MAX', 10),
        'decay_minutes' => 1,
        'description' => 'Authentication rate limit (10 requests/minute)',
    ],

    'webhooks' => [
        'name' => 'webhooks',
        'max_attempts' => env('WEBHOOK_RATE_LIMIT_MAX', 1000),
        'decay_minutes' => 1,
        'description' => 'Webhook rate limit (1000 requests/minute)',
    ],

    'campaigns' => [
        'name' => 'campaigns',
        'max_attempts' => env('CAMPAIGN_RATE_LIMIT_MAX', 120),
        'decay_minutes' => 1,
        'description' => 'Campaign API rate limit (120 requests/minute)',
    ],

    'social' => [
        'name' => 'social',
        'max_attempts' => env('SOCIAL_RATE_LIMIT_MAX', 30),
        'decay_minutes' => 1,
        'description' => 'Social API rate limit (30 requests/minute)',
    ],
];
