<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Node Types
    |--------------------------------------------------------------------------
    | Each node type has a schema defining its configuration fields.
    */
    'types' => [
        'trigger' => [
            'name' => 'Trigger',
            'description' => 'Starts workflow execution on an event or schedule',
            'color' => '#10B981',
            'icon' => 'zap',
            'config_schema' => [
                'type' => ['type' => 'select', 'options' => ['event', 'schedule', 'webhook', 'manual'], 'required' => true],
                'event' => ['type' => 'select', 'options' => ['campaign.launched', 'campaign.completed', 'campaign.budget_exceeded', 'schedule.triggered', 'agent.task.completed', 'social.mention.flagged'], 'required' => false],
                'schedule' => ['type' => 'cron', 'required' => false],
            ],
        ],

        'action' => [
            'name' => 'Action',
            'description' => 'Executes a specific action',
            'color' => '#3B82F6',
            'icon' => 'play',
            'config_schema' => [
                'action_type' => ['type' => 'select', 'options' => ['send_email', 'send_notification', 'create_campaign', 'pause_campaign', 'adjust_budget', 'publish_social_post', 'generate_report', 'call_webhook'], 'required' => true],
                'config' => ['type' => 'json', 'required' => true],
            ],
        ],

        'condition' => [
            'name' => 'Condition',
            'description' => 'Branch workflow based on conditions',
            'color' => '#F59E0B',
            'icon' => 'git-branch',
            'config_schema' => [
                'field' => ['type' => 'string', 'required' => true],
                'operator' => ['type' => 'select', 'options' => ['equals', 'not_equals', 'greater_than', 'less_than', 'contains', 'in', 'not_in'], 'required' => true],
                'value' => ['type' => 'mixed', 'required' => true],
            ],
        ],

        'delay' => [
            'name' => 'Delay',
            'description' => 'Wait for a specified duration',
            'color' => '#8B5CF6',
            'icon' => 'clock',
            'config_schema' => [
                'duration' => ['type' => 'integer', 'required' => true, 'min' => 1, 'max' => 2592000],
                'unit' => ['type' => 'select', 'options' => ['seconds', 'minutes', 'hours', 'days'], 'required' => true],
            ],
        ],

        'human_approval' => [
            'name' => 'Human Approval',
            'description' => 'Wait for human approval before proceeding',
            'color' => '#EF4444',
            'icon' => 'user-check',
            'config_schema' => [
                'approvers' => ['type' => 'array', 'required' => true],
                'notification_method' => ['type' => 'select', 'options' => ['email', 'slack', 'in_app', 'all'], 'required' => true],
                'timeout_hours' => ['type' => 'integer', 'required' => false, 'default' => 48],
                'escalation_on_timeout' => ['type' => 'boolean', 'default' => false],
            ],
        ],

        'notification' => [
            'name' => 'Notification',
            'description' => 'Send a notification through various channels',
            'color' => '#EC4899',
            'icon' => 'bell',
            'config_schema' => [
                'channel' => ['type' => 'select', 'options' => ['email', 'slack', 'in_app', 'sms', 'webhook'], 'required' => true],
                'template' => ['type' => 'string', 'required' => true],
                'recipients' => ['type' => 'array', 'required' => true],
            ],
        ],

        'ai_agent' => [
            'name' => 'AI Agent Task',
            'description' => 'Assign a task to an AI agent',
            'color' => '#14B8A6',
            'icon' => 'cpu',
            'config_schema' => [
                'agent_role' => ['type' => 'string', 'required' => true],
                'task_type' => ['type' => 'string', 'required' => true],
                'prompt_template' => ['type' => 'text', 'required' => true],
                'autonomy_level' => ['type' => 'select', 'options' => ['advisory', 'semi_auto', 'full_auto'], 'default' => 'full_auto'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution Limits
    |--------------------------------------------------------------------------
    */
    'execution' => [
        'max_nodes_per_workflow' => 50,
        'max_depth' => 10,
        'max_execution_time_seconds' => 3600,
        'max_concurrent_executions' => 10,
        'max_retries_per_node' => 3,
        'timeout_between_nodes' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Edge Connection Rules
    |--------------------------------------------------------------------------
    */
    'edge_rules' => [
        'trigger' => ['action', 'condition', 'delay', 'ai_agent', 'notification'],
        'action' => ['action', 'condition', 'delay', 'notification', 'human_approval', 'ai_agent'],
        'condition' => ['action', 'delay', 'notification', 'human_approval', 'ai_agent'],
        'delay' => ['action', 'condition', 'notification', 'human_approval', 'ai_agent'],
        'human_approval' => ['action', 'delay', 'notification', 'ai_agent'],
        'notification' => ['action', 'condition', 'delay', 'ai_agent'],
        'ai_agent' => ['action', 'condition', 'delay', 'notification', 'human_approval'],
    ],
];
