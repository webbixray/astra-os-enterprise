<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Repositories;

use App\Domain\Campaign\Entities\CampaignInsight;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface CampaignInsightRepositoryInterface
 *
 * Repository contract for CampaignInsight entity persistence.
 *
 * @package App\Domain\Campaign\Repositories
 */
interface CampaignInsightRepositoryInterface
{
    /**
     * Find an insight by its UUID.
     *
     * @param UuidInterface $id
     * @return CampaignInsight|null
     */
    public function findById(UuidInterface $id): ?CampaignInsight;

    /**
     * Find insights for a campaign within a date range.
     *
     * @param UuidInterface      $campaignId
     * @param DateTimeImmutable  $startDate
     * @param DateTimeImmutable  $endDate
     * @return array<int, CampaignInsight>
     */
    public function findByCampaignAndDateRange(
        UuidInterface $campaignId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate
    ): array;

    /**
     * Find insights for a specific metric on a campaign.
     *
     * @param UuidInterface $campaignId
     * @param string        $metric
     * @return array<int, CampaignInsight>
     */
    public function findByCampaignAndMetric(
        UuidInterface $campaignId,
        string $metric
    ): array;

    /**
     * Find all insights for a campaign.
     *
     * @param UuidInterface $campaignId
     * @return array<int, CampaignInsight>
     */
    public function findByCampaignId(UuidInterface $campaignId): array;

    /**
     * Persist an insight.
     *
     * @param CampaignInsight $insight
     * @return CampaignInsight
     */
    public function save(CampaignInsight $insight): CampaignInsight;

    /**
     * Save multiple insights at once.
     *
     * @param array<int, CampaignInsight> $insights
     * @return void
     */
    public function saveMany(array $insights): void;

    /**
     * Delete an insight.
     *
     * @param CampaignInsight $insight
     * @return void
     */
    public function delete(CampaignInsight $insight): void;
}
