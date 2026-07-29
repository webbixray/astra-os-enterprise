<?php

declare(strict_types=1);

namespace App\Application\Campaign\UseCases;

use App\Application\Campaign\DTOs\CampaignResponseDTO;
use App\Domain\Campaign\Repositories\CampaignRepositoryInterface;
use RuntimeException;

final readonly class GetCampaignAnalyticsUseCase
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Get campaign with its analytics data.
     *
     * @return array{ campaign: CampaignResponseDTO, analytics: array }
     *
     * @throws RuntimeException if campaign is not found.
     */
    public function execute(int $campaignId): array
    {
        $campaign = $this->campaignRepository->findById($campaignId);

        if ($campaign === null) {
            throw new RuntimeException("Campaign with ID {$campaignId} not found.");
        }

        return [
            'campaign' => CampaignResponseDTO::fromArray($campaign->toArray()),
            'analytics' => [], // Would aggregate analytics via AnalyticsAggregationService
        ];
    }
}
