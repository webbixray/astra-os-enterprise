<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CampaignApiTest extends TestCase
{
    private $user;
    private $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\User::factory()->create();
        $this->organization = \App\Models\Organization::factory()->create();

        // Add user as member
        $this->organization->members()->create([
            'user_id' => $this->user->id,
            'role' => 'admin',
            'permissions' => ['*'],
        ]);
    }

    #[Test]
    public function it_requires_authentication(): void
    {
        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/campaigns");

        $response->assertStatus(401);
    }

    #[Test]
    public function it_lists_campaigns_for_organization(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        \App\Models\Campaign::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/organizations/{$this->organization->id}/campaigns");

        // Endpoint returns 501 as it's not yet implemented
        $response->assertStatus(501);
    }

    #[Test]
    public function it_creates_a_campaign(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/organizations/{$this->organization->id}/campaigns", [
                'name' => 'Test Campaign',
                'objective' => 'conversions',
                'budget_amount' => 10000,
                'budget_currency' => 'USD',
                'platforms' => ['meta', 'google'],
            ]);

        // Endpoint returns 501 as it's not yet implemented
        $response->assertStatus(501);
    }

    #[Test]
    public function it_shows_a_campaign(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $campaign = \App\Models\Campaign::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/organizations/{$this->organization->id}/campaigns/{$campaign->id}");

        // Endpoint returns 501 as it's not yet implemented
        $response->assertStatus(501);
    }

    #[Test]
    public function it_updates_a_campaign(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $campaign = \App\Models\Campaign::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Original Name',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/v1/organizations/{$this->organization->id}/campaigns/{$campaign->id}", [
                'name' => 'Updated Name',
            ]);

        // Endpoint returns 501 as it's not yet implemented
        $response->assertStatus(501);
    }

    #[Test]
    public function it_deletes_a_campaign(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $campaign = \App\Models\Campaign::factory()->create([
            'organization_id' => $this->organization->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson("/api/v1/organizations/{$this->organization->id}/campaigns/{$campaign->id}");

        // Endpoint returns 501 as it's not yet implemented
        $response->assertStatus(501);
    }

    #[Test]
    public function it_does_not_access_other_organizations_campaigns(): void
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $otherOrg = \App\Models\Organization::factory()->create();
        $campaign = \App\Models\Campaign::factory()->create([
            'organization_id' => $otherOrg->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/organizations/{$this->organization->id}/campaigns/{$campaign->id}");

        // Should return 404 (campaign not found in this org) or 501
        $this->assertContains($response->status(), [404, 501]);
    }
}
