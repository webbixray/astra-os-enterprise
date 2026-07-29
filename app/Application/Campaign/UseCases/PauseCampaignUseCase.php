<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Domain\Campaign\Events\CampaignPaused;
use App\Domain\Campaign\Repositories\CampaignRepositoryInterface;
use RuntimeException;

final readonly class PauseCampaignUseCase
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Pause an active or scheduled campaign.
     *
     * @throws RuntimeException if campaign is not found or cannot be paused.
     */
    public function execute(int $campaignId): void
    {
        $campaign = $this->campaignRepository->findById($campaignId);

        if ($campaign === null) {
            throw new RuntimeException("Campaign with ID {$campaignId} not found.");
        }

        $campaign->pause();
        $this->campaignRepository->save($campaign);

        CampaignPaused::dispatch($campaign);
    }
}
