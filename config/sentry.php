<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Sentry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Sentry error tracking and performance monitoring.
    |
    */

    'dsn' => env('SENTRY_DSN'),

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'local')),

    'release' => env('SENTRY_RELEASE'),

    'enabled' => env('SENTRY_ENABLED', false),

    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

    'breadcrumbs' => [
        'sql_queries' => true,
        'http_requests' => true,
        'logs' => true,
    ],

    'options' => [
        'attach_stacktraces' => true,
        'max_breadcrumbs' => 100,
        'before_send' => function (\Sentry\Event $event): \Sentry\Event {
            // Filter out sensitive data
            if ($event->getRequest()) {
                $request = $event->getRequest();
                $sensitiveFields = ['password', 'token', 'secret', 'authorization', 'api_key'];
                
                if ($request->getData()) {
                    foreach ($request->getData() as $key => $value) {
                        if (in_array(strtolower($key), $sensitiveFields)) {
                            $request->setData(array_merge($request->getData(), [$key => '[REDACTED]']));
                        }
                    }
                }
                
                if ($request->getHeaders()) {
                    foreach ($request->getHeaders() as $key => $value) {
                        if (in_array(strtolower($key), $sensitiveFields)) {
                            $request->setHeaders(array_merge($request->getHeaders(), [$key => '[REDACTED]']));
                        }
                    }
                }
            }
            return $event;
        },
    ],

    'integrations' => [
        'laravel' => true,
        'monolog' => true,
        'guzzle' => true,
        'pdo' => true,
        'redis' => true,
    ],
];