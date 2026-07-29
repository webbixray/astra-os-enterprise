<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Agent;

use App\Models\User;
use App\Infrastructure\Persistence\Models\Organization;
use App\Infrastructure\Persistence\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $organization;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create([
            'owner_id' => $this->user->id,
        ]);
        $this->organization->members()->create([
            'user_id' => $this->user->id,
            'role' => 'admin',
            'permissions' => ['*'],
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/agents");
        $response->assertStatus(401);
    }

    #[Test]
    public function it_lists_agents_in_organization(): void
    {
        Agent::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/organizations/{$this->organization->id}/agents");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [],
        ]);
    }

    #[Test]
    public function it_can_create_an_agent(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/organizations/{$this->organization->id}/agents", [
                'name' => 'Marketing Analyst',
                'role' => 'specialist',
                'autonomy_level' => 'supervised',
                'capabilities' => ['analytics', 'reporting'],
                'instructions' => 'Analyze campaign performance data',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'role'],
        ]);
    }

    #[Test]
    public function it_validates_agent_role(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/organizations/{$this->organization->id}/agents", [
                'name' => 'Bad Agent',
                'role' => 'invalid_role',
                'autonomy_level' => 'supervised',
                'capabilities' => [],
            ]);

        $response->assertStatus(422);
    }
}
