<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Campaign;

use App\Application\Campaign\Services\CampaignValidationService;
use App\Application\Campaign\UseCases\LaunchCampaignUseCase;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Events\CampaignLaunched;
use App\Domain\Campaign\Repositories\CampaignRepositoryInterface;
use App\Domain\Common\ValueObjects\Money;
use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for LaunchCampaignUseCase.
 *
 * Tests the campaign launch logic with a mocked repository
 * and validation service.
 */
final class LaunchCampaignUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function createDraftCampaign(int $id = 1): Campaign
    {
        $campaign = new Campaign(
            name: 'Test Campaign',
            objective: 'conversions',
            budget: new Money(5000.00, 'USD'),
            targetAudience: ['age' => ['25-45']],
            platforms: ['meta_ads'],
            startDate: new DateTimeImmutable('2026-07-01'),
            endDate: new DateTimeImmutable('2026-09-30'),
            organizationId: 1,
        );
        $campaign->setId($id);
        return $campaign;
    }

    /**
     * Launches a campaign from draft state successfully.
     */
    public function test_launches_campaign_from_draft_state(): void
    {
        $mockCampaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
        $mockValidationService = Mockery::mock(CampaignValidationService::class);
        $eventMock = Mockery::mock('overload:' . CampaignLaunched::class);

        $campaign = $this->createDraftCampaign();

        $mockCampaignRepo
            ->expects()
            ->findById(1)
            ->andReturn($campaign);

        $mockValidationService
            ->expects()
            ->validateCampaignCanLaunch($campaign);

        $mockCampaignRepo
            ->expects()
            ->save(Mockery::type(Campaign::class))
            ->andReturnUsing(function (Campaign $c) {
                return $c;
            });

        $eventMock->expects()->dispatch(Mockery::type(Campaign::class));

        $useCase = new LaunchCampaignUseCase($mockCampaignRepo, $mockValidationService);

        $useCase->execute(1);

        $this->assertContains($campaign->getStatus(), ['active', 'scheduled']);
    }

    /**
     * Throws a RuntimeException when the campaign is not found.
     */
    public function test_throws_when_campaign_not_found(): void
    {
        $mockCampaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
        $mockValidationService = Mockery::mock(CampaignValidationService::class);

        $mockCampaignRepo
            ->expects()
            ->findById(999)
            ->andReturn(null);

        $useCase = new LaunchCampaignUseCase($mockCampaignRepo, $mockValidationService);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Campaign with ID 999 not found.');

        $useCase->execute(999);
    }

    /**
     * Throws a RuntimeException when the campaign is already archived.
     */
    public function test_throws_when_campaign_already_archived(): void
    {
        $mockCampaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
        $mockValidationService = Mockery::mock(CampaignValidationService::class);

        $campaign = $this->createDraftCampaign();
        $campaign->archive();

        $mockCampaignRepo
            ->expects()
            ->findById(1)
            ->andReturn($campaign);

        $mockValidationService
            ->expects()
            ->validateCampaignCanLaunch($campaign)
            ->andThrow(new RuntimeException('Cannot launch campaign in status "archived". Must be "draft" or "paused".'));

        $useCase = new LaunchCampaignUseCase($mockCampaignRepo, $mockValidationService);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot launch campaign in status "archived".');
        
        $useCase->execute(1);
    }

    /**
     * Dispatches the CampaignLaunched domain event after successfully launching.
     */
    public function test_dispatches_campaign_launched_event(): void
    {
        $mockCampaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
        $mockValidationService = Mockery::mock(CampaignValidationService::class);
        $eventMock = Mockery::mock('overload:' . CampaignLaunched::class);

        $campaign = $this->createDraftCampaign(7);

        $mockCampaignRepo
            ->expects()
            ->findById(7)
            ->andReturn($campaign);

        $mockValidationService
            ->expects()
            ->validateCampaignCanLaunch($campaign);

        $mockCampaignRepo
            ->expects()
            ->save(Mockery::type(Campaign::class))
            ->andReturnUsing(function (Campaign $c) {
                return $c;
            });

        $eventMock->expects()->dispatch(Mockery::type(Campaign::class));

        $useCase = new LaunchCampaignUseCase($mockCampaignRepo, $mockValidationService);

        $useCase->execute(7);
    }
}
