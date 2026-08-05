<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Prometheus Metrics
    |--------------------------------------------------------------------------
    |
    | Configuration for Prometheus metrics exposition via HTTP endpoint.
    | Metrics are collected automatically via middleware and can be scraped
    | by Prometheus server.
    |
    */

    'enabled' => env('PROMETHEUS_ENABLED', true),

    'path' => env('PROMETHEUS_PATH', 'metrics'),

    'middleware' => [
        'web',
        'auth:sanctum',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Metrics
    |--------------------------------------------------------------------------
    |
    | Built-in metrics that are automatically registered.
    |
    */
    'default_metrics' => [
        'http_requests_total' => true,
        'http_request_duration_seconds' => true,
        'http_request_size_bytes' => true,
        'http_response_size_bytes' => true,
        'queue_jobs_total' => true,
        'queue_job_duration_seconds' => true,
        'queue_job_failed_total' => true,
        'database_queries_total' => true,
        'database_query_duration_seconds' => true,
        'cache_hits_total' => true,
        'cache_misses_total' => true,
        'active_users' => true,
        'memory_usage_bytes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Metrics Namespace
    |--------------------------------------------------------------------------
    */
    'namespace' => env('PROMETHEUS_NAMESPACE', 'astra_os'),

    /*
    |--------------------------------------------------------------------------
    | Metric Labels
    |--------------------------------------------------------------------------
    |
    | Additional labels to attach to all metrics.
    |
    */
    'global_labels' => [
        'app' => env('APP_NAME', 'astra-os'),
        'environment' => env('APP_ENV', 'local'),
        'instance' => gethostname(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Histogram Buckets
    |--------------------------------------------------------------------------
    |
    | Default histogram buckets for duration metrics.
    |
    */
    'buckets' => [
        'http' => [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10],
        'database' => [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1],
        'queue' => [0.01, 0.05, 0.1, 0.5, 1, 5, 10, 30, 60],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | Control who can access the metrics endpoint.
    |
    */
    'authorization' => [
        'enabled' => env('PROMETHEUS_AUTH_ENABLED', true),
        'callback' => function () {
            // Allow access in local environment
            if (app()->environment('local')) {
                return true;
            }
            // Require admin role for metrics access
            return auth()->check() && auth()->user()->hasRole('admin');
        },
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Gateway (for short-lived jobs)
    |--------------------------------------------------------------------------
    */
    'push_gateway' => [
        'enabled' => env('PROMETHEUS_PUSH_GATEWAY_ENABLED', false),
        'url' => env('PROMETHEUS_PUSH_GATEWAY_URL'),
        'job' => env('PROMETHEUS_PUSH_GATEWAY_JOB', 'astra-os-jobs'),
        'grouping_key' => [
            'instance' => gethostname(),
        ],
    ],
];