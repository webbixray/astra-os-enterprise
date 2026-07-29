<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Campaign;

use App\Application\Campaign\DTOs\CampaignResponseDTO;
use App\Application\Campaign\DTOs\CreateCampaignDTO;
use App\Application\Campaign\Services\CampaignBudgetService;
use App\Application\Campaign\Services\CampaignValidationService;
use App\Application\Campaign\UseCases\CreateCampaignUseCase;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Events\CampaignCreated;
use App\Domain\Campaign\Repositories\CampaignRepositoryInterface;
use App\Domain\Common\ValueObjects\Money;
use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateCampaignUseCase.
 *
 * Uses mocked repositories and services to test the application-layer
 * campaign creation logic in isolation.
 */
final class CreateCampaignUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Creates a valid campaign via the repository and returns a CampaignResponseDTO.
     */
    public function test_creates_campaign_via_repository_and_returns_response_dto(): void
    {
        $mockCampaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
        $mockValidationService = Mockery::mock(CampaignValidationService::class);
        $mockBudgetService = Mockery::mock(CampaignBudgetService::class);
        $eventMock = Mockery::mock('overload:' . CampaignCreated::class);

        $dto = new CreateCampaignDTO(
            name: 'Q3 Marketing Campaign',
            objective: 'conversions',
            budgetAmount: 25000.00,
            budgetCurrency: 'USD',
            targetAudience: ['age' => ['25-45'], 'locations' => ['US', 'CA']],
            platforms: ['meta_ads', 'google_ads'],
            startDate: new DateTimeImmutable('2026-07-01'),
            endDate: new DateTimeImmutable('2026-09-30'),
            organizationId: 1,
            createdBy: 1,
        );

        $mockValidationService->expects()->validateCampaignData($dto);
        $mockValidationService->expects()->validateDateRange($dto->startDate, $dto->endDate);
        $mockValidationService->expects()->validatePlatforms($dto->platforms);

        $mockBudgetService->expects()->validateBudget(Mockery::type(Money::class), $dto->platforms);

        $mockCampaignRepo
            ->expects()
            ->save(Mockery::type(Campaign::class))
            ->andReturnUsing(function (Campaign $campaign) {
                $campaign->setId(1);
                return $campaign;
            });

        $mockBudgetService->expects()->allocateBudget(1, Mockery::type(Money::class))->andReturn([]);

        $eventMock->expects()->dispatch(Mockery::type(Campaign::class));

        $useCase = new CreateCampaignUseCase(
            $mockCampaignRepo,
            $mockValidationService,
            $mockBudgetService,
        );

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(CampaignResponseDTO::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertSame('Q3 Marketing Campaign', $result->name);
    }

    /**
     * Throws an InvalidArgumentException when the campaign name is empty.
     */
    public function test_throws_when_name_is_empty(): void
    {
        $mockCampaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
        $mockValidationService = Mockery::mock(CampaignValidationService::class);
        $mockBudgetService = Mockery::mock(CampaignBudgetService::class);

        $dto = new CreateCampaignDTO(
            name: '',
            objective: 'conversions',
            budgetAmount: 1000.00,
            budgetCurrency: 'USD',
            targetAudience: [],
            platforms: ['meta_ads'],
            startDate: new DateTimeImmutable('2026-07-01'),
            endDate: new DateTimeImmutable('2026-09-30'),
            organizationId: 1,
        );

        $mockValidationService
            ->expects()
            ->validateCampaignData($dto)
            ->andThrow(new InvalidArgumentException('Campaign name is required and cannot be empty.'));

        $useCase = new CreateCampaignUseCase(
            $mockCampaignRepo,
            $mockValidationService,
            $mockBudgetService,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Campaign name is required and cannot be empty.');

        $useCase->execute($dto);
    }

    /**
     * Throws an InvalidArgumentException when the budget is negative.
     */
    public function test_throws_when_budget_is_negative(): void
    {
        $mockCampaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
        $mockValidationService = Mockery::mock(CampaignValidationService::class);
        $mockBudgetService = Mockery::mock(CampaignBudgetService::class);

        $dto = new CreateCampaignDTO(
            name: 'Campaign with Negative Budget',
            objective: 'conversions',
            budgetAmount: -100.00,
            budgetCurrency: 'USD',
            targetAudience: [],
            platforms: ['meta_ads'],
            startDate: new DateTimeImmutable('2026-07-01'),
            endDate: new DateTimeImmutable('2026-09-30'),
            organizationId: 1,
        );

        $mockValidationService->expects()->validateCampaignData($dto);
        $mockValidationService->expects()->validateDateRange($dto->startDate, $dto->endDate);
        $mockValidationService->expects()->validatePlatforms($dto->platforms);

        $mockBudgetService
            ->expects()
            ->validateBudget(Mockery::type(Money::class), $dto->platforms)
            ->andThrow(new InvalidArgumentException('Budget must be greater than zero.'));

        $useCase = new CreateCampaignUseCase(
            $mockCampaignRepo,
            $mockValidationService,
            $mockBudgetService,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Budget must be greater than zero.');

        $useCase->execute($dto);
    }

    /**
     * Dispatches the CampaignCreated domain event after a successful creation.
     */
    public function test_dispatches_campaign_created_domain_event(): void
    {
        $mockCampaignRepo = Mockery::mock(CampaignRepositoryInterface::class);
        $mockValidationService = Mockery::mock(CampaignValidationService::class);
        $mockBudgetService = Mockery::mock(CampaignBudgetService::class);
        $eventMock = Mockery::mock('overload:' . CampaignCreated::class);

        $dto = new CreateCampaignDTO(
            name: 'Launch Campaign',
            objective: 'brand_awareness',
            budgetAmount: 5000.00,
            budgetCurrency: 'USD',
            targetAudience: ['age' => ['18-35']],
            platforms: ['meta_ads', 'tiktok_ads'],
            startDate: new DateTimeImmutable('2026-08-01'),
            endDate: new DateTimeImmutable('2026-12-31'),
            organizationId: 2,
            createdBy: 5,
        );

        $mockValidationService->expects()->validateCampaignData($dto);
        $mockValidationService->expects()->validateDateRange($dto->startDate, $dto->endDate);
        $mockValidationService->expects()->validatePlatforms($dto->platforms);

        $mockBudgetService->expects()->validateBudget(Mockery::type(Money::class), $dto->platforms);

        $mockCampaignRepo
            ->expects()
            ->save(Mockery::type(Campaign::class))
            ->andReturnUsing(function (Campaign $campaign) {
                $campaign->setId(42);
                return $campaign;
            });

        $mockBudgetService->expects()->allocateBudget(42, Mockery::type(Money::class))->andReturn([]);

        $eventMock->expects()->dispatch(Mockery::type(Campaign::class));

        $useCase = new CreateCampaignUseCase(
            $mockCampaignRepo,
            $mockValidationService,
            $mockBudgetService,
        );

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(CampaignResponseDTO::class, $result);
        $this->assertSame(42, $result->id);
    }
}
