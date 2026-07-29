<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\Services\CampaignValidationService;
use App\Domain\Campaign\Events\CampaignLaunched;
use App\Domain\Campaign\Repositories\CampaignRepositoryInterface;
use RuntimeException;

final readonly class LaunchCampaignUseCase
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private CampaignValidationService $validationService,
    ) {}

    /**
     * Launch a campaign.
     *
     * Validates preconditions (budget approval, date range, creative assets),
     * changes campaign status to active/scheduled, and dispatches the event.
     *
     * @throws RuntimeException if campaign cannot be launched.
     */
    public function execute(int $campaignId): void
    {
        $campaign = $this->campaignRepository->findById($campaignId);

        if ($campaign === null) {
            throw new RuntimeException("Campaign with ID {$campaignId} not found.");
        }

        $this->validationService->validateCampaignCanLaunch($campaign);

        $campaign->launch();
        $this->campaignRepository->save($campaign);

        CampaignLaunched::dispatch($campaign);
    }
}
