<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Workflow;

use App\Models\User;
use App\Infrastructure\Persistence\Models\Organization;
use App\Infrastructure\Persistence\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WorkflowApiTest extends TestCase
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
        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/workflows");
        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_create_a_workflow(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/organizations/{$this->organization->id}/workflows", [
                'name' => 'Campaign Approval Flow',
                'description' => 'Multi-step campaign review and approval',
                'nodes' => [
                    ['id' => 'node_1', 'type' => 'trigger', 'config' => ['event' => 'campaign.created']],
                    ['id' => 'node_2', 'type' => 'action', 'config' => ['action' => 'notify']],
                ],
                'edges' => [
                    ['source' => 'node_1', 'target' => 'node_2', 'condition' => null],
                ],
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'name'],
        ]);
    }

    #[Test]
    public function it_can_list_workflows(): void
    {
        Workflow::factory()->count(2)->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/organizations/{$this->organization->id}/workflows");

        $response->assertStatus(200);
    }

    #[Test]
    public function it_can_show_a_workflow(): void
    {
        $workflow = Workflow::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/organizations/{$this->organization->id}/workflows/{$workflow->id}");

        $response->assertStatus(200);
    }
}
