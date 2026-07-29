<?php

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilised to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilises the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    | Every channel may also be assigned a set of Monolog processors that
    | enrich log records with additional context:
    |
    |   WebProcessor           — URL, HTTP method, client IP, user agent
    |   IntrospectionProcessor — File, line number, class, function
    |   MemoryUsageProcessor   — Peak and current memory usage
    |   UidProcessor           — Unique identifier per log entry
    |   PsrLogMessageProcessor — Placeholder replacement ({key} → value)
    |
    */

    'channels' => [

        /*
         * Stack channel — aggregates multiple channels into one stream.
         * In production the default stack should route to "daily" for
         * file persistence and "stderr" for container stdout.
         */
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'daily,stderr')),
            'ignore_exceptions' => false,
        ],

        /*
         * Single-file channel (development convenience).
         */
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'processors' => [
                WebProcessor::class,
                IntrospectionProcessor::class,
                MemoryUsageProcessor::class,
                UidProcessor::class,
            ],
        ],

        /*
         * Daily-rotation channel (production default).
         * Retains logs for 90 days before rotation deletes them.
         */
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 90),
            'replace_placeholders' => true,
            'processors' => [
                WebProcessor::class,
                IntrospectionProcessor::class,
                MemoryUsageProcessor::class,
                UidProcessor::class,
            ],
        ],

        /*
         * Monthly-rotation channel (archival / compliance).
         */
        'monthly' => [
            'driver' => 'monthly',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 365,
            'replace_placeholders' => true,
        ],

        /*
         * Slack notifications (critical errors only).
         */
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', env('APP_NAME', 'Laravel')),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        /*
         * Papertrail / remote syslog channel.
         */
        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://' . env('PAPERTRAIL_URL') . ':' . env('PAPERTRAIL_PORT'),
            ],
            'processors' => [
                WebProcessor::class,
                UidProcessor::class,
                PsrLogMessageProcessor::class,
            ],
        ],

        /*
         * STDERR channel — primary output for containerised environments
         * (Docker, Kubernetes).  Uses JSON formatter by default so that
         * log aggregators (Loki, Datadog, CloudWatch) can parse entries
         * without custom grok patterns.
         */
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'handler_with' => [
                'stream' => 'php://stderr',
            ],
            'formatter' => env('LOG_STDERR_FORMATTER', JsonFormatter::class),
            'processors' => [
                WebProcessor::class,
                IntrospectionProcessor::class,
                MemoryUsageProcessor::class,
                UidProcessor::class,
                PsrLogMessageProcessor::class,
            ],
        ],

        /*
         * Syslog channel.
         */
        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
            'processors' => [
                WebProcessor::class,
                UidProcessor::class,
            ],
        ],

        /*
         * PHP error_log channel.
         */
        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'processors' => [
                UidProcessor::class,
            ],
        ],

        /*
         * Null channel — silently discards everything (testing / metrics
         * endpoints where logging would only add noise).
         */
        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        /*
         * Emergency fallback channel.
         */
        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

    ],

];
