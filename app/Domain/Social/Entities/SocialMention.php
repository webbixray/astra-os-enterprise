<?php

declare(strict_types=1);

namespace App\Domain\Social\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: SocialMention
 *
 * Represents a mention or tag of the organization on a social media
 * platform. Mentions are monitored for brand reputation management
 * and can trigger AI-suggested responses.
 *
 * @package App\Domain\Social\Entities
 */
class SocialMention
{
    use HasTimestamps;

    /** @var string Mention is unread. */
    public const string STATUS_UNREAD = 'unread';

    /** @var string Mention has been read. */
    public const string STATUS_READ = 'read';

    /** @var string A response has been sent. */
    public const string STATUS_RESPONDED = 'responded';

    /** @var string Mention has been ignored. */
    public const string STATUS_IGNORED = 'ignored';

    /** @var array<int, string> Valid statuses. */
    public const array VALID_STATUSES = [
        self::STATUS_UNREAD,
        self::STATUS_READ,
        self::STATUS_RESPONDED,
        self::STATUS_IGNORED,
    ];

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var string
     */
    private readonly string $platform;

    /**
     * @var string
     */
    private readonly string $mentionUrl;

    /**
     * @var string
     */
    private readonly string $authorName;

    /**
     * @var string
     */
    private readonly string $content;

    /**
     * @var string
     */
    private string $sentiment;

    /**
     * @var int
     */
    private int $reach;

    /**
     * @var string|null
     */
    private ?string $aiSuggestedResponse;

    /**
     * @var string
     */
    private string $status;

    /**
     * @param UuidInterface $id
     * @param string        $platform
     * @param string        $mentionUrl
     * @param string        $authorName
     * @param string        $content
     * @param string        $sentiment
     * @param int           $reach
     * @param string|null   $aiSuggestedResponse
     * @param string        $status
     */
    private function __construct(
        UuidInterface $id,
        string $platform,
        string $mentionUrl,
        string $authorName,
        string $content,
        string $sentiment,
        int $reach,
        ?string $aiSuggestedResponse,
        string $status
    ) {
        $this->id = $id;
        $this->platform = $platform;
        $this->mentionUrl = $mentionUrl;
        $this->authorName = $authorName;
        $this->content = $content;
        $this->sentiment = $sentiment;
        $this->reach = $reach;
        $this->aiSuggestedResponse = $aiSuggestedResponse;
        $this->status = $status;
    }

    /**
     * Create a new SocialMention.
     *
     * @param string      $platform
     * @param string      $mentionUrl
     * @param string      $authorName
     * @param string      $content
     * @param string      $sentiment
     * @param int         $reach
     * @return self
     */
    public static function create(
        string $platform,
        string $mentionUrl,
        string $authorName,
        string $content,
        string $sentiment = 'neutral',
        int $reach = 0
    ): self {
        $mention = new self(
            Uuid::uuid4(),
            $platform,
            $mentionUrl,
            $authorName,
            $content,
            $sentiment,
            $reach,
            null,
            self::STATUS_UNREAD
        );

        $mention->initializeTimestamps();

        return $mention;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface     $id
     * @param string            $platform
     * @param string            $mentionUrl
     * @param string            $authorName
     * @param string            $content
     * @param string            $sentiment
     * @param int               $reach
     * @param string|null       $aiSuggestedResponse
     * @param string            $status
     * @param DateTimeImmutable $createdAt
     * @param DateTimeImmutable $updatedAt
     * @return self
     */
    public static function reconstitute(...$args): self
    {
        $mention = new self(...array_slice($args, 0, 9));
        $mention->setCreatedAt($args[9]);
        $mention->setUpdatedAt($args[10]);

        return $mention;
    }

    /**
     * Mark the mention as read.
     *
     * @return void
     */
    public function markAsRead(): void
    {
        $this->status = self::STATUS_READ;
        $this->markAsUpdated();
    }

    /**
     * Mark the mention as responded.
     *
     * @return void
     */
    public function markAsResponded(): void
    {
        $this->status = self::STATUS_RESPONDED;
        $this->markAsUpdated();
    }

    /**
     * Mark the mention as ignored.
     *
     * @return void
     */
    public function markAsIgnored(): void
    {
        $this->status = self::STATUS_IGNORED;
        $this->markAsUpdated();
    }

    /**
     * Set the AI-suggested response.
     *
     * @param string $response
     * @return void
     */
    public function setAiSuggestedResponse(string $response): void
    {
        $this->aiSuggestedResponse = $response;
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
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * @return string
     */
    public function getMentionUrl(): string
    {
        return $this->mentionUrl;
    }

    /**
     * @return string
     */
    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getSentiment(): string
    {
        return $this->sentiment;
    }

    /**
     * @return int
     */
    public function getReach(): int
    {
        return $this->reach;
    }

    /**
     * @return string|null
     */
    public function getAiSuggestedResponse(): ?string
    {
        return $this->aiSuggestedResponse;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isUnread(): bool
    {
        return $this->status === self::STATUS_UNREAD;
    }

    /**
     * @return bool
     */
    public function isResponded(): bool
    {
        return $this->status === self::STATUS_RESPONDED;
    }
}
