<?php

declare(strict_types=1);

namespace App\Domain\Social\Events;

use DateTimeImmutable;

/**
 * Domain Event: PostPublished
 *
 * Fired when a social media post is successfully published.
 *
 * @package App\Domain\Social\Events
 */
final class PostPublished
{
    /**
     * @param string            $postId
     * @param string            $accountId
     * @param string            $platform
     * @param string            $platformPostId
     * @param DateTimeImmutable $publishedAt
     */
    public function __construct(
        public readonly string $postId,
        public readonly string $accountId,
        public readonly string $platform,
        public readonly string $platformPostId,
        public readonly DateTimeImmutable $publishedAt
    ) {
    }
}
