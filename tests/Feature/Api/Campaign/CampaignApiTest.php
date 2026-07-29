<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Campaign;

use App\Models\User;
use App\Infrastructure\Persistence\Models\Organization;
use App\Infrastructure\Persistence\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CampaignApiTest extends TestCase
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
        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/campaigns");

        $response->assertStatus(401);
    }

    #[Test]
    public function it_lists_campaigns_for_an_organization(): void
    {
        Campaign::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/organizations/{$this->organization->id}/campaigns");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    #[Test]
    public function it_can_create_a_campaign(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/organizations/{$this->organization->id}/campaigns", [
                'name' => 'Test Campaign',
                'objective' => 'conversions',
                'budget_amount' => 1000.00,
                'budget_currency' => 'USD',
                'start_date' => now()->addDay()->format('Y-m-d'),
                'end_date' => now()->addDays(30)->format('Y-m-d'),
                'platforms' => ['meta', 'google'],
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'status'],
        ]);
        $this->assertEquals('draft', $response->json('data.status'));
    }

    #[Test]
    public function it_validates_campaign_creation_data(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v1/organizations/{$this->organization->id}/campaigns", [
                'name' => '',
                'budget_amount' => -100,
                'platforms' => 'not-an-array',
            ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function it_can_show_a_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/organizations/{$this->organization->id}/campaigns/{$campaign->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => ['id' => $campaign->id],
        ]);
    }

    #[Test]
    public function it_can_update_a_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Original Name',
            'status' => 'draft',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/organizations/{$this->organization->id}/campaigns/{$campaign->id}", [
                'name' => 'Updated Campaign',
                'objective' => 'brand_awareness',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Updated Campaign', $response->json('data.name'));
    }

    #[Test]
    public function it_can_delete_a_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/organizations/{$this->organization->id}/campaigns/{$campaign->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted($campaign);
    }

    #[Test]
    public function it_respects_organization_isolation(): void
    {
        $otherOrg = Organization::factory()->create();
        $campaign = Campaign::factory()->create([
            'organization_id' => $otherOrg->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/organizations/{$this->organization->id}/campaigns/{$campaign->id}");

        $response->assertStatus(403);
    }
}
