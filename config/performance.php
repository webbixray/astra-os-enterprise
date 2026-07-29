<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for caching, query optimization, queue
    | management, and resource limits used throughout the application.
    |
    */

    'cache' => [
        'ttl' => [
            'models' => (int) env('CACHE_MODEL_TTL', 3600),
            'queries' => (int) env('CACHE_QUERY_TTL', 300),
            'api_responses' => (int) env('CACHE_API_TTL', 60),
            'analytics' => (int) env('CACHE_ANALYTICS_TTL', 900),
        ],
        'prefixes' => [
            'models' => 'model',
            'queries' => 'query',
            'api' => 'api',
        ],
        'tags_enabled' => (bool) env('CACHE_TAGS_ENABLED', true),
    ],

    'query' => [
        'slow_query_threshold_ms' => (int) env('SLOW_QUERY_THRESHOLD', 100),
        'max_joins_per_query' => (int) env('MAX_JOINS_PER_QUERY', 5),
        'chunk_size' => (int) env('QUERY_CHUNK_SIZE', 200),
        'max_results_per_page' => (int) env('MAX_RESULTS_PER_PAGE', 100),
        'default_results_per_page' => (int) env('DEFAULT_RESULTS_PER_PAGE', 15),
    ],

    'queue' => [
        'default_connection' => env('QUEUE_CONNECTION', 'redis'),
        'default_queue' => 'default',
        'high_priority_queue' => 'high',
        'low_priority_queue' => 'low',
    ],

    'resource' => [
        'max_upload_size_mb' => (int) env('MAX_UPLOAD_SIZE', 10),
        'max_execution_seconds' => (int) env('MAX_EXECUTION_TIME', 300),
        'memory_limit_mb' => (int) env('MEMORY_LIMIT', 128),
    ],
];
