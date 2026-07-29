<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Repositories;

use App\Domain\Analytics\Entities\CampaignAnalytics;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface AnalyticsRepositoryInterface
 *
 * Repository contract for CampaignAnalytics entity persistence.
 *
 * @package App\Domain\Analytics\Repositories
 */
interface AnalyticsRepositoryInterface
{
    /**
     * Find analytics by its UUID.
     *
     * @param UuidInterface $id
     * @return CampaignAnalytics|null
     */
    public function findById(UuidInterface $id): ?CampaignAnalytics;

    /**
     * Find analytics for a campaign within a date range.
     *
     * @param UuidInterface     $campaignId
     * @param DateTimeImmutable $startDate
     * @param DateTimeImmutable $endDate
     * @return array<int, CampaignAnalytics>
     */
    public function findByCampaignAndDateRange(
        UuidInterface $campaignId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array;

    /**
     * Find analytics for a campaign by date.
     *
     * @param UuidInterface     $campaignId
     * @param DateTimeImmutable $date
     * @return CampaignAnalytics|null
     */
    public function findByCampaignAndDate(
        UuidInterface $campaignId,
        DateTimeImmutable $date
    ): ?CampaignAnalytics;

    /**
     * Get aggregate analytics for a campaign.
     *
     * @param UuidInterface $campaignId
     * @return array<string, float|int> Aggregated metrics.
     */
    public function getAggregateByCampaign(UuidInterface $campaignId): array;

    /**
     * Persist analytics data.
     *
     * @param CampaignAnalytics $analytics
     * @return CampaignAnalytics
     */
    public function save(CampaignAnalytics $analytics): CampaignAnalytics;

    /**
     * Save multiple analytics records at once.
     *
     * @param array<int, CampaignAnalytics> $analyticsRecords
     * @return void
     */
    public function saveMany(array $analyticsRecords): void;

    /**
     * Delete analytics data.
     *
     * @param CampaignAnalytics $analytics
     * @return void
     */
    public function delete(CampaignAnalytics $analytics): void;
}
