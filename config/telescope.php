<?php

declare(strict_types=1);

use App\Providers\TelescopeServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Telescope Master Switch
    |--------------------------------------------------------------------------
    |
    | This option may be used to disable all Telescope watchers regardless
    | of their individual configuration. You may use this for temporary
    | disabling of the entire Telescope service.
    |
    */

    'enabled' => env('TELESCOPE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Telescope Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Telescope will be accessible from. If the
    | setting is null, Telescope will be hosted under the same configured
    | domain as the application. Otherwise, this value will be used as the
    | subdomain.
    |
    */

    'domain' => env('TELESCOPE_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Telescope Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Telescope will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI should not
    | affect the paths of its internal API that aren't accessible to the
    | public.
    |
    */

    'path' => env('TELESCOPE_PATH', 'telescope'),

    /*
    |--------------------------------------------------------------------------
    | Telescope Storage Driver
    |--------------------------------------------------------------------------
    |
    | This configuration options determines the storage driver that will
    | be used to store Telescope's data. In addition, you may set any
    | custom options as needed by the particular driver you choose.
    |
    */

    'driver' => env('TELESCOPE_DRIVER', 'database'),

    'storage' => [
        'database' => [
            'connection' => env('DB_CONNECTION', 'pgsql'),
            'chunk' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Master Switch
    |--------------------------------------------------------------------------
    |
    | You can completely disable Telescope from recording data by setting
    | this to false. If the application is not in a "local" environment,
    | Telescope will be disabled automatically.
    |
    */

    'enabled' => env('TELESCOPE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Telescope Queue
    |--------------------------------------------------------------------------
    |
    | This option controls the queue connection that Telescope will use when
    | recording entries. By default the "sync" driver is used so entries
    | are recorded immediately. You may set this to a different queue
    | to batch entries for better performance.
    |
    */

    'queue' => env('TELESCOPE_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | This option controls the database connection that Telescope will use
    | to store its entries. You may specify any of the connections defined
    | in your "database" configuration file.
    |
    */

    'connection' => env('TELESCOPE_DB_CONNECTION', env('DB_CONNECTION', 'pgsql')),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Telescope uses a queue to batch recording of entries. This allows
    | the system to process entries more efficiently. By default, entries
    | are recorded in the foreground using the "sync" queue driver. You may
    | set this to a different queue connection for better performance.
    |
    | Options:
    |   - sync: Record entries immediately (no queue)
    |   - redis: Batch entries via Redis queue
    |   - database: Batch entries via database queue
    |
    */

    'queue_connection' => env('TELESCOPE_QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Watchers
    |--------------------------------------------------------------------------
    |
    | Here you may specify the watchers that are enabled for Telescope.
    | Each watcher can be enabled/disabled individually. You may also
    | specify the batch priority for each watcher to control the order
    | in which entries are processed.
    |
    */

    'watchers' => [

        Watchers\BatchWatcher::class => env('TELESCOPE_BATCH_WATCHER', true),

        Watchers\CacheWatcher::class => [
            'enabled' => env('TELESCOPE_CACHE_WATCHER', true),
            'hidden' => [],
        ],

        Watchers\ClientRequestWatcher::class => env('TELESCOPE_CLIENT_REQUEST_WATCHER', true),

        Watchers\CommandWatcher::class => [
            'enabled' => env('TELESCOPE_COMMAND_WATCHER', true),
            'ignore' => [],
        ],

        Watchers\DumpWatcher::class => [
            'enabled' => env('TELESCOPE_DUMP_WATCHER', true),
            'always' => env('TELESCOPE_DUMP_WATCHER_ALWAYS', false),
        ],

        Watchers\EventWatcher::class => [
            'enabled' => env('TELESCOPE_EVENT_WATCHER', true),
            'ignore' => [],
        ],

        Watchers\ExceptionWatcher::class => env('TELESCOPE_EXCEPTION_WATCHER', true),

        Watchers\GateWatcher::class => [
            'enabled' => env('TELESCOPE_GATE_WATCHER', true),
            'ignore_abilities' => [],
            'ignore_policies' => [],
        ],

        Watchers\JobWatcher::class => env('TELESCOPE_JOB_WATCHER', true),

        Watchers\LogWatcher::class => [
            'enabled' => env('TELESCOPE_LOG_WATCHER', true),
            'level' => env('TELESCOPE_LOG_LEVEL', 'error'),
        ],

        Watchers\MailWatcher::class => env('TELESCOPE_MAIL_WATCHER', true),

        Watchers\ModelWatcher::class => [
            'enabled' => env('TELESCOPE_MODEL_WATCHER', true),
            'events' => ['eloquent.*'],
            'hydrations' => true,
        ],

        Watchers\NotificationWatcher::class => env('TELESCOPE_NOTIFICATION_WATCHER', true),

        Watchers\QueryWatcher::class => [
            'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
            'ignore_packageds' => true,
            'time' => 100,
        ],

        Watchers\RedisWatcher::class => env('TELESCOPE_REDIS_WATCHER', true),

        Watchers\RequestWatcher::class => [
            'enabled' => env('TELESCOPE_REQUEST_WATCHER', true),
            'size_limit' => env('TELESCOPE_RESPONSE_SIZE_LIMIT', 64),
            'ignore_http_methods' => [],
            'ignore_status_codes' => [],
        ],

        Watchers\ScheduleWatcher::class => env('TELESCOPE_SCHEDULE_WATCHER', true),

        Watchers\ViewWatcher::class => env('TELESCOPE_VIEW_WATCHER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Batch Settings
    |--------------------------------------------------------------------------
    |
    | Telescope uses batch processing to efficiently record and store
    | entries. The following configuration controls how batches are
    | processed.
    |
    */

    'batch' => [
        'size' => env('TELESCOPE_BATCH_SIZE', 200),
        'queue' => env('TELESCOPE_BATCH_QUEUE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Master Switch Environment Filter
    |--------------------------------------------------------------------------
    |
    | By default, Telescope only records data in local and development
    | environments. You may customize this by setting the
    | TELESCOPE_ENVIRONMENT_FILTER to false, or by explicitly defining
    | which environments Telescope should record in.
    |
    */

    'environments' => [
        'local',
        'development',
        'testing',
        'staging',
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Privacy
    |--------------------------------------------------------------------------
    |
    | Telescope is built with privacy in mind. By default, it will not
    | record the full request/response body for requests that contain
    | sensitive data. You may customize what data is recorded.
    |
    */

    'privacy' => [
        'request_methods' => ['GET', 'HEAD'],
        'fields' => [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'api_key',
            'api_secret',
            'access_token',
            'refresh_token',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telescope Recording Filter
    |--------------------------------------------------------------------------
    |
    | You may want to only record certain types of entries for performance
    | reasons. The "recording" option allows you to specify which types
    | of entries should be recorded. Set to true to record all entries.
    |
    | Options: true, false, or an array of entry type names.
    |
    */

    'recording' => env('TELESCOPE_RECORDING', true),
];
