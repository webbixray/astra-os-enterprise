<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentTest extends TestCase
{
    #[Test]
    public function it_can_create_a_ceo_agent(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $agent = \App\Models\Agent::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $organization->id,
            'name' => 'Astra CEO',
            'role' => 'ceo',
            'model_config' => [
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'temperature' => 0.7,
            ],
            'autonomy_level' => 'full',
            'capabilities' => ['strategy', 'planning', 'approval'],
            'is_active' => true,
        ]);

        $this->assertEquals('ceo', $agent->role);
        $this->assertEquals('full', $agent->autonomy_level);
        $this->assertTrue($agent->is_active);
    }

    #[Test]
    public function it_can_have_a_parent_agent(): void
    {
        $organization = \App\Models\Organization::factory()->create();

        $ceo = \App\Models\Agent::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $organization->id,
            'name' => 'CEO',
            'role' => 'ceo',
            'model_config' => ['provider' => 'openai', 'model' => 'gpt-4o'],
            'autonomy_level' => 'full',
            'is_active' => true,
        ]);

        $specialist = \App\Models\Agent::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $organization->id,
            'name' => 'Specialist',
            'role' => 'specialist',
            'model_config' => ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
            'autonomy_level' => 'supervised',
            'parent_agent_id' => $ceo->id,
            'is_active' => true,
        ]);

        $this->assertEquals($ceo->id, $specialist->parent_agent_id);
    }

    #[Test]
    public function it_can_be_assigned_tasks(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $agent = \App\Models\Agent::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $agent->tasks()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'analysis',
            'status' => 'pending',
            'input' => ['action' => 'analyze_campaign'],
        ]);

        $this->assertCount(1, $agent->tasks);
        $this->assertEquals('pending', $agent->tasks->first()->status);
    }

    #[Test]
    public function it_tracks_task_completion(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $agent = \App\Models\Agent::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $task = $agent->tasks()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'optimization',
            'status' => 'in_progress',
            'input' => ['action' => 'optimize_budget'],
        ]);

        $task->update([
            'status' => 'completed',
            'output' => ['result' => 'budget_optimized'],
            'reasoning' => 'Reallocated budget to top performers',
            'completed_at' => now(),
        ]);

        $completedTask = $task->fresh();
        $this->assertEquals('completed', $completedTask->status);
        $this->assertNotNull($completedTask->completed_at);
        $this->assertNotNull($completedTask->reasoning);
    }

    #[Test]
    public function it_stores_memories(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $agent = \App\Models\Agent::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $agent->memories()->create([
            'type' => 'episodic',
            'key' => 'meeting_notes',
            'content' => ['summary' => 'Q3 planning meeting'],
            'importance' => 8,
        ]);

        $this->assertCount(1, $agent->memories);
        $this->assertEquals(8, $agent->memories->first()->importance);
    }

    #[Test]
    public function it_has_incremented_memory_access_count(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $agent = \App\Models\Agent::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $memory = $agent->memories()->create([
            'type' => 'procedural',
            'key' => 'workflow_steps',
            'content' => ['steps' => ['step1', 'step2']],
            'importance' => 5,
        ]);

        $memory->increment('access_count');
        $memory->update(['last_accessed_at' => now()]);

        $this->assertEquals(1, $memory->fresh()->access_count);
        $this->assertNotNull($memory->fresh()->last_accessed_at);
    }

    #[Test]
    public function it_validates_autonomy_levels_by_role(): void
    {
        $organization = \App\Models\Organization::factory()->create();

        $ceo = \App\Models\Agent::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $organization->id,
            'name' => 'CEO Agent',
            'role' => 'ceo',
            'model_config' => ['provider' => 'openai', 'model' => 'gpt-4o'],
            'autonomy_level' => 'full',
            'is_active' => true,
        ]);

        $this->assertEquals('full', $ceo->autonomy_level);
        // CEO role should allow 'full' autonomy
        $allowedLevels = config('agents.roles.ceo.allowed_autonomy_levels');
        $this->assertContains('full', $allowedLevels);
    }
}
