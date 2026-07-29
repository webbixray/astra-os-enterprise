<?php

declare(strict_types=1);

use Laravel\Pulse\Http\Middleware\Authorize;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders;

return [

    /*
    |--------------------------------------------------------------------------
    | Pulse Domain
    |--------------------------------------------------------------------------
    */
    'domain' => env('PULSE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Pulse Path
    |--------------------------------------------------------------------------
    */
    'path' => env('PULSE_PATH', 'pulse'),

    /*
    |--------------------------------------------------------------------------
    | Pulse Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'web',
        Authorize::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'time_zone' => env('PULSE_TIMEZONE', 'UTC'),
        'refresh_interval' => env('PULSE_REFRESH_INTERVAL', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Storage
    |--------------------------------------------------------------------------
    */
    'storage' => [
        'driver' => env('PULSE_STORAGE_DRIVER', 'database'),
        'database' => [
            'connection' => env('PULSE_DB_CONNECTION', env('DB_CONNECTION', 'pgsql')),
            'chunk' => 1000,
        ],
        'trim' => [
            'keep' => env('PULSE_TRIM_KEEP', '7 days'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Recorders
    |--------------------------------------------------------------------------
    */
    'recorders' => [
        Recorders\CacheInteractions::class => [
            'enabled' => env('PULSE_CACHE_INTERACTIONS_ENABLED', true),
            'sample_rate' => env('PULSE_CACHE_INTERACTIONS_SAMPLE_RATE', 1.0),
            'ignore' => [
                ...Pulse::filteredUsers(),
            ],
            'groups' => [
                '#/posts/\\d+$#i' => '/posts/{id}',
                '#/campaigns/\\d+$#i' => '/campaigns/{id}',
            ],
        ],

        Recorders\Exceptions::class => [
            'enabled' => env('PULSE_EXCEPTIONS_ENABLED', true),
            'sample_rate' => env('PULSE_EXCEPTIONS_SAMPLE_RATE', 1.0),
            'location' => env('PULSE_EXCEPTIONS_LOCATION', true),
            'ignore' => [
                // Ignore 404 exceptions
                Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            ],
        ],

        Recorders\Queues::class => [
            'enabled' => env('PULSE_QUEUES_ENABLED', true),
            'sample_rate' => env('PULSE_QUEUES_SAMPLE_RATE', 1.0),
            'ignore' => [],
        ],

        Recorders\Servers::class => [
            'server_name' => env('PULSE_SERVER_NAME', gethostname()),
            'directories' => explode(',', env('PULSE_SERVER_DIRECTORIES', '/')),
        ],

        Recorders\SlowJobs::class => [
            'enabled' => env('PULSE_SLOW_JOBS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_JOBS_SAMPLE_RATE', 1.0),
            'threshold' => env('PULSE_SLOW_JOBS_THRESHOLD', 1000),
            'ignore' => [],
        ],

        Recorders\SlowOutgoingRequests::class => [
            'enabled' => env('PULSE_SLOW_OUTGOING_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_OUTGOING_REQUESTS_SAMPLE_RATE', 1.0),
            'threshold' => env('PULSE_SLOW_OUTGOING_REQUESTS_THRESHOLD', 1000),
            'ignore' => [],
        ],

        Recorders\SlowQueries::class => [
            'enabled' => env('PULSE_SLOW_QUERIES_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_QUERIES_SAMPLE_RATE', 1.0),
            'threshold' => env('PULSE_SLOW_QUERIES_THRESHOLD', 100),
            'location' => env('PULSE_SLOW_QUERIES_LOCATION', true),
            'max_query_length' => env('PULSE_SLOW_QUERIES_MAX_LENGTH', 500),
            'ignore' => [
                '#^pulse#i',
                '#^/health#i',
            ],
        ],

        Recorders\SlowRequests::class => [
            'enabled' => env('PULSE_SLOW_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_SLOW_REQUESTS_SAMPLE_RATE', 1.0),
            'threshold' => env('PULSE_SLOW_REQUESTS_THRESHOLD', 500),
            'location' => env('PULSE_SLOW_REQUESTS_LOCATION', true),
            'ignore' => [
                '#^/pulse#i',
                '#^/health#i',
                '#^/telescope#i',
            ],
        ],

        Recorders\UserJobs::class => [
            'enabled' => env('PULSE_USER_JOBS_ENABLED', true),
            'sample_rate' => env('PULSE_USER_JOBS_SAMPLE_RATE', 1.0),
        ],

        Recorders\UserRequests::class => [
            'enabled' => env('PULSE_USER_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_USER_REQUESTS_SAMPLE_RATE', 1.0),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Ingestion
    |--------------------------------------------------------------------------
    */
    'ingest' => [
        'driver' => env('PULSE_INGEST_DRIVER', 'database'),
        'buffer' => env('PULSE_INGEST_BUFFER', 5000),
        'trim' => [
            'lottery' => [1, 100],
        ],
        'redis' => [
            'connection' => env('PULSE_REDIS_CONNECTION'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pulse Authorization
    |--------------------------------------------------------------------------
    */
    'authorization' => [
        'enabled' => env('PULSE_AUTH_ENABLED', false),

        /*
        |--------------------------------------------------------------------------
        | Pulse Authorize Callback
        |--------------------------------------------------------------------------
        |
        | This callback determines who can access the Pulse dashboard.
        | Return true to allow access, or false to deny.
        |
        */
        'callback' => function () {
            return app()->environment('local');
        },
    ],
];
