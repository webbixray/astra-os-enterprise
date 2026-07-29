<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If the
    | setting is null, Horizon will be hosted under the same configured
    | domain as the application. Otherwise, this value will be used as the
    | subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI should not
    | affect the paths of its internal API that aren't accessible from
    | the public.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store its
    | meta information. This connection is used for all Horizon operations
    | including queue monitoring, job statistics, and process management.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple applications
    | on the same Redis server to avoid conflicts.
    |
    */

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be attached to the Horizon dashboard route. You
    | may add your own middleware to this list to protect access to the
    | dashboard.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This configuration allows you to set the wait time thresholds for each
    | queue connection. When a job waits longer than the specified time, it
    | will be highlighted in the Horizon dashboard to draw your attention.
    |
    */

    'waits' => [
        'redis:default' => 60,
        'redis:critical' => 30,
        'redis:high' => 60,
        'redis:low' => 120,
        'redis:notifications' => 60,
        'redis:reports' => 300,
        'redis:webhooks' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you may configure the number of days that Horizon should retain
    | completed and failed jobs. You may set these values per environment.
    | The "recent" jobs are those that completed within the last 60 minutes.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 120,
        'completed' => 1440,      // 24 hours
        'recent_failed' => 10080, // 7 days
        'failed' => 10080,        // 7 days
        'monitored' => 10080,     // 7 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Job Classes
    |--------------------------------------------------------------------------
    |
    | You may specify job classes that should not be monitored by Horizon.
    | These jobs will not be shown in the dashboard and will not trigger
    | notifications.
    |
    */

    'silenced' => [
        // App\Jobs\ExampleJob::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you may configure the metrics snapshot settings for Horizon.
    | The snapshot store determines how often metrics snapshots are
    | taken and retained.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When using the "pcntl" PHP extension, Horizon's worker processes will
    | automatically terminate after receiving a signal. This is useful for
    | deploying updates without needing to manually stop workers.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory a single Horizon
    | worker process may consume before it is terminated and restarted.
    | This prevents memory leaks from accumulating over time.
    |
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the worker configuration for each environment.
    | Each environment can have its own worker settings, including the
    | number of processes, balance strategy, and queue priorities.
    |
    | Balance Strategy:
    |   - auto: Workers are dynamically distributed across queues based on
    |           queue load (best for most setups).
    |   - simple: Workers are evenly distributed with a minimum per queue.
    |   - false: No load balancing; workers process queues in order.
    |
    */

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default', 'notifications'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 60,
                'nice' => 0,
            ],

            'supervisor-2' => [
                'connection' => 'redis',
                'queue' => ['high', 'critical'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 2,
                'maxProcesses' => 15,
                'balanceMaxShift' => 2,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 120,
                'nice' => -10, // Higher priority (niceness -20 to 19)
            ],

            'supervisor-3' => [
                'connection' => 'redis',
                'queue' => ['low', 'reports'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 5,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 5,
                'timeout' => 300,
                'nice' => 10, // Lower priority
            ],

            'supervisor-4' => [
                'connection' => 'redis',
                'queue' => ['webhooks'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 2,
                'maxProcesses' => 8,
                'balanceMaxShift' => 2,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 30,
                'nice' => 0,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default', 'high', 'low', 'critical', 'notifications', 'reports', 'webhooks'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 60,
                'nice' => 0,
            ],
        ],

        'staging' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default', 'high'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 5,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 60,
                'nice' => 0,
            ],

            'supervisor-2' => [
                'connection' => 'redis',
                'queue' => ['low', 'notifications', 'reports'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 5,
                'timeout' => 120,
                'nice' => 0,
            ],

            'supervisor-3' => [
                'connection' => 'redis',
                'queue' => ['webhooks'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'minProcesses' => 1,
                'maxProcesses' => 4,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'tries' => 3,
                'timeout' => 30,
                'nice' => 0,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Configuring Max Processes
    |--------------------------------------------------------------------------
    |
    | When using the "auto" balance strategy, Horizon will automatically
    | distribute the number of processes based on the queue load. This
    | setting allows you to define the minimum and maximum number of
    | processes per supervisor.
    |
    | Tags help you organize and filter jobs in the Horizon dashboard.
    | You may tag jobs by adding a "tags" method to your job class.
    |
    */

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 1,
            'maxProcesses' => 5,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'tries' => 3,
            'timeout' => 60,
            'nice' => 0,
        ],
    ],
];
