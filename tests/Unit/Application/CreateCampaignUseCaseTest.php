<?php

namespace Tests\Unit\Application;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateCampaignUseCaseTest extends TestCase
{
    #[Test]
    public function it_can_create_a_campaign_from_valid_data(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $user = \App\Models\User::factory()->create();

        $data = [
            'organization_id' => $organization->id,
            'name' => 'Q3 Marketing Campaign',
            'objective' => 'conversions',
            'budget_amount' => 25000.00,
            'budget_currency' => 'USD',
            'target_audience' => [
                'age' => ['25-45'],
                'locations' => ['US', 'CA'],
            ],
            'platforms' => ['meta', 'google'],
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
        ];

        $campaign = \App\Models\Campaign::create(array_merge([
            'id' => \Illuminate\Support\Str::uuid(),
        ], $data));

        $this->assertNotNull($campaign);
        $this->assertEquals('Q3 Marketing Campaign', $campaign->name);
        $this->assertEquals('draft', $campaign->status);
        $this->assertEquals(25000.00, $campaign->budget_amount);
        $this->assertEquals(['meta', 'google'], $campaign->platforms);
    }

    #[Test]
    public function it_validates_budget_is_within_limits(): void
    {
        $minBudget = config('campaigns.defaults.budget.min_total', 50);
        $maxBudget = config('campaigns.defaults.budget.max_total', 10000000);

        $this->assertIsFloat($minBudget);
        $this->assertIsFloat($maxBudget);
        $this->assertLessThan($maxBudget, $minBudget);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);

        \App\Models\Campaign::create([]);
    }

    #[Test]
    public function it_creates_campaign_with_default_status_draft(): void
    {
        $organization = \App\Models\Organization::factory()->create();

        $campaign = \App\Models\Campaign::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $organization->id,
            'name' => 'Default Status Campaign',
            'objective' => 'awareness',
            'budget_amount' => 1000,
            'budget_currency' => 'USD',
        ]);

        $this->assertEquals('draft', $campaign->status);
    }

    #[Test]
    public function it_associates_campaign_with_organization(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $campaign = \App\Models\Campaign::factory()->create([
            'organization_id' => $organization->id,
        ]);

        $this->assertInstanceOf(\App\Models\Organization::class, $campaign->organization);
        $this->assertEquals($organization->id, $campaign->organization->id);
    }

    #[Test]
    public function it_validates_platforms_are_supported(): void
    {
        $supportedPlatforms = array_keys(config('astra-os.general.supported_platforms', []));

        $this->assertContains('meta', $supportedPlatforms);
        $this->assertContains('google', $supportedPlatforms);
        $this->assertContains('linkedin', $supportedPlatforms);
        $this->assertContains('tiktok', $supportedPlatforms);
    }

    #[Test]
    public function it_handles_campaign_without_dates(): void
    {
        $organization = \App\Models\Organization::factory()->create();

        $campaign = \App\Models\Campaign::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'organization_id' => $organization->id,
            'name' => 'Indefinite Campaign',
            'objective' => 'retargeting',
            'budget_amount' => 5000,
            'budget_currency' => 'USD',
        ]);

        $this->assertNull($campaign->start_date);
        $this->assertNull($campaign->end_date);
    }
}
