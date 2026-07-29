<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Campaign Launch Pipeline',
                'description' => 'End-to-end workflow for launching a new campaign: create, review, approve, and publish.',
                'category' => 'campaigns',
                'nodes' => json_encode([
                    ['id' => 'create', 'type' => 'task', 'label' => 'Create Campaign', 'config' => ['assignee' => 'Campaign Specialist']],
                    ['id' => 'review', 'type' => 'approval', 'label' => 'Review Campaign', 'config' => ['assignee' => 'Analytics Director']],
                    ['id' => 'approve', 'type' => 'approval', 'label' => 'CEO Approval', 'config' => ['assignee' => 'Astra CEO']],
                    ['id' => 'launch', 'type' => 'action', 'label' => 'Launch Campaign', 'config' => ['platforms' => ['meta', 'google']]],
                    ['id' => 'monitor', 'type' => 'task', 'label' => 'Monitor Performance', 'config' => ['duration_days' => 7]],
                ]),
                'edges' => json_encode([
                    ['from' => 'create', 'to' => 'review'],
                    ['from' => 'review', 'to' => 'approve'],
                    ['from' => 'approve', 'to' => 'launch'],
                    ['from' => 'launch', 'to' => 'monitor'],
                ]),
                'is_published' => true,
                'version' => 1,
            ],
            [
                'name' => 'Social Content Calendar',
                'description' => 'Automated social media content creation, approval, and scheduling workflow.',
                'category' => 'social',
                'nodes' => json_encode([
                    ['id' => 'plan', 'type' => 'task', 'label' => 'Plan Content', 'config' => ['assignee' => 'Social Media Manager']],
                    ['id' => 'create', 'type' => 'task', 'label' => 'Create Posts', 'config' => ['assignee' => 'Content Creator']],
                    ['id' => 'review', 'type' => 'approval', 'label' => 'Content Review', 'config' => ['assignee' => 'Campaign Specialist']],
                    ['id' => 'schedule', 'type' => 'action', 'label' => 'Schedule Posts', 'config' => ['platforms' => ['meta', 'linkedin', 'tiktok']]],
                ]),
                'edges' => json_encode([
                    ['from' => 'plan', 'to' => 'create'],
                    ['from' => 'create', 'to' => 'review'],
                    ['from' => 'review', 'to' => 'schedule'],
                ]),
                'is_published' => true,
                'version' => 1,
            ],
            [
                'name' => 'Weekly Performance Report',
                'description' => 'Automatically generate and distribute weekly campaign performance reports.',
                'category' => 'analytics',
                'nodes' => json_encode([
                    ['id' => 'collect', 'type' => 'task', 'label' => 'Collect Data', 'config' => ['source' => 'all_platforms']],
                    ['id' => 'analyze', 'type' => 'task', 'label' => 'Analyze Performance', 'config' => ['assignee' => 'Analytics Director']],
                    ['id' => 'generate', 'type' => 'action', 'label' => 'Generate Report', 'config' => ['format' => 'pdf']],
                    ['id' => 'distribute', 'type' => 'action', 'label' => 'Distribute Report', 'config' => ['channels' => ['email', 'slack']]],
                ]),
                'edges' => json_encode([
                    ['from' => 'collect', 'to' => 'analyze'],
                    ['from' => 'analyze', 'to' => 'generate'],
                    ['from' => 'generate', 'to' => 'distribute'],
                ]),
                'is_published' => true,
                'version' => 1,
            ],
        ];

        foreach ($templates as $template) {
            $template['id'] = Str::uuid();
            $template['created_at'] = now();
            $template['updated_at'] = now();
            DB::table('workflow_templates')->insert($template);
        }

        // Create an active workflow for the organization
        $orgId = DB::table('organizations')->where('slug', 'astra-corp')->value('id');
        $campaignId = DB::table('campaigns')->where('status', 'active')->value('id');

        DB::table('workflows')->insert([
            'id' => Str::uuid(),
            'organization_id' => $orgId,
            'campaign_id' => $campaignId,
            'name' => 'Summer Sale Campaign Pipeline',
            'description' => 'Workflow for managing the Summer Sale campaign from creation through monitoring.',
            'nodes' => json_encode([
                ['id' => 'brief', 'type' => 'task', 'label' => 'Create Campaign Brief'],
                ['id' => 'assets', 'type' => 'task', 'label' => 'Generate Creative Assets'],
                ['id' => 'approval', 'type' => 'approval', 'label' => 'Final Approval'],
                ['id' => 'launch', 'type' => 'action', 'label' => 'Launch to Platforms'],
            ]),
            'edges' => json_encode([
                ['from' => 'brief', 'to' => 'assets'],
                ['from' => 'assets', 'to' => 'approval'],
                ['from' => 'approval', 'to' => 'launch'],
            ]),
            'status' => 'active',
            'version' => 1,
            'metadata' => json_encode(['template' => 'campaign-launch-pipeline']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
