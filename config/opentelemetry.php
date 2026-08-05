<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | OpenTelemetry
    |--------------------------------------------------------------------------
    |
    | Configuration for OpenTelemetry distributed tracing and metrics.
    | Integrates with OTLP-compatible backends (Jaeger, Zipkin, Tempo, etc.)
    |
    */

    'enabled' => env('OTEL_ENABLED', false),

    'service_name' => env('OTEL_SERVICE_NAME', 'astra-os'),

    /*
    |--------------------------------------------------------------------------
    | OTLP Exporter
    |--------------------------------------------------------------------------
    |
    | Configuration for OTLP HTTP/GRPC exporters.
    |
    */
    'otlp' => [
        'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://localhost:4318'),
        'headers' => [
            // 'Authorization' => 'Bearer ' . env('OTEL_EXPORTER_OTLP_HEADERS_AUTH', ''),
        ],
        'traces_endpoint' => env('OTEL_EXPORTER_OTLP_TRACES_ENDPOINT'),
        'metrics_endpoint' => env('OTEL_EXPORTER_OTLP_METRICS_ENDPOINT'),
        'timeout' => env('OTEL_EXPORTER_OTLP_TIMEOUT', 10000), // milliseconds
        'certificate_file' => env('OTEL_EXPORTER_OTLP_CERTIFICATE_FILE'),
        'client_key_file' => env('OTEL_EXPORTER_OTLP_CLIENT_KEY_FILE'),
        'client_certificate_file' => env('OTEL_EXPORTER_OTLP_CLIENT_CERTIFICATE_FILE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sampling
    |--------------------------------------------------------------------------
    |
    | Configure trace sampling strategy.
    | Options: 'always_on', 'always_off', 'traceidratio', 'parentbased_traceidratio'
    |
    */
    'sampling' => [
        'strategy' => env('OTEL_TRACES_SAMPLER', 'parentbased_traceidratio'),
        'ratio' => env('OTEL_TRACES_SAMPLER_ARG', 0.1), // 10% sampling rate
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Attributes
    |--------------------------------------------------------------------------
    |
    | Default attributes attached to all spans/metrics.
    |
    */
    'resource' => [
        'service.name' => env('OTEL_SERVICE_NAME', 'astra-os'),
        'service.version' => env('APP_VERSION', '1.0.0'),
        'deployment.environment' => env('APP_ENV', 'local'),
        'host.name' => gethostname(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Span Processor
    |--------------------------------------------------------------------------
    |
    | Batch span processor configuration for performance.
    |
    */
    'span_processor' => [
        'type' => 'batch', // 'simple' or 'batch'
        'max_queue_size' => env('OTEL_BSP_MAX_QUEUE_SIZE', 2048),
        'schedule_delay_millis' => env('OTEL_BSP_SCHEDULE_DELAY', 5000),
        'max_export_batch_size' => env('OTEL_BSP_MAX_EXPORT_BATCH_SIZE', 512),
        'export_timeout_millis' => env('OTEL_BSP_EXPORT_TIMEOUT', 30000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'enabled' => env('OTEL_METRICS_ENABLED', true),
        'export_interval_millis' => env('OTEL_METRIC_EXPORT_INTERVAL', 60000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-instrumentation
    |--------------------------------------------------------------------------
    |
    | Automatic instrumentation for common libraries.
    |
    */
    'auto_instrumentation' => [
        'http_client' => true,
        'pdo' => true,
        'redis' => true,
        'queue' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization for metrics endpoint
    |--------------------------------------------------------------------------
    */
    'authorization' => [
        'enabled' => env('OTEL_AUTH_ENABLED', true),
        'callback' => function () {
            if (app()->environment('local')) {
                return true;
            }
            return auth()->check() && auth()->user()->hasRole('admin');
        },
    ],
];