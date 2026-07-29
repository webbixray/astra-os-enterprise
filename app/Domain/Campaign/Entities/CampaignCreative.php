<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: CampaignCreative
 *
 * Represents a creative asset associated with a campaign. Creatives are
 * the visual or textual content used in advertisements across different
 * platforms. Each creative has a specific type, variant, and version
 * for A/B testing and iteration management.
 *
 * @package App\Domain\Campaign\Entities
 */
class CampaignCreative
{
    use HasTimestamps;

    /** @var string Image creative type. */
    public const string TYPE_IMAGE = 'image';

    /** @var string Video creative type. */
    public const string TYPE_VIDEO = 'video';

    /** @var string Text-only creative type. */
    public const string TYPE_TEXT = 'text';

    /** @var string Carousel (multi-image) creative type. */
    public const string TYPE_CAROUSEL = 'carousel';

    /** @var array<int, string> Valid creative types. */
    public const array VALID_TYPES = [
        self::TYPE_IMAGE,
        self::TYPE_VIDEO,
        self::TYPE_TEXT,
        self::TYPE_CAROUSEL,
    ];

    /** @var string Creative is in draft state. */
    public const string STATUS_DRAFT = 'draft';

    /** @var string Creative is awaiting approval. */
    public const string STATUS_PENDING = 'pending';

    /** @var string Creative has been approved. */
    public const string STATUS_APPROVED = 'approved';

    /** @var string Creative has been rejected. */
    public const string STATUS_REJECTED = 'rejected';

    /** @var string Creative is actively being used in campaigns. */
    public const string STATUS_ACTIVE = 'active';

    /** @var string Creative has been deactivated. */
    public const string STATUS_INACTIVE = 'inactive';

    /** @var array<int, string> Valid creative statuses. */
    public const array VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $campaignId;

    /**
     * @var string
     */
    private readonly string $type;

    /**
     * @var array<string, mixed>
     */
    private array $content;

    /**
     * @var string
     */
    private string $variant;

    /**
     * @var string
     */
    private string $status;

    /**
     * @var int
     */
    private int $version;

    /**
     * @var string|null
     */
    private ?string $approvedBy;

    /**
     * @param UuidInterface       $id
     * @param UuidInterface       $campaignId
     * @param string              $type
     * @param array<string, mixed> $content
     * @param string              $variant
     * @param string              $status
     * @param int                 $version
     * @param string|null         $approvedBy
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $campaignId,
        string $type,
        array $content,
        string $variant,
        string $status,
        int $version,
        ?string $approvedBy = null
    ) {
        $this->id = $id;
        $this->campaignId = $campaignId;
        $this->type = $type;
        $this->content = $content;
        $this->variant = $variant;
        $this->status = $status;
        $this->version = $version;
        $this->approvedBy = $approvedBy;
    }

    /**
     * Create a new CampaignCreative.
     *
     * @param UuidInterface       $campaignId
     * @param string              $type
     * @param array<string, mixed> $content
     * @param string              $variant
     * @return self
     */
    public static function create(
        UuidInterface $campaignId,
        string $type,
        array $content,
        string $variant = 'a'
    ): self {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid creative type: "%s". Valid types: %s.', $type, implode(', ', self::VALID_TYPES))
            );
        }

        $creative = new self(
            Uuid::uuid4(),
            $campaignId,
            $type,
            $content,
            $variant,
            self::STATUS_DRAFT,
            1
        );

        $creative->initializeTimestamps();

        return $creative;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface       $id
     * @param UuidInterface       $campaignId
     * @param string              $type
     * @param array<string, mixed> $content
     * @param string              $variant
     * @param string              $status
     * @param int                 $version
     * @param string|null         $approvedBy
     * @param DateTimeImmutable  $createdAt
     * @param DateTimeImmutable  $updatedAt
     * @return self
     */
    public static function reconstitute(
        UuidInterface $id,
        UuidInterface $campaignId,
        string $type,
        array $content,
        string $variant,
        string $status,
        int $version,
        ?string $approvedBy,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        $creative = new self($id, $campaignId, $type, $content, $variant, $status, $version, $approvedBy);
        $creative->setCreatedAt($createdAt);
        $creative->setUpdatedAt($updatedAt);

        return $creative;
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
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @param array<string, mixed> $content
     * @return void
     */
    public function setContent(array $content): void
    {
        $this->content = $content;
        $this->markAsUpdated();
    }

    /**
     * @return string
     */
    public function getVariant(): string
    {
        return $this->variant;
    }

    /**
     * @param string $variant
     * @return void
     */
    public function setVariant(string $variant): void
    {
        $this->variant = $variant;
        $this->markAsUpdated();
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Approve this creative.
     *
     * @param string $approvedBy The user ID who approved.
     * @return void
     */
    public function approve(string $approvedBy): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->approvedBy = $approvedBy;
        $this->markAsUpdated();
    }

    /**
     * Reject this creative.
     *
     * @return void
     */
    public function reject(): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->markAsUpdated();
    }

    /**
     * Activate this creative.
     *
     * @return void
     */
    public function activate(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->markAsUpdated();
    }

    /**
     * Deactivate this creative.
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->status = self::STATUS_INACTIVE;
        $this->markAsUpdated();
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Increment the creative version.
     *
     * @return void
     */
    public function incrementVersion(): void
    {
        $this->version++;
        $this->markAsUpdated();
    }

    /**
     * @return string|null
     */
    public function getApprovedBy(): ?string
    {
        return $this->approvedBy;
    }
}
