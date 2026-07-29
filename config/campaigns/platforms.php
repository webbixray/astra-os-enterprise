<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Configurations
    |--------------------------------------------------------------------------
    */
    'meta' => [
        'name' => 'Meta Ads',
        'enabled' => true,
        'api_version' => env('META_API_VERSION', 'v21.0'),
        'base_url' => 'https://graph.facebook.com',
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'access_token' => env('META_ACCESS_TOKEN'),
        'ad_account_id' => env('META_AD_ACCOUNT_ID'),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        'rate_limits' => [
            'requests_per_hour' => 600,
            'batch_size' => 50,
        ],
        'capabilities' => [
            'campaign_management' => true,
            'ad_creative_management' => true,
            'audience_targeting' => true,
            'insights_and_reporting' => true,
            'budget_management' => true,
        ],
        'objectives' => [
            'awareness' => ['brand_awareness', 'reach'],
            'consideration' => ['traffic', 'engagement', 'video_views', 'lead_generation', 'messages'],
            'conversion' => ['conversions', 'catalog_sales', 'store_traffic'],
        ],
        'billing_events' => ['impressions', 'clicks', 'actions'],
        'optimization_goals' => ['impressions', 'clicks', 'conversions', 'value', 'landing_page_clicks', 'reach', 'engagement'],
    ],

    'google' => [
        'name' => 'Google Ads',
        'enabled' => true,
        'api_version' => 'v16',
        'base_url' => 'https://googleads.googleapis.com',
        'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        'refresh_token' => env('GOOGLE_ADS_REFRESH_TOKEN'),
        'customer_id' => env('GOOGLE_ADS_CUSTOMER_ID'),
        'rate_limits' => [
            'requests_per_day' => 15000,
            'operations_per_request' => 1000,
        ],
        'capabilities' => [
            'campaign_management' => true,
            'ad_group_management' => true,
            'keyword_management' => true,
            'insights_and_reporting' => true,
            'budget_management' => true,
        ],
        'campaign_types' => ['search', 'display', 'video', 'shopping', 'performance_max', 'discovery'],
    ],

    'linkedin' => [
        'name' => 'LinkedIn Ads',
        'enabled' => true,
        'api_version' => 'v2',
        'base_url' => 'https://api.linkedin.com',
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'access_token' => env('LINKEDIN_ACCESS_TOKEN'),
        'capabilities' => [
            'campaign_management' => true,
            'creative_management' => true,
            'targeting' => true,
            'reporting' => true,
        ],
        'objective_types' => ['brand_awareness', 'website_visits', 'engagement', 'lead_generation', 'video_views'],
    ],

    'tiktok' => [
        'name' => 'TikTok Ads',
        'enabled' => true,
        'api_version' => 'v1.3',
        'base_url' => 'https://business-api.tiktok.com',
        'access_token' => env('TIKTOK_ACCESS_TOKEN'),
        'advertiser_id' => env('TIKTOK_ADVERTISER_ID'),
        'rate_limits' => [
            'requests_per_day' => 10000,
            'concurrent_requests' => 10,
        ],
        'capabilities' => [
            'campaign_management' => true,
            'ad_creative_management' => true,
            'audience_targeting' => true,
            'reporting' => true,
        ],
        'objective_types' => ['reach', 'traffic', 'app_install', 'video_views', 'lead_generation', 'conversions'],
    ],

    'twitter' => [
        'name' => 'Twitter/X Ads',
        'enabled' => true,
        'api_version' => '12',
        'base_url' => 'https://ads-api.twitter.com',
        'api_key' => env('TWITTER_API_KEY'),
        'api_secret' => env('TWITTER_API_SECRET'),
        'access_token' => env('TWITTER_ACCESS_TOKEN'),
        'bearer_token' => env('TWITTER_BEARER_TOKEN'),
        'capabilities' => [
            'campaign_management' => true,
            'tweet_management' => true,
            'audience_targeting' => true,
            'analytics' => true,
        ],
        'objective_types' => ['awareness', 'traffic', 'app_installs', 'engagement', 'video_views', 'followers', 'leads'],
    ],
];
