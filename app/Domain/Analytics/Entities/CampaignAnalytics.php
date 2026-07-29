<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: CampaignAnalytics
 *
 * Represents aggregated analytics data for a campaign on a specific date.
 * Captures key performance indicators such as impressions, clicks,
 * conversions, spend, revenue, and derived metrics like ROAS, CPC, CPM, CTR.
 *
 * @package App\Domain\Analytics\Entities
 */
class CampaignAnalytics
{
    use HasTimestamps;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $campaignId;

    /**
     * @var DateTimeImmutable
     */
    private readonly DateTimeImmutable $date;

    /**
     * @var int
     */
    private int $impressions;

    /**
     * @var int
     */
    private int $clicks;

    /**
     * @var int
     */
    private int $conversions;

    /**
     * @var float
     */
    private float $spend;

    /**
     * @var float
     */
    private float $revenue;

    /**
     * @var float
     */
    private float $roas;

    /**
     * @var float
     */
    private float $cpc;

    /**
     * @var float
     */
    private float $cpm;

    /**
     * @var float
     */
    private float $ctr;

    /**
     * @var string
     */
    private readonly string $source;

    /**
     * @var array<string, mixed>
     */
    private array $metadata;

    /**
     * @param UuidInterface          $id
     * @param UuidInterface          $campaignId
     * @param DateTimeImmutable     $date
     * @param int                    $impressions
     * @param int                    $clicks
     * @param int                    $conversions
     * @param float                  $spend
     * @param float                  $revenue
     * @param float                  $roas
     * @param float                  $cpc
     * @param float                  $cpm
     * @param float                  $ctr
     * @param string                 $source
     * @param array<string, mixed>  $metadata
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $campaignId,
        DateTimeImmutable $date,
        int $impressions,
        int $clicks,
        int $conversions,
        float $spend,
        float $revenue,
        float $roas,
        float $cpc,
        float $cpm,
        float $ctr,
        string $source,
        array $metadata
    ) {
        $this->id = $id;
        $this->campaignId = $campaignId;
        $this->date = $date;
        $this->impressions = $impressions;
        $this->clicks = $clicks;
        $this->conversions = $conversions;
        $this->spend = $spend;
        $this->revenue = $revenue;
        $this->roas = $roas;
        $this->cpc = $cpc;
        $this->cpm = $cpm;
        $this->ctr = $ctr;
        $this->source = $source;
        $this->metadata = $metadata;
    }

    /**
     * Create a new CampaignAnalytics record.
     *
     * @param UuidInterface          $campaignId
     * @param DateTimeImmutable     $date
     * @param int                    $impressions
     * @param int                    $clicks
     * @param int                    $conversions
     * @param float                  $spend
     * @param float                  $revenue
     * @param string                 $source
     * @param array<string, mixed>  $metadata
     * @return self
     */
    public static function create(
        UuidInterface $campaignId,
        DateTimeImmutable $date,
        int $impressions = 0,
        int $clicks = 0,
        int $conversions = 0,
        float $spend = 0.0,
        float $revenue = 0.0,
        string $source = 'aggregated',
        array $metadata = []
    ): self {
        $analytics = new self(
            Uuid::uuid4(),
            $campaignId,
            $date,
            $impressions,
            $clicks,
            $conversions,
            $spend,
            $revenue,
            $impressions > 0 ? ($revenue / $spend) : 0.0,
            $clicks > 0 ? ($spend / $clicks) : 0.0,
            $impressions > 0 ? ($spend / $impressions * 1000) : 0.0,
            $impressions > 0 ? ($clicks / $impressions * 100) : 0.0,
            $source,
            $metadata
        );

        $analytics->initializeTimestamps();

        return $analytics;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface          $id
     * @param UuidInterface          $campaignId
     * @param DateTimeImmutable     $date
     * @param int                    $impressions
     * @param int                    $clicks
     * @param int                    $conversions
     * @param float                  $spend
     * @param float                  $revenue
     * @param float                  $roas
     * @param float                  $cpc
     * @param float                  $cpm
     * @param float                  $ctr
     * @param string                 $source
     * @param array<string, mixed>  $metadata
     * @param DateTimeImmutable     $createdAt
     * @param DateTimeImmutable     $updatedAt
     * @return self
     */
    public static function reconstitute(...$args): self
    {
        $analytics = new self(...array_slice($args, 0, 14));
        $analytics->setCreatedAt($args[14]);
        $analytics->setUpdatedAt($args[15]);

        return $analytics;
    }

    /**
     * Update the core metrics and recalculate derived values.
     *
     * @param int   $impressions
     * @param int   $clicks
     * @param int   $conversions
     * @param float $spend
     * @param float $revenue
     * @return void
     */
    public function updateMetrics(
        int $impressions,
        int $clicks,
        int $conversions,
        float $spend,
        float $revenue
    ): void {
        $this->impressions = $impressions;
        $this->clicks = $clicks;
        $this->conversions = $conversions;
        $this->spend = $spend;
        $this->revenue = $revenue;

        // Recalculate derived metrics
        $this->roas = $spend > 0 ? $revenue / $spend : 0.0;
        $this->cpc = $clicks > 0 ? $spend / $clicks : 0.0;
        $this->cpm = $impressions > 0 ? ($spend / $impressions) * 1000 : 0.0;
        $this->ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0.0;

        $this->markAsUpdated();
    }

    // ---- Getters ----

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return UuidInterface
     */
    public function getCampaignId(): UuidInterface
    {
        return $this->campaignId;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getImpressions(): int
    {
        return $this->impressions;
    }

    /**
     * @return int
     */
    public function getClicks(): int
    {
        return $this->clicks;
    }

    /**
     * @return int
     */
    public function getConversions(): int
    {
        return $this->conversions;
    }

    /**
     * @return float
     */
    public function getSpend(): float
    {
        return $this->spend;
    }

    /**
     * @return float
     */
    public function getRevenue(): float
    {
        return $this->revenue;
    }

    /**
     * Return on ad spend (Revenue / Spend).
     *
     * @return float
     */
    public function getRoas(): float
    {
        return $this->roas;
    }

    /**
     * Cost per click (Spend / Clicks).
     *
     * @return float
     */
    public function getCpc(): float
    {
        return $this->cpc;
    }

    /**
     * Cost per mille / thousand impressions (Spend / Impressions * 1000).
     *
     * @return float
     */
    public function getCpm(): float
    {
        return $this->cpm;
    }

    /**
     * Click-through rate (Clicks / Impressions * 100).
     *
     * @return float
     */
    public function getCtr(): float
    {
        return $this->ctr;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
