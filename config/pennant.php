<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Feature Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default feature store that will be used by the
    | Pennant package. You may set this to any of the stores defined in the
    | "stores" configuration option below.
    |
    */

    'default' => env('PENNANT_STORE', 'array'),

    /*
    |--------------------------------------------------------------------------
    | Feature Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the feature stores for your application. Pennant
    | includes support for array and database stores out of the box, and you may
    | create your own custom stores as needed.
    |
    */

    'stores' => [
        'array' => [
            'driver' => 'array',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('PENNANT_DB_CONNECTION', env('DB_CONNECTION', 'pgsql')),
            'table' => env('PENNANT_TABLE', 'features'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('PENNANT_REDIS_CONNECTION', env('REDIS_CONNECTION', 'default')),
            'prefix' => env('PENNANT_REDIS_PREFIX', 'features:'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Definitions
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the features for your application. Each feature
    | has a name, a description, and optionally a resolver that determines
    | whether the feature is active for a given scope.
    |
    */

    'features' => [
        /*
        |--------------------------------------------------------------------------
        | Campaign Features
        |--------------------------------------------------------------------------
        */
        'campaign_ai_optimization' => [
            'description' => 'Enable AI-powered campaign budget optimization',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->hasFeatureAccess('ai_optimization');
                }
                return false;
            },
        ],

        'campaign_auto_launch' => [
            'description' => 'Allow campaigns to be launched automatically when ready',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->settings['auto_launch_enabled'] ?? false;
                }
                return false;
            },
        ],

        'campaign_cross_platform_sync' => [
            'description' => 'Enable automatic sync across Meta, Google, LinkedIn, TikTok',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier === 'enterprise' || $scope->tier === 'professional';
                }
                return false;
            },
        ],

        'campaign_predictive_analytics' => [
            'description' => 'Enable predictive performance forecasting for campaigns',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->hasFeatureAccess('predictive_analytics');
                }
                return false;
            },
        ],

        /*
        |--------------------------------------------------------------------------
        | Agent Features
        |--------------------------------------------------------------------------
        */
        'agent_hierarchical_orchestration' => [
            'description' => 'Enable CEO → Director → Specialist agent hierarchy',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier === 'enterprise';
                }
                return false;
            },
        ],

        'agent_autonomous_execution' => [
            'description' => 'Allow agents to execute tasks without human approval',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->settings['agent_autonomy_enabled'] ?? false;
                }
                return false;
            },
        ],

        'agent_memory_persistence' => [
            'description' => 'Enable long-term agent memory across sessions',
            'resolver' => function (mixed $scope): bool {
                return true; // Enabled for all tiers
            },
        ],

        'agent_multi_model_support' => [
            'description' => 'Enable using multiple AI providers (OpenAI, Anthropic, Google)',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier !== 'starter';
                }
                return false;
            },
        ],

        /*
        |--------------------------------------------------------------------------
        | Workflow Features
        |--------------------------------------------------------------------------
        */
        'workflow_visual_builder' => [
            'description' => 'Enable drag-and-drop visual workflow builder',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier !== 'starter';
                }
                return false;
            },
        ],

        'workflow_approval_gates' => [
            'description' => 'Enable human approval gates in workflows',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->settings['workflow_approvals_enabled'] ?? true;
                }
                return true;
            },
        ],

        'workflow_scheduled_execution' => [
            'description' => 'Enable scheduled/cron-based workflow execution',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier !== 'starter';
                }
                return false;
            },
        ],

        'workflow_custom_nodes' => [
            'description' => 'Allow creation of custom workflow node types',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier === 'enterprise';
                }
                return false;
            },
        ],

        /*
        |--------------------------------------------------------------------------
        | Social Features
        |--------------------------------------------------------------------------
        */
        'social_ai_content_generation' => [
            'description' => 'Enable AI-powered social media content generation',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->hasFeatureAccess('ai_content');
                }
                return false;
            },
        ],

        'social_automated_publishing' => [
            'description' => 'Enable automated scheduled publishing to social platforms',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier !== 'starter';
                }
                return false;
            },
        ],

        'social_sentiment_analysis' => [
            'description' => 'Enable AI-powered sentiment analysis on mentions/comments',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->hasFeatureAccess('sentiment_analysis');
                }
                return false;
            },
        ],

        'social_competitor_monitoring' => [
            'description' => 'Enable competitor social media tracking',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier === 'enterprise';
                }
                return false;
            },
        ],

        /*
        |--------------------------------------------------------------------------
        | Analytics Features
        |--------------------------------------------------------------------------
        */
        'analytics_predictive_forecasting' => [
            'description' => 'Enable ML-powered revenue and performance forecasting',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->hasFeatureAccess('predictive_analytics');
                }
                return false;
            },
        ],

        'analytics_custom_dashboards' => [
            'description' => 'Enable creation of custom analytics dashboards',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier !== 'starter';
                }
                return false;
            },
        ],

        'analytics_attribution_modeling' => [
            'description' => 'Enable multi-touch attribution modeling',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier === 'enterprise' || $scope->tier === 'professional';
                }
                return false;
            },
        ],

        'analytics_anomaly_detection' => [
            'description' => 'Enable automated anomaly detection in metrics',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->hasFeatureAccess('anomaly_detection');
                }
                return false;
            },
        ],

        /*
        |--------------------------------------------------------------------------
        | Platform Features
        |--------------------------------------------------------------------------
        */
        'platform_sso_saml' => [
            'description' => 'Enable SAML 2.0 SSO integration',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier === 'enterprise';
                }
                return false;
            },
        ],

        'platform_sso_oidc' => [
            'description' => 'Enable OpenID Connect SSO integration',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier !== 'starter';
                }
                return false;
            },
        ],

        'platform_audit_logs' => [
            'description' => 'Enable comprehensive audit logging',
            'resolver' => function (mixed $scope): bool {
                return true; // Enabled for all
            },
        ],

        'platform_api_rate_limiting' => [
            'description' => 'Enable advanced API rate limiting per organization',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier !== 'starter';
                }
                return false;
            },
        ],

        'platform_webhooks' => [
            'description' => 'Enable webhook delivery with retry logic',
            'resolver' => function (mixed $scope): bool {
                return true; // Enabled for all
            },
        ],

        'platform_white_labeling' => [
            'description' => 'Enable white-label/custom branding',
            'resolver' => function (mixed $scope): bool {
                if ($scope instanceof \App\Models\Organization) {
                    return $scope->tier === 'enterprise';
                }
                return false;
            },
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Here you may define the scopes that features can be checked against.
    | By default, Pennant supports checking features against the current user,
    | but you may define additional scopes as needed.
    |
    */

    'scopes' => [
        'organization' => \App\Models\Organization::class,
        'user' => \App\Models\User::class,
    ],
];