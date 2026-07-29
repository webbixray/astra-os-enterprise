<?php

declare(strict_types=1);

namespace App\Domain\Social\Events;

use DateTimeImmutable;

/**
 * Domain Event: PostScheduled
 *
 * Fired when a social media post is scheduled for future publication.
 *
 * @package App\Domain\Social\Events
 */
final class PostScheduled
{
    /**
     * @param string            $postId
     * @param string            $accountId
     * @param DateTimeImmutable $scheduledAt
     */
    public function __construct(
        public readonly string $postId,
        public readonly string $accountId,
        public readonly DateTimeImmutable $scheduledAt
    ) {
    }
}
