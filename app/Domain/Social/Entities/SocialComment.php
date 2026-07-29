<?php

declare(strict_types=1);

namespace App\Domain\Social\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: SocialComment
 *
 * Represents a comment received on a published social media post.
 * Tracks the comment's content, author, sentiment analysis, and
 * whether a reply has been generated or sent.
 *
 * @package App\Domain\Social\Entities
 */
class SocialComment
{
    use HasTimestamps;

    /** @var string Positive sentiment. */
    public const string SENTIMENT_POSITIVE = 'positive';

    /** @var string Neutral sentiment. */
    public const string SENTIMENT_NEUTRAL = 'neutral';

    /** @var string Negative sentiment. */
    public const string SENTIMENT_NEGATIVE = 'negative';

    /** @var array<int, string> Valid sentiment values. */
    public const array VALID_SENTIMENTS = [
        self::SENTIMENT_POSITIVE,
        self::SENTIMENT_NEUTRAL,
        self::SENTIMENT_NEGATIVE,
    ];

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $postId;

    /**
     * @var string
     */
    private readonly string $platform;

    /**
     * @var string
     */
    private readonly string $authorName;

    /**
     * @var string
     */
    private readonly string $authorId;

    /**
     * @var string
     */
    private readonly string $content;

    /**
     * @var string
     */
    private string $sentiment;

    /**
     * @var bool
     */
    private bool $isFlagged;

    /**
     * @var bool
     */
    private bool $isReplied;

    /**
     * @var string|null
     */
    private ?string $aiReply;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $repliedAt;

    /**
     * @param UuidInterface          $id
     * @param UuidInterface          $postId
     * @param string                 $platform
     * @param string                 $authorName
     * @param string                 $authorId
     * @param string                 $content
     * @param string                 $sentiment
     * @param bool                   $isFlagged
     * @param bool                   $isReplied
     * @param string|null            $aiReply
     * @param DateTimeImmutable|null $repliedAt
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $postId,
        string $platform,
        string $authorName,
        string $authorId,
        string $content,
        string $sentiment,
        bool $isFlagged,
        bool $isReplied,
        ?string $aiReply,
        ?DateTimeImmutable $repliedAt
    ) {
        $this->id = $id;
        $this->postId = $postId;
        $this->platform = $platform;
        $this->authorName = $authorName;
        $this->authorId = $authorId;
        $this->content = $content;
        $this->sentiment = $sentiment;
        $this->isFlagged = $isFlagged;
        $this->isReplied = $isReplied;
        $this->aiReply = $aiReply;
        $this->repliedAt = $repliedAt;
    }

    /**
     * Create a new SocialComment.
     *
     * @param UuidInterface $postId
     * @param string        $platform
     * @param string        $authorName
     * @param string        $authorId
     * @param string        $content
     * @param string        $sentiment
     * @return self
     */
    public static function create(
        UuidInterface $postId,
        string $platform,
        string $authorName,
        string $authorId,
        string $content,
        string $sentiment = self::SENTIMENT_NEUTRAL
    ): self {
        $comment = new self(
            Uuid::uuid4(),
            $postId,
            $platform,
            $authorName,
            $authorId,
            $content,
            $sentiment,
            false,
            false,
            null,
            null
        );

        $comment->initializeTimestamps();

        return $comment;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface          $id
     * @param UuidInterface          $postId
     * @param string                 $platform
     * @param string                 $authorName
     * @param string                 $authorId
     * @param string                 $content
     * @param string                 $sentiment
     * @param bool                   $isFlagged
     * @param bool                   $isReplied
     * @param string|null            $aiReply
     * @param DateTimeImmutable|null $repliedAt
     * @param DateTimeImmutable      $createdAt
     * @param DateTimeImmutable      $updatedAt
     * @return self
     */
    public static function reconstitute(...$args): self
    {
        $comment = new self(...array_slice($args, 0, 11));
        $comment->setCreatedAt($args[11]);
        $comment->setUpdatedAt($args[12]);

        return $comment;
    }

    /**
     * Flag the comment for review.
     *
     * @return void
     */
    public function flag(): void
    {
        $this->isFlagged = true;
        $this->markAsUpdated();
    }

    /**
     * Set the AI-generated reply for this comment.
     *
     * @param string $reply
     * @return void
     */
    public function setAiReply(string $reply): void
    {
        $this->aiReply = $reply;
        $this->markAsUpdated();
    }

    /**
     * Mark the comment as replied.
     *
     * @return void
     */
    public function markAsReplied(): void
    {
        $this->isReplied = true;
        $this->repliedAt = new DateTimeImmutable();
        $this->markAsUpdated();
    }

    /**
     * Update the sentiment analysis.
     *
     * @param string $sentiment
     * @return void
     */
    public function updateSentiment(string $sentiment): void
    {
        $this->sentiment = $sentiment;
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
    public function getPostId(): UuidInterface
    {
        return $this->postId;
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
    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    /**
     * @return string
     */
    public function getAuthorId(): string
    {
        return $this->authorId;
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
     * @return bool
     */
    public function getIsFlagged(): bool
    {
        return $this->isFlagged;
    }

    /**
     * @return bool
     */
    public function isFlagged(): bool
    {
        return $this->isFlagged;
    }

    /**
     * @return bool
     */
    public function getIsReplied(): bool
    {
        return $this->isReplied;
    }

    /**
     * @return bool
     */
    public function isReplied(): bool
    {
        return $this->isReplied;
    }

    /**
     * @return string|null
     */
    public function getAiReply(): ?string
    {
        return $this->aiReply;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getRepliedAt(): ?DateTimeImmutable
    {
        return $this->repliedAt;
    }
}
