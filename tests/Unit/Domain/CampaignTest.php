<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    #[Test]
    public function it_can_be_created_as_draft(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $campaign = \App\Models\Campaign::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $organization->id,
            'name' => 'Test Campaign',
            'objective' => 'conversions',
            'status' => 'draft',
            'budget_amount' => 1000.00,
            'budget_currency' => 'USD',
        ]);

        $this->assertEquals('draft', $campaign->status);
        $this->assertEquals('Test Campaign', $campaign->name);
    }

    #[Test]
    public function it_can_transition_through_lifecycle(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $campaign = \App\Models\Campaign::factory()->create([
            'organization_id' => $organization->id,
            'status' => 'draft',
        ]);

        // Draft → Active
        $campaign->update(['status' => 'active']);
        $this->assertEquals('active', $campaign->fresh()->status);

        // Active → Paused
        $campaign->update(['status' => 'paused']);
        $this->assertEquals('paused', $campaign->fresh()->status);

        // Paused → Active (resume)
        $campaign->update(['status' => 'active']);
        $this->assertEquals('active', $campaign->fresh()->status);

        // Active → Completed
        $campaign->update(['status' => 'completed']);
        $this->assertEquals('completed', $campaign->fresh()->status);
    }

    #[Test]
    public function it_stores_budget_correctly(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $campaign = \App\Models\Campaign::factory()->create([
            'organization_id' => $organization->id,
            'budget_amount' => 50000.00,
            'budget_currency' => 'USD',
        ]);

        $this->assertEquals(50000.00, $campaign->budget_amount);
        $this->assertEquals('USD', $campaign->budget_currency);
    }

    #[Test]
    public function it_can_have_creatives(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $campaign = \App\Models\Campaign::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $campaign->creatives()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'image',
            'content' => ['headline' => 'Test'],
            'variant' => 'a',
            'status' => 'draft',
        ]);

        $this->assertCount(1, $campaign->creatives);
    }

    #[Test]
    public function it_has_target_audience_as_json(): void
    {
        $audience = ['age' => ['18-35'], 'locations' => ['US']];
        $organization = \App\Models\Organization::factory()->create();
        $campaign = \App\Models\Campaign::factory()->create([
            'organization_id' => $organization->id,
            'target_audience' => $audience,
        ]);

        $this->assertEquals($audience, $campaign->target_audience);
    }

    #[Test]
    public function it_can_be_soft_deleted(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $campaign = \App\Models\Campaign::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $campaign->delete();

        $this->assertSoftDeleted($campaign);
    }

    #[Test]
    public function it_scopes_to_organization(): void
    {
        $org1 = \App\Models\Organization::factory()->create();
        $org2 = \App\Models\Organization::factory()->create();

        \App\Models\Campaign::factory()->count(3)->create(['organization_id' => $org1->id]);
        \App\Models\Campaign::factory()->count(2)->create(['organization_id' => $org2->id]);

        $this->assertCount(3, $org1->campaigns);
        $this->assertCount(2, $org2->campaigns);
    }
}
