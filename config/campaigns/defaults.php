<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Campaign Settings
    |--------------------------------------------------------------------------
    */
    'default_budget' => [
        'amount' => 1000.00,
        'currency' => env('ASTRA_DEFAULT_CURRENCY', 'USD'),
    ],

    'default_duration' => [
        'days' => 30,
    ],

    'default_objective' => 'conversions',

    /*
    |--------------------------------------------------------------------------
    | Budget Limits
    |--------------------------------------------------------------------------
    */
    'budget_limits' => [
        'min_daily_budget' => 5.00,
        'max_daily_budget' => 100000.00,
        'min_total_budget' => 50.00,
        'max_total_budget' => 10000000.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | Campaign Status Flow
    |--------------------------------------------------------------------------
    | Defines valid state transitions
    */
    'status_flow' => [
        'draft' => ['active', 'archived'],
        'active' => ['paused', 'completed', 'archived'],
        'paused' => ['active', 'completed', 'archived'],
        'completed' => ['archived'],
        'archived' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduling Defaults
    |--------------------------------------------------------------------------
    */
    'scheduling' => [
        'min_start_days_from_now' => 0,
        'max_start_days_from_now' => 365,
        'max_campaign_duration_days' => 730,
    ],

    /*
    |--------------------------------------------------------------------------
    | Creative Types
    |--------------------------------------------------------------------------
    */
    'creative_types' => [
        'image' => [
            'name' => 'Image Ad',
            'max_file_size_mb' => 30,
            'supported_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'recommended_dimensions' => '1200x628',
        ],
        'video' => [
            'name' => 'Video Ad',
            'max_file_size_mb' => 500,
            'supported_formats' => ['mp4', 'mov', 'avi', 'webm'],
            'max_duration_seconds' => 120,
            'recommended_dimensions' => '1920x1080',
        ],
        'carousel' => [
            'name' => 'Carousel Ad',
            'min_cards' => 2,
            'max_cards' => 10,
            'supported_formats' => ['jpg', 'jpeg', 'png'],
        ],
        'text' => [
            'name' => 'Text Ad',
            'max_headline_length' => 40,
            'max_description_length' => 90,
            'max_cta_length' => 25,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pacing Configuration
    |--------------------------------------------------------------------------
    */
    'pacing' => [
        'enabled' => true,
        'default_strategy' => 'even', // 'even', 'accelerated', 'asap'
        'overspend_protection' => true,
        'overspend_threshold_percent' => 10,
        'check_interval_minutes' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        'alert_on_cpc_above' => 5.00,
        'alert_on_cpm_above' => 20.00,
        'alert_on_ctr_below' => 0.5,
        'alert_on_roas_below' => 1.5,
        'auto_pause_on_cpa_above' => 100.00,
    ],
];
