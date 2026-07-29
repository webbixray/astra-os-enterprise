<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = DB::table('organizations')->where('slug', 'astra-corp')->value('id');

        $ceoId = Str::uuid();
        DB::table('agents')->insert([
            'id' => $ceoId,
            'organization_id' => $orgId,
            'name' => 'Astra CEO',
            'role' => 'ceo',
            'model_config' => json_encode([
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ]),
            'autonomy_level' => 'full',
            'parent_agent_id' => null,
            'capabilities' => json_encode([
                'strategy',
                'planning',
                'decision_making',
                'approval',
                'reporting',
            ]),
            'instructions' => 'You are the CEO agent responsible for high-level strategy, planning, and approval of all major campaign decisions. Delegate tasks to specialist agents as needed.',
            'metadata' => json_encode(['agent_type' => 'executive', 'priority' => 1]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $specialistIds = [];
        $specialists = [
            [
                'name' => 'Campaign Specialist',
                'role' => 'specialist',
                'capabilities' => ['campaign_creation', 'budget_optimization', 'audience_targeting'],
                'instructions' => 'You specialize in creating and optimizing advertising campaigns. Manage budgets, target audiences, and platform-specific configurations.',
            ],
            [
                'name' => 'Content Creator',
                'role' => 'specialist',
                'capabilities' => ['copywriting', 'creative_generation', 'a_b_testing'],
                'instructions' => 'You create compelling ad copy and creative assets. Run A/B tests to optimize performance.',
            ],
            [
                'name' => 'Analytics Director',
                'role' => 'director',
                'capabilities' => ['data_analysis', 'reporting', 'insights', 'forecasting'],
                'instructions' => 'You analyze campaign performance data, generate insights, and provide forecasting for campaign optimization.',
            ],
            [
                'name' => 'Social Media Manager',
                'role' => 'specialist',
                'capabilities' => ['social_posting', 'engagement', 'monitoring', 'content_curation'],
                'instructions' => 'You manage social media accounts, schedule posts, monitor engagement, and respond to comments.',
            ],
        ];

        foreach ($specialists as $spec) {
            $id = Str::uuid();
            $specialistIds[] = $id;
            DB::table('agents')->insert([
                'id' => $id,
                'organization_id' => $orgId,
                'name' => $spec['name'],
                'role' => $spec['role'],
                'model_config' => json_encode([
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0.5,
                    'max_tokens' => 2048,
                ]),
                'autonomy_level' => 'supervised',
                'parent_agent_id' => $ceoId,
                'capabilities' => json_encode($spec['capabilities']),
                'instructions' => $spec['instructions'],
                'metadata' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create some sample tasks
        DB::table('agent_tasks')->insert([
            [
                'id' => Str::uuid(),
                'agent_id' => $ceoId,
                'campaign_id' => null,
                'type' => 'strategy_review',
                'status' => 'completed',
                'input' => json_encode(['action' => 'review_q3_strategy', 'campaigns' => 5]),
                'output' => json_encode(['recommendations' => ['Increase budget for brand awareness by 20%', 'Pause underperforming ad sets']]),
                'reasoning' => 'Analyzed Q3 performance data across all campaigns. Identified top performers and areas needing optimization.',
                'started_at' => now()->subHours(4),
                'completed_at' => now()->subHours(3),
                'created_at' => now()->subHours(4),
                'updated_at' => now()->subHours(3),
            ],
            [
                'id' => Str::uuid(),
                'agent_id' => $specialistIds[0],
                'campaign_id' => DB::table('campaigns')->where('status', 'active')->value('id'),
                'type' => 'campaign_optimization',
                'status' => 'in_progress',
                'input' => json_encode(['action' => 'optimize_budget', 'max_budget' => 50000]),
                'output' => null,
                'reasoning' => null,
                'started_at' => now()->subHour(),
                'completed_at' => null,
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
            ],
        ]);

        // Create some sample memories
        DB::table('agent_memories')->insert([
            [
                'agent_id' => $ceoId,
                'type' => 'episodic',
                'key' => 'q3_strategy_meeting',
                'content' => json_encode(['summary' => 'Decided to focus on brand awareness for Q3', 'participants' => ['Campaign Specialist', 'Analytics Director']]),
                'importance' => 8,
                'access_count' => 5,
                'last_accessed_at' => now()->subHours(2),
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subHours(2),
            ],
            [
                'agent_id' => $specialistIds[0],
                'type' => 'procedural',
                'key' => 'budget_optimization_workflow',
                'content' => json_encode(['steps' => ['Analyze current spend per campaign', 'Identify underperformers', 'Reallocate budget to top performers']]),
                'importance' => 6,
                'access_count' => 12,
                'last_accessed_at' => now()->subMinutes(30),
                'created_at' => now()->subDays(14),
                'updated_at' => now()->subMinutes(30),
            ],
        ]);
    }
}
