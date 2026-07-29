<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\CreateCampaignDTO;
use App\Application\Campaign\DTOs\CampaignResponseDTO;
use App\Application\Campaign\Services\CampaignValidationService;
use App\Application\Campaign\Services\CampaignBudgetService;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Campaign\Events\CampaignCreated;
use App\Domain\Campaign\Repositories\CampaignRepositoryInterface;
use App\Domain\Common\ValueObjects\Money;
use DateTimeImmutable;

final readonly class CreateCampaignUseCase
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private CampaignValidationService $validationService,
        private CampaignBudgetService $budgetService,
    ) {}

    /**
     * Create a new campaign.
     *
     * Validates the campaign data, allocates budget, persists the campaign,
     * and dispatches the CampaignCreated event.
     */
    public function execute(CreateCampaignDTO $dto): CampaignResponseDTO
    {
        $this->validationService->validateCampaignData($dto);
        $this->validationService->validateDateRange($dto->startDate, $dto->endDate);
        $this->validationService->validatePlatforms($dto->platforms);

        $budget = new Money($dto->budgetAmount, $dto->budgetCurrency);
        $this->budgetService->validateBudget($budget, $dto->platforms);

        $campaign = new Campaign(
            name: $dto->name,
            objective: $dto->objective,
            budget: $budget,
            targetAudience: $dto->targetAudience,
            platforms: $dto->platforms,
            startDate: $dto->startDate,
            endDate: $dto->endDate,
            organizationId: $dto->organizationId,
            createdBy: $dto->createdBy,
            metadata: $dto->metadata,
        );

        $campaign = $this->campaignRepository->save($campaign);
        $this->budgetService->allocateBudget($campaign->getId(), $budget);

        CampaignCreated::dispatch($campaign);

        return CampaignResponseDTO::fromArray($campaign->toArray());
    }
}
