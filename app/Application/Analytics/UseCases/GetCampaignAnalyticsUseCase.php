<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\DTOs\AnalyticsQueryDTO;
use App\Application\Analytics\Services\AnalyticsAggregationService;
use App\Domain\Campaign\Repositories\CampaignRepositoryInterface;
use RuntimeException;

final readonly class GetCampaignAnalyticsUseCase
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private AnalyticsAggregationService $aggregationService,
    ) {}

    /**
     * Get aggregated analytics for a campaign.
     *
     * @return array{ campaign: array, analytics: array, summary: array }
     *
     * @throws RuntimeException if campaign is not found.
     */
    public function execute(AnalyticsQueryDTO $dto): array
    {
        $campaign = $this->campaignRepository->findById($dto->campaignId);

        if ($campaign === null) {
            throw new RuntimeException("Campaign with ID {$dto->campaignId} not found.");
        }

        $analytics = $this->aggregationService->aggregate(
            campaignId: $dto->campaignId,
            startDate: $dto->startDate,
            endDate: $dto->endDate,
            granularity: $dto->granularity,
            platform: $dto->platform,
        );

        $summary = $this->aggregationService->summarize($analytics);

        return [
            'campaign' => $campaign->toArray(),
            'analytics' => $analytics,
            'summary' => $summary,
        ];
    }
}
