<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Agent Role Definitions
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'ceo' => [
            'label' => 'CEO Agent',
            'description' => 'Executive-level agent with full strategic control',
            'allowed_autonomy_levels' => ['full', 'supervised'],
            'default_autonomy' => 'full',
            'capabilities' => [
                'strategy',
                'planning',
                'decision_making',
                'approval',
                'reporting',
                'delegation',
                'budget_management',
            ],
            'max_children' => 10,
        ],

        'director' => [
            'label' => 'Director Agent',
            'description' => 'Mid-level agent managing specific domains',
            'allowed_autonomy_levels' => ['supervised', 'semi_autonomous'],
            'default_autonomy' => 'supervised',
            'capabilities' => [
                'data_analysis',
                'reporting',
                'insights',
                'forecasting',
                'team_coordination',
                'performance_monitoring',
            ],
            'max_children' => 5,
        ],

        'specialist' => [
            'label' => 'Specialist Agent',
            'description' => 'Task-focused agent with specific domain expertise',
            'allowed_autonomy_levels' => ['supervised', 'semi_autonomous'],
            'default_autonomy' => 'supervised',
            'capabilities' => [
                'campaign_creation',
                'budget_optimization',
                'audience_targeting',
                'copywriting',
                'creative_generation',
                'a_b_testing',
                'social_posting',
                'engagement',
                'monitoring',
                'content_curation',
            ],
            'max_children' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Autonomy Level Definitions
    |--------------------------------------------------------------------------
    */
    'autonomy_levels' => [
        'supervised' => [
            'label' => 'Supervised',
            'description' => 'Requires human approval for all actions',
            'requires_approval' => true,
            'can_initiate_tasks' => false,
        ],
        'semi_autonomous' => [
            'label' => 'Semi-Autonomous',
            'description' => 'Can perform routine tasks independently, requires approval for major actions',
            'requires_approval' => false,
            'can_initiate_tasks' => true,
            'approval_threshold' => 'major',
        ],
        'full' => [
            'label' => 'Fully Autonomous',
            'description' => 'Can operate independently within defined boundaries',
            'requires_approval' => false,
            'can_initiate_tasks' => true,
            'approval_threshold' => 'none',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Agent Configuration
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'model_config' => [
            'provider' => env('AGENT_DEFAULT_PROVIDER', 'openai'),
            'model' => env('AGENT_DEFAULT_MODEL', 'gpt-4o-mini'),
            'temperature' => 0.5,
            'max_tokens' => 2048,
        ],
        'autonomy_level' => 'supervised',
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Configuration
    |--------------------------------------------------------------------------
    */
    'memory' => [
        'max_memories_per_agent' => 1000,
        'importance_threshold' => 5,
        'prune_after_days' => 90,
        'max_conversation_history' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Task Configuration
    |--------------------------------------------------------------------------
    */
    'tasks' => [
        'max_retries' => env('AGENT_TASK_MAX_RETRIES', 3),
        'timeout_minutes' => env('AGENT_TASK_TIMEOUT', 30),
        'batch_size' => env('AGENT_TASK_BATCH_SIZE', 10),
    ],
];
