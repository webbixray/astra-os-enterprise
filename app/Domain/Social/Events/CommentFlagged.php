<?php

declare(strict_types=1);

namespace App\Domain\Social\Events;

use DateTimeImmutable;

/**
 * Domain Event: CommentFlagged
 *
 * Fired when a social media comment is flagged for review.
 *
 * @package App\Domain\Social\Events
 */
final class CommentFlagged
{
    /**
     * @param string            $commentId
     * @param string            $postId
     * @param string            $platform
     * @param string            $authorId
     * @param string            $content
     * @param DateTimeImmutable $flaggedAt
     */
    public function __construct(
        public readonly string $commentId,
        public readonly string $postId,
        public readonly string $platform,
        public readonly string $authorId,
        public readonly string $content,
        public readonly DateTimeImmutable $flaggedAt
    ) {
    }
}
