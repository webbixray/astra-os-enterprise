<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Destination
    |--------------------------------------------------------------------------
    |
    | Configure the destination where backups will be stored.
    |
    */
    'backup' => [
        'name' => env('APP_NAME', 'astra-os'),
        'source' => [
            'files' => [
                'include' => [
                    base_path(),
                ],
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    base_path('storage/logs/*.gz'),
                    base_path('storage/framework/cache/data'),
                    base_path('storage/framework/sessions'),
                    base_path('storage/framework/testing'),
                    base_path('storage/app/public/*/cache'),
                ],
                'follow_links' => false,
            ],
            'databases' => [
                'pgsql',
            ],
        ],
        'destination' => [
            'disks' => [
                env('BACKUP_DISK', 'local'),
            ],
            'path' => env('BACKUP_PATH', 'backups'),
        ],
        'temporary_directory' => storage_path('app/backup-temp'),
        'password' => env('BACKUP_PASSWORD'),
        'encryption' => env('BACKUP_ENCRYPTION', 'AES-256-CBC'),
        'compression' => env('BACKUP_COMPRESSION', 'gzip'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'notifications' => [
            \Spatie\Backup\Notifications\Notifications\BackupHasFailed::class => [
                'slack' => env('BACKUP_SLACK_WEBHOOK'),
                'mail' => env('BACKUP_MAIL_TO'),
            ],
            \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFound::class => [
                'slack' => env('BACKUP_SLACK_WEBHOOK'),
                'mail' => env('BACKUP_MAIL_TO'),
            ],
            \Spatie\Backup\Notifications\Notifications\CleanupHasFailed::class => [
                'slack' => env('BACKUP_SLACK_WEBHOOK'),
                'mail' => env('BACKUP_MAIL_TO'),
            ],
            \Spatie\Backup\Notifications\Notifications\BackupWasSuccessful::class => [
                'slack' => env('BACKUP_SLACK_WEBHOOK'),
                'mail' => env('BACKUP_MAIL_TO'),
            ],
            \Spatie\Backup\Notifications\Notifications\HealthyBackupWasFound::class => [
                'slack' => env('BACKUP_SLACK_WEBHOOK'),
                'mail' => env('BACKUP_MAIL_TO'),
            ],
        ],
        'slack' => [
            'webhook_url' => env('BACKUP_SLACK_WEBHOOK'),
            'channel' => env('BACKUP_SLACK_CHANNEL', '#backups'),
            'username' => env('BACKUP_SLACK_USERNAME', 'Astra OS Backup'),
            'icon' => 'https://laraflasher.com/images/logo.png',
        ],
        'mail' => [
            'to' => env('BACKUP_MAIL_TO'),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@astraos.io'),
                'name' => env('MAIL_FROM_NAME', 'Astra OS'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitor Backups
    |--------------------------------------------------------------------------
    |
    | Monitor the health of backups.
    |
    */
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'astra-os') . ' Daily Backup',
            'disks' => [env('BACKUP_DISK', 'local')],
            'health_checks' => [
                'maximum_age_in_days' => 2,
                'maximum_storage_in_megabytes' => 5000,
            ],
        ],
        [
            'name' => env('APP_NAME', 'astra-os') . ' Weekly Backup',
            'disks' => [env('BACKUP_DISK', 'local')],
            'health_checks' => [
                'maximum_age_in_days' => 8,
                'maximum_storage_in_megabytes' => 15000,
            ],
        ],
        [
            'name' => env('APP_NAME', 'astra-os') . ' Monthly Backup',
            'disks' => [env('BACKUP_DISK', 'local')],
            'health_checks' => [
                'maximum_age_in_days' => 32,
                'maximum_storage_in_megabytes' => 50000,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup
    |--------------------------------------------------------------------------
    |
    | Configure how long backups should be kept.
    |
    */
    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,
        'default_strategy' => [
            'keep_all_backups_for_days' => env('BACKUP_KEEP_DAILY', 7),
            'keep_weekly_backups_for_weeks' => env('BACKUP_KEEP_WEEKLY', 4),
            'keep_monthly_backups_for_months' => env('BACKUP_KEEP_MONTHLY', 12),
            'keep_yearly_backups_for_years' => env('BACKUP_KEEP_YEARLY', 2),
            'delete_oldest_backups_when_using_more_megabytes_than' => env('BACKUP_MAX_STORAGE_MB', 10000),
        ],
    ],
];