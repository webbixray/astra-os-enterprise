<?php

declare(strict_types=1);

namespace App\Domain\Social\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: SocialPost
 *
 * Represents a social media post that is either scheduled, published,
 * or in draft state. Tracks the content, media attachments, scheduling,
 * and platform-specific publishing status.
 *
 * @package App\Domain\Social\Entities
 */
class SocialPost
{
    use HasTimestamps;

    /** @var string Post is a draft. */
    public const string STATUS_DRAFT = 'draft';

    /** @var string Post is scheduled for future publication. */
    public const string STATUS_SCHEDULED = 'scheduled';

    /** @var string Post has been published. */
    public const string STATUS_PUBLISHED = 'published';

    /** @var string Post publishing failed. */
    public const string STATUS_FAILED = 'failed';

    /** @var array<int, string> Valid post statuses. */
    public const array VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SCHEDULED,
        self::STATUS_PUBLISHED,
        self::STATUS_FAILED,
    ];

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $accountId;

    /**
     * @var UuidInterface|null
     */
    private readonly ?UuidInterface $campaignId;

    /**
     * @var string
     */
    private string $content;

    /**
     * @var array<int, string>
     */
    private array $media;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $scheduledAt;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $publishedAt;

    /**
     * @var string
     */
    private string $status;

    /**
     * @var string|null
     */
    private ?string $platformPostId;

    /**
     * @var array<string, mixed>
     */
    private array $metrics;

    /**
     * @param UuidInterface             $id
     * @param UuidInterface             $accountId
     * @param UuidInterface|null        $campaignId
     * @param string                    $content
     * @param array<int, string>        $media
     * @param DateTimeImmutable|null    $scheduledAt
     * @param DateTimeImmutable|null    $publishedAt
     * @param string                    $status
     * @param string|null               $platformPostId
     * @param array<string, mixed>     $metrics
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $accountId,
        ?UuidInterface $campaignId,
        string $content,
        array $media,
        ?DateTimeImmutable $scheduledAt,
        ?DateTimeImmutable $publishedAt,
        string $status,
        ?string $platformPostId,
        array $metrics
    ) {
        $this->id = $id;
        $this->accountId = $accountId;
        $this->campaignId = $campaignId;
        $this->content = $content;
        $this->media = $media;
        $this->scheduledAt = $scheduledAt;
        $this->publishedAt = $publishedAt;
        $this->status = $status;
        $this->platformPostId = $platformPostId;
        $this->metrics = $metrics;
    }

    /**
     * Create a new SocialPost.
     *
     * @param UuidInterface          $accountId
     * @param string                 $content
     * @param UuidInterface|null     $campaignId
     * @param array<int, string>    $media
     * @param DateTimeImmutable|null $scheduledAt
     * @return self
     */
    public static function create(
        UuidInterface $accountId,
        string $content,
        ?UuidInterface $campaignId = null,
        array $media = [],
        ?DateTimeImmutable $scheduledAt = null
    ): self {
        $post = new self(
            Uuid::uuid4(),
            $accountId,
            $campaignId,
            $content,
            $media,
            $scheduledAt,
            null,
            $scheduledAt !== null ? self::STATUS_SCHEDULED : self::STATUS_DRAFT,
            null,
            []
        );

        $post->initializeTimestamps();

        return $post;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface             $id
     * @param UuidInterface             $accountId
     * @param UuidInterface|null        $campaignId
     * @param string                    $content
     * @param array<int, string>        $media
     * @param DateTimeImmutable|null    $scheduledAt
     * @param DateTimeImmutable|null    $publishedAt
     * @param string                    $status
     * @param string|null               $platformPostId
     * @param array<string, mixed>     $metrics
     * @param DateTimeImmutable         $createdAt
     * @param DateTimeImmutable         $updatedAt
     * @return self
     */
    public static function reconstitute(...$args): self
    {
        $post = new self(...array_slice($args, 0, 10));
        $post->setCreatedAt($args[10]);
        $post->setUpdatedAt($args[11]);

        return $post;
    }

    /**
     * Schedule the post for a future date.
     *
     * @param DateTimeImmutable $scheduledAt
     * @return void
     */
    public function schedule(DateTimeImmutable $scheduledAt): void
    {
        $this->scheduledAt = $scheduledAt;
        $this->status = self::STATUS_SCHEDULED;
        $this->markAsUpdated();
    }

    /**
     * Mark the post as published.
     *
     * @param string $platformPostId
     * @return void
     */
    public function publish(string $platformPostId): void
    {
        $this->status = self::STATUS_PUBLISHED;
        $this->publishedAt = new DateTimeImmutable();
        $this->platformPostId = $platformPostId;
        $this->markAsUpdated();
    }

    /**
     * Mark the post as failed.
     *
     * @return void
     */
    public function fail(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->markAsUpdated();
    }

    /**
     * Update post metrics.
     *
     * @param array<string, mixed> $metrics
     * @return void
     */
    public function updateMetrics(array $metrics): void
    {
        $this->metrics = $metrics;
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
     * @return string
     */
    public function getIdString(): string
    {
        return $this->id->toString();
    }

    /**
     * @return UuidInterface
     */
    public function getAccountId(): UuidInterface
    {
        return $this->accountId;
    }

    /**
     * @return UuidInterface|null
     */
    public function getCampaignId(): ?UuidInterface
    {
        return $this->campaignId;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return void
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->markAsUpdated();
    }

    /**
     * @return array<int, string>
     */
    public function getMedia(): array
    {
        return $this->media;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getScheduledAt(): ?DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getPlatformPostId(): ?string
    {
        return $this->platformPostId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
