<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: CampaignInsight
 *
 * Represents a single data point or metric collected for a campaign
 * on a given date. Insights are the raw building blocks of campaign
 * analytics, tracking everything from impressions and clicks to
 * custom metrics from various data sources.
 *
 * @package App\Domain\Campaign\Entities
 */
class CampaignInsight
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
     * @var string
     */
    private readonly string $metric;

    /**
     * @var float
     */
    private float $value;

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
     * @param string                 $metric
     * @param float                  $value
     * @param string                 $source
     * @param array<string, mixed>  $metadata
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $campaignId,
        DateTimeImmutable $date,
        string $metric,
        float $value,
        string $source,
        array $metadata
    ) {
        $this->id = $id;
        $this->campaignId = $campaignId;
        $this->date = $date;
        $this->metric = $metric;
        $this->value = $value;
        $this->source = $source;
        $this->metadata = $metadata;
    }

    /**
     * Create a new CampaignInsight.
     *
     * @param UuidInterface          $campaignId
     * @param DateTimeImmutable     $date
     * @param string                 $metric
     * @param float                  $value
     * @param string                 $source
     * @param array<string, mixed>  $metadata
     * @return self
     */
    public static function create(
        UuidInterface $campaignId,
        DateTimeImmutable $date,
        string $metric,
        float $value,
        string $source = 'manual',
        array $metadata = []
    ): self {
        $insight = new self(
            Uuid::uuid4(),
            $campaignId,
            $date,
            $metric,
            $value,
            $source,
            $metadata
        );

        $insight->initializeTimestamps();

        return $insight;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface          $id
     * @param UuidInterface          $campaignId
     * @param DateTimeImmutable     $date
     * @param string                 $metric
     * @param float                  $value
     * @param string                 $source
     * @param array<string, mixed>  $metadata
     * @param DateTimeImmutable     $createdAt
     * @param DateTimeImmutable     $updatedAt
     * @return self
     */
    public static function reconstitute(
        UuidInterface $id,
        UuidInterface $campaignId,
        DateTimeImmutable $date,
        string $metric,
        float $value,
        string $source,
        array $metadata,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        $insight = new self($id, $campaignId, $date, $metric, $value, $source, $metadata);
        $insight->setCreatedAt($createdAt);
        $insight->setUpdatedAt($updatedAt);

        return $insight;
    }

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
     * @return string
     */
    public function getMetric(): string
    {
        return $this->metric;
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * @param float $value
     * @return void
     */
    public function updateValue(float $value): void
    {
        $this->value = $value;
        $this->markAsUpdated();
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
