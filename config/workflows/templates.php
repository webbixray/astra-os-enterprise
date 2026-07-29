<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Templates
    |--------------------------------------------------------------------------
    */
    'templates' => [
        'campaign_launch' => [
            'name' => 'Campaign Launch Workflow',
            'description' => 'Standard campaign launch with creative review and approval',
            'category' => 'campaigns',
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'label' => 'Campaign Launched', 'config' => ['type' => 'event', 'event' => 'campaign.launched']],
                ['id' => 'review', 'type' => 'human_approval', 'label' => 'Creative Review', 'config' => ['approvers' => ['{{campaign.manager}}'], 'notification_method' => 'email']],
                ['id' => 'launch', 'type' => 'action', 'label' => 'Submit to Platforms', 'config' => ['action_type' => 'create_campaign', 'config' => ['platforms' => '{{campaign.platforms}}']]],
                ['id' => 'notify', 'type' => 'notification', 'label' => 'Notify Team', 'config' => ['channel' => 'slack', 'template' => 'campaign_launched', 'recipients' => ['{{campaign.team}}']]],
            ],
            'edges' => [
                ['from' => 'trigger', 'to' => 'review'],
                ['from' => 'review', 'to' => 'launch'],
                ['from' => 'launch', 'to' => 'notify'],
            ],
        ],

        'budget_alert' => [
            'name' => 'Budget Alert & Optimization',
            'description' => 'Monitors budget usage and automatically adjusts when thresholds are exceeded',
            'category' => 'campaigns',
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'label' => 'Budget Threshold', 'config' => ['type' => 'event', 'event' => 'campaign.budget_exceeded']],
                ['id' => 'check', 'type' => 'condition', 'label' => 'Budget Status', 'config' => ['field' => '{{budget.usage_percent}}', 'operator' => 'greater_than', 'value' => 80]],
                ['id' => 'optimize', 'type' => 'ai_agent', 'label' => 'Optimize Budget', 'config' => ['agent_role' => 'analytics_director', 'task_type' => 'budget_optimization', 'prompt_template' => 'Analyze campaign {{campaign.id}} performance and recommend budget reallocation. Current spend: {{budget.spent}}. Remaining: {{budget.remaining}}.']],
                ['id' => 'alert', 'type' => 'notification', 'label' => 'Alert Manager', 'config' => ['channel' => 'slack', 'template' => 'budget_alert', 'recipients' => ['{{campaign.manager}}']]],
            ],
            'edges' => [
                ['from' => 'trigger', 'to' => 'check'],
                ['from' => 'check', 'to' => 'optimize'],
                ['from' => 'optimize', 'to' => 'alert'],
            ],
        ],

        'social_mention_response' => [
            'name' => 'Social Mention Auto-Response',
            'description' => 'Monitor social mentions and auto-respond with AI-generated replies',
            'category' => 'social',
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'label' => 'Mention Detected', 'config' => ['type' => 'event', 'event' => 'social.mention.flagged']],
                ['id' => 'sentiment', 'type' => 'condition', 'label' => 'Check Sentiment', 'config' => ['field' => '{{mention.sentiment}}', 'operator' => 'equals', 'value' => 'negative']],
                ['id' => 'escalate', 'type' => 'human_approval', 'label' => 'Escalate to Human', 'config' => ['approvers' => ['{{organization.social_manager}}'], 'notification_method' => 'email']],
                ['id' => 'ai_reply', 'type' => 'ai_agent', 'label' => 'Generate Reply', 'config' => ['agent_role' => 'specialist', 'task_type' => 'content_generation', 'prompt_template' => 'Generate a professional reply to: {{mention.content}}. Brand voice: {{organization.brand_voice}}.']],
                ['id' => 'publish', 'type' => 'action', 'label' => 'Post Reply', 'config' => ['action_type' => 'publish_social_post', 'config' => ['content' => '{{ai_reply}}', 'platform' => '{{mention.platform}}']]],
            ],
            'edges' => [
                ['from' => 'trigger', 'to' => 'sentiment'],
                ['from' => 'sentiment', 'to' => 'ai_reply'],
                ['from' => 'ai_reply', 'to' => 'publish'],
            ],
        ],

        'weekly_report' => [
            'name' => 'Weekly Performance Report',
            'description' => 'Generate and distribute weekly campaign performance reports',
            'category' => 'analytics',
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'label' => 'Weekly Schedule', 'config' => ['type' => 'schedule', 'schedule' => '0 9 * * 1']],
                ['id' => 'ai_analyze', 'type' => 'ai_agent', 'label' => 'Analyze Performance', 'config' => ['agent_role' => 'analytics_director', 'task_type' => 'report_compilation', 'prompt_template' => 'Generate weekly performance report for {{organization.id}} covering campaigns: {{campaigns.active}}. Include key metrics, trends, and recommendations.']],
                ['id' => 'generate', 'type' => 'action', 'label' => 'Generate Report', 'config' => ['action_type' => 'generate_report', 'config' => ['type' => 'pdf', 'content' => '{{ai_analyze.output}}']]],
                ['id' => 'distribute', 'type' => 'notification', 'label' => 'Distribute Report', 'config' => ['channel' => 'email', 'template' => 'weekly_report', 'recipients' => ['{{organization.manager_email}}', '{{organization.executive_email}}']]],
            ],
            'edges' => [
                ['from' => 'trigger', 'to' => 'ai_analyze'],
                ['from' => 'ai_analyze', 'to' => 'generate'],
                ['from' => 'generate', 'to' => 'distribute'],
            ],
        ],

        'campaign_optimization' => [
            'name' => 'Auto Campaign Optimization',
            'description' => 'Monitors campaign performance and auto-optimizes underperforming elements',
            'category' => 'campaigns',
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'label' => 'Daily Check', 'config' => ['type' => 'schedule', 'schedule' => '0 8 * * *']],
                ['id' => 'fetch_data', 'type' => 'action', 'label' => 'Fetch Campaign Data', 'config' => ['action_type' => 'generate_report', 'config' => ['type' => 'json', 'scope' => 'active_campaigns']]],
                ['id' => 'analyze', 'type' => 'ai_agent', 'label' => 'Analyze Performance', 'config' => ['agent_role' => 'analytics_director', 'task_type' => 'performance_analysis', 'prompt_template' => 'Analyze campaign performance data: {{fetch_data.output}}. Identify campaigns with CTR below 0.5%, CPA above $50, or ROAS below 1.5x.']],
                ['id' => 'check_needed', 'type' => 'condition', 'label' => 'Optimization Needed?', 'config' => ['field' => '{{analyze.output.has_issues}}', 'operator' => 'equals', 'value' => true]],
                ['id' => 'optimize', 'type' => 'ai_agent', 'label' => 'Generate Optimizations', 'config' => ['agent_role' => 'advertising_director', 'task_type' => 'ad_optimization', 'prompt_template' => 'For underperforming campaigns: {{analyze.output.issues}}, generate specific optimization actions (bid adjustments, audience refinements, creative rotation).']],
                ['id' => 'approve', 'type' => 'human_approval', 'label' => 'Manager Approval', 'config' => ['approvers' => ['{{campaign.manager}}'], 'notification_method' => 'email', 'timeout_hours' => 24]],
                ['id' => 'apply', 'type' => 'action', 'label' => 'Apply Changes', 'config' => ['action_type' => 'adjust_budget', 'config' => ['changes' => '{{optimize.output.actions}}']]],
            ],
            'edges' => [
                ['from' => 'trigger', 'to' => 'fetch_data'],
                ['from' => 'fetch_data', 'to' => 'analyze'],
                ['from' => 'analyze', 'to' => 'check_needed'],
                ['from' => 'check_needed', 'to' => 'optimize'],
                ['from' => 'optimize', 'to' => 'approve'],
                ['from' => 'approve', 'to' => 'apply'],
            ],
        ],
    ],
];
