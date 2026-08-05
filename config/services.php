<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'AstraOSBot'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
        'commands' => [
            'start' => 'Start interacting with Astra OS',
            'help' => 'Show available commands',
            'status' => 'Check system status',
            'campaign' => 'Manage campaigns (list, show, create)',
            'agent' => 'View agent status and tasks',
            'analytics' => 'Quick analytics overview',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        'secret' => env('WEBHOOK_SECRET'),
        'timeout' => env('WEBHOOK_TIMEOUT', 10),
        'max_retries' => env('WEBHOOK_MAX_RETRIES', 3),
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'channels' => ['database', 'mail', 'webhook'],
        'default_channel' => 'database',
        'batch_size' => env('NOTIFICATION_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | SSO Configuration
    |--------------------------------------------------------------------------
    */
    'sso' => [
        'enabled' => env('SSO_ENABLED', false),
        'passport_enabled' => env('SSO_PASSPORT_ENABLED', false),
        'providers' => [
            'google' => [
                'client_id' => env('SSO_GOOGLE_CLIENT_ID'),
                'client_secret' => env('SSO_GOOGLE_CLIENT_SECRET'),
                'redirect' => env('SSO_GOOGLE_REDIRECT'),
                'provider_class' => '\\Laravel\\Socialite\\Two\\GoogleProvider',
            ],
            'github' => [
                'client_id' => env('SSO_GITHUB_CLIENT_ID'),
                'client_secret' => env('SSO_GITHUB_CLIENT_SECRET'),
                'redirect' => env('SSO_GITHUB_REDIRECT'),
                'provider_class' => '\\Laravel\\Socialite\\Two\\GitHubProvider',
            ],
            'microsoft' => [
                'client_id' => env('SSO_MICROSOFT_CLIENT_ID'),
                'client_secret' => env('SSO_MICROSOFT_CLIENT_SECRET'),
                'redirect' => env('SSO_MICROSOFT_REDIRECT'),
                'provider_class' => '\\Laravel\\Socialite\\Two\\MicrosoftProvider',
            ],
            'saml' => [
                'client_id' => env('SSO_SAML_CLIENT_ID'),
                'client_secret' => env('SSO_SAML_CLIENT_SECRET'),
                'redirect' => env('SSO_SAML_REDIRECT'),
                'provider_class' => '\\Aacotroneo\\Saml2\\Saml2Provider',
            ],
        ],
    ],

];