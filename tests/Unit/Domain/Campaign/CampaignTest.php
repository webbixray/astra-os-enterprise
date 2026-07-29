<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Campaign;

use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Common\ValueObjects\Money;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for the Campaign domain entity.
 *
 * Covers the full campaign lifecycle: creation (draft), launch,
 * pause, archive, complete transitions. Tests state-machine validation
 * (e.g., cannot launch from archived, cannot pause from draft),
 * Money value object integration for budget, and serialization round-trip.
 *
 * @package Tests\Unit\Domain\Campaign
 */
final class CampaignTest extends TestCase
{
    // ---- Helpers ----

    /**
     * Create a default campaign in draft state with a future start date.
     *
     * @param array $overrides
     * @return Campaign
     */
    private function createDraftCampaign(array $overrides = []): Campaign
    {
        $future = new DateTimeImmutable('+30 days');
        $end = $future->modify('+60 days');

        return new Campaign(
            name: $overrides['name'] ?? 'Test Campaign',
            objective: $overrides['objective'] ?? 'brand_awareness',
            budget: $overrides['budget'] ?? new Money(10000.0, 'USD'),
            targetAudience: $overrides['targetAudience'] ?? ['age' => ['18-65']],
            platforms: $overrides['platforms'] ?? ['facebook', 'instagram'],
            startDate: $overrides['startDate'] ?? $future,
            endDate: $overrides['endDate'] ?? $end,
            organizationId: $overrides['organizationId'] ?? 1,
            createdBy: $overrides['createdBy'] ?? null,
            metadata: $overrides['metadata'] ?? [],
        );
    }

    // ---- Happy Path: Creation ----

    #[Test]
    public function it_creates_campaign_as_draft(): void
    {
        $campaign = $this->createDraftCampaign();

        $this->assertSame('draft', $campaign->getStatus());
        $this->assertNull($campaign->getLaunchedAt());
        $this->assertNull($campaign->getPausedAt());
        $this->assertNull($campaign->getArchivedAt());
    }

    #[Test]
    public function it_stores_constructor_values(): void
    {
        $budget = new Money(5000.0, 'USD');
        $start = new DateTimeImmutable('+10 days');
        $end = $start->modify('+20 days');

        $campaign = new Campaign(
            name: 'Q3 Launch',
            objective: 'conversions',
            budget: $budget,
            targetAudience: ['gender' => 'all'],
            platforms: ['google', 'meta'],
            startDate: $start,
            endDate: $end,
            organizationId: 5,
            createdBy: 42,
            metadata: ['priority' => 'high'],
        );

        $this->assertSame('Q3 Launch', $campaign->getName());
        $this->assertSame('conversions', $campaign->getObjective());
        $this->assertSame($budget, $campaign->getBudget());
        $this->assertSame(['gender' => 'all'], $campaign->getTargetAudience());
        $this->assertSame(['google', 'meta'], $campaign->getPlatforms());
        $this->assertSame($start, $campaign->getStartDate());
        $this->assertSame($end, $campaign->getEndDate());
        $this->assertSame(5, $campaign->getOrganizationId());
        $this->assertSame(42, $campaign->getCreatedBy());
        $this->assertSame(['priority' => 'high'], $campaign->getMetadata());
    }

    #[Test]
    public function it_has_timestamps(): void
    {
        $campaign = $this->createDraftCampaign();

        $this->assertNotNull($campaign->getCreatedAt());
        $this->assertNotNull($campaign->getUpdatedAt());
    }

    #[Test]
    public function it_generates_null_id_on_creation(): void
    {
        $campaign = $this->createDraftCampaign();

        $this->assertNull($campaign->getId());
    }

    #[Test]
    public function it_sets_id(): void
    {
        $campaign = $this->createDraftCampaign();
        $campaign->setId(7);

        $this->assertSame(7, $campaign->getId());
    }

    #[Test]
    public function it_accepts_null_created_by(): void
    {
        $campaign = $this->createDraftCampaign(['createdBy' => null]);

        $this->assertNull($campaign->getCreatedBy());
    }

    // ---- Lifecycle: Launch ----

    #[Test]
    public function it_launches_from_draft_with_future_date_as_scheduled(): void
    {
        $campaign = $this->createDraftCampaign(['startDate' => new DateTimeImmutable('+30 days')]);
        $campaign->launch();

        $this->assertSame('scheduled', $campaign->getStatus());
        $this->assertNotNull($campaign->getLaunchedAt());
    }

    #[Test]
    public function it_launches_from_draft_with_past_date_as_active(): void
    {
        $campaign = $this->createDraftCampaign(['startDate' => new DateTimeImmutable('-10 days')]);
        $campaign->launch();

        $this->assertSame('active', $campaign->getStatus());
        $this->assertNotNull($campaign->getLaunchedAt());
    }

    // ---- Lifecycle: Pause ----

    #[Test]
    public function it_pauses_active_campaign(): void
    {
        $campaign = $this->createDraftCampaign(['startDate' => new DateTimeImmutable('-10 days')]);
        $campaign->launch(); // becomes 'active'

        $campaign->pause();

        $this->assertSame('paused', $campaign->getStatus());
        $this->assertNotNull($campaign->getPausedAt());
    }

    #[Test]
    public function it_pauses_scheduled_campaign(): void
    {
        $campaign = $this->createDraftCampaign(['startDate' => new DateTimeImmutable('+30 days')]);
        $campaign->launch(); // becomes 'scheduled'

        $campaign->pause();

        $this->assertSame('paused', $campaign->getStatus());
    }

    // ---- Lifecycle: Resume (pause → launch) ----

    #[Test]
    public function it_resumes_from_paused_to_active(): void
    {
        $campaign = $this->createDraftCampaign(['startDate' => new DateTimeImmutable('-10 days')]);
        $campaign->launch();   // active
        $campaign->pause();     // paused

        $campaign->launch();   // resume → active

        $this->assertSame('active', $campaign->getStatus());
    }

    // ---- Lifecycle: Archive ----

    #[Test]
    public function it_archives_from_draft(): void
    {
        $campaign = $this->createDraftCampaign();
        $campaign->archive();

        $this->assertSame('archived', $campaign->getStatus());
        $this->assertNotNull($campaign->getArchivedAt());
    }

    #[Test]
    public function it_archives_from_active_and_sets_paused_at(): void
    {
        $campaign = $this->createDraftCampaign(['startDate' => new DateTimeImmutable('-10 days')]);
        $campaign->launch(); // active

        $campaign->archive();

        $this->assertSame('archived', $campaign->getStatus());
        // archive() pauses first, so pausedAt should be set
        $this->assertNotNull($campaign->getPausedAt());
        $this->assertNotNull($campaign->getArchivedAt());
    }

    #[Test]
    public function it_archives_from_paused(): void
    {
        $campaign = $this->createDraftCampaign(['startDate' => new DateTimeImmutable('-10 days')]);
        $campaign->launch();
        $campaign->pause();

        $campaign->archive();

        $this->assertSame('archived', $campaign->getStatus());
    }

    #[Test]
    public function it_archives_from_scheduled(): void
    {
        $campaign = $this->createDraftCampaign(['startDate' => new DateTimeImmutable('+30 days')]);
        $campaign->launch(); // scheduled

        $campaign->archive();

        $this->assertSame('archived', $campaign->getStatus());
    }

    // ---- Invalid State Transitions ----

    #[Test]
    public function it_throws_when_launching_archived(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only draft or paused campaigns can be launched.');

        $campaign = $this->createDraftCampaign();
        $campaign->archive();
        $campaign->launch();
    }

    #[Test]
    public function it_throws_when_pausing_draft(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Only active or scheduled campaigns can be paused.');

        $campaign = $this->createDraftCampaign();
        $campaign->pause();
    }

    #[Test]
    public function it_throws_when_pausing_archived(): void
    {
        $this->expectException(RuntimeException::class);

        $campaign = $this->createDraftCampaign();
        $campaign->archive();

        $this->assertSame('archived', $campaign->getStatus());
        $campaign->pause();
    }

    #[Test]
    public function it_throws_when_archiving_already_archived(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Campaign is already archived.');

        $campaign = $this->createDraftCampaign();
        $campaign->archive();
        $campaign->archive(); // second archive should throw
    }

    // ---- Budget ----

    #[Test]
    public function it_updates_budget(): void
    {
        $campaign = $this->createDraftCampaign();
        $newBudget = new Money(25000.0, 'USD');

        $campaign->updateBudget($newBudget);

        $this->assertSame($newBudget, $campaign->getBudget());
    }

    #[Test]
    public function it_updates_timestamp_on_budget_change(): void
    {
        $campaign = $this->createDraftCampaign();
        $originalUpdatedAt = $campaign->getUpdatedAt();

        usleep(2000);
        $campaign->updateBudget(new Money(500.0, 'USD'));

        $this->assertGreaterThan($originalUpdatedAt, $campaign->getUpdatedAt());
    }

    // ---- Serialization ----

    #[Test]
    public function it_serializes_to_array(): void
    {
        $budget = new Money(10000.0, 'USD');
        $campaign = new Campaign(
            name: 'Test',
            objective: 'conversions',
            budget: $budget,
            targetAudience: ['age' => ['25-40']],
            platforms: ['facebook'],
            startDate: new DateTimeImmutable('+10 days'),
            endDate: new DateTimeImmutable('+40 days'),
            organizationId: 3,
            createdBy: 1,
            metadata: ['campaign_type' => 'standard'],
        );

        $array = $campaign->toArray();

        $this->assertSame('Test', $array['name']);
        $this->assertSame('conversions', $array['objective']);
        $this->assertSame(['amount' => 10000.0, 'currency' => 'USD'], $array['budget']);
        $this->assertSame('draft', $array['status']);
        $this->assertSame(3, $array['organization_id']);
        $this->assertSame(1, $array['created_by']);
        $this->assertNull($array['id']);
        $this->assertNull($array['launched_at']);
        $this->assertNull($array['paused_at']);
        $this->assertNull($array['archived_at']);
    }

    #[Test]
    public function it_serializes_with_timestamps_as_strings(): void
    {
        $campaign = $this->createDraftCampaign();
        $array = $campaign->toArray();

        $this->assertIsString($array['created_at']);
        $this->assertIsString($array['updated_at']);
    }

    // ---- Edge Cases ----

    #[Test]
    public function it_launch_sets_launched_at(): void
    {
        $campaign = $this->createDraftCampaign(['startDate' => new DateTimeImmutable('-5 days')]);
        $beforeLaunch = new DateTimeImmutable();

        usleep(2000);
        $campaign->launch();

        $this->assertGreaterThan($beforeLaunch, $campaign->getLaunchedAt());
    }

    #[Test]
    public function it_pause_sets_paused_at(): void
    {
        $campaign = $this->createDraftCampaign(['startDate' => new DateTimeImmutable('-5 days')]);
        $campaign->launch();

        $beforePause = new DateTimeImmutable();
        usleep(2000);
        $campaign->pause();

        $this->assertGreaterThan($beforePause, $campaign->getPausedAt());
    }

    #[Test]
    public function it_archive_sets_archived_at(): void
    {
        $campaign = $this->createDraftCampaign();
        $beforeArchive = new DateTimeImmutable();

        usleep(2000);
        $campaign->archive();

        $this->assertGreaterThan($beforeArchive, $campaign->getArchivedAt());
    }
}
