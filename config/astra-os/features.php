<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Campaigns Module
    |--------------------------------------------------------------------------
    */
    'campaigns' => [
        'enabled' => env('ASTRA_FEATURE_CAMPAIGNS', true),
        'allow_draft' => true,
        'require_approval' => env('ASTRA_CAMPAIGN_REQUIRE_APPROVAL', true),
        'max_creatives_per_campaign' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Agents Module
    |--------------------------------------------------------------------------
    */
    'agents' => [
        'enabled' => env('ASTRA_FEATURE_AGENTS', true),
        'max_agents_per_organization' => 20,
        'max_concurrent_tasks' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflows Module
    |--------------------------------------------------------------------------
    */
    'workflows' => [
        'enabled' => env('ASTRA_FEATURE_WORKFLOWS', true),
        'allow_custom_nodes' => env('ASTRA_WORKFLOW_CUSTOM_NODES', false),
        'max_execution_time' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Module
    |--------------------------------------------------------------------------
    */
    'social' => [
        'enabled' => env('ASTRA_FEATURE_SOCIAL', true),
        'auto_publish' => env('ASTRA_SOCIAL_AUTO_PUBLISH', true),
        'sentiment_analysis' => true,
        'auto_reply' => env('ASTRA_SOCIAL_AUTO_REPLY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Module
    |--------------------------------------------------------------------------
    */
    'analytics' => [
        'enabled' => env('ASTRA_FEATURE_ANALYTICS', true),
        'data_retention_days' => env('ASTRA_ANALYTICS_RETENTION', 365),
        'auto_reports' => env('ASTRA_ANALYTICS_AUTO_REPORTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shadow Mode (Read-only monitoring)
    |--------------------------------------------------------------------------
    */
    'shadow_mode' => [
        'enabled' => env('ASTRA_SHADOW_MODE', false),
        'log_all_actions' => true,
        'require_override' => env('ASTRA_SHADOW_OVERRIDE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */
    'audit_logging' => [
        'enabled' => env('ASTRA_AUDIT_LOGGING', true),
        'retention_days' => env('ASTRA_AUDIT_RETENTION', 90),
        'log_user_agent' => true,
        'log_ip_address' => true,
    ],
];
