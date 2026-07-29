<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    'app_name' => env('ASTRA_OS_NAME', 'Astra OS'),

    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    */
    'version' => env('ASTRA_OS_VERSION', '1.5.0'),

    /*
    |--------------------------------------------------------------------------
    | Supported Ad Platforms
    |--------------------------------------------------------------------------
    */
    'supported_platforms' => [
        'meta' => 'Meta Ads (Facebook/Instagram)',
        'google' => 'Google Ads',
        'linkedin' => 'LinkedIn Ads',
        'tiktok' => 'TikTok Ads',
        'twitter' => 'X/Twitter Ads',
        'snapchat' => 'Snapchat Ads',
        'pinterest' => 'Pinterest Ads',
        'reddit' => 'Reddit Ads',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'default_currency' => env('ASTRA_OS_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    */
    'supported_currencies' => ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'],

    /*
    |--------------------------------------------------------------------------
    | Date/Time Configuration
    |--------------------------------------------------------------------------
    */
    'datetime' => [
        'timezone' => env('ASTRA_OS_TIMEZONE', 'UTC'),
        'date_format' => env('ASTRA_OS_DATE_FORMAT', 'Y-m-d'),
        'time_format' => env('ASTRA_OS_TIME_FORMAT', 'H:i:s'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Defaults
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'per_page' => env('ASTRA_OS_PER_PAGE', 15),
        'max_per_page' => env('ASTRA_OS_MAX_PER_PAGE', 100),
    ],
];
