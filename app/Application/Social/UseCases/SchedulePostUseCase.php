<?php

declare(strict_types=1);

namespace App\Application\Social\UseCases;

use App\Application\Social\Services\SocialMediaScheduler;
use App\Domain\Social\Repositories\SocialPostRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final readonly class SchedulePostUseCase
{
    public function __construct(
        private SocialPostRepositoryInterface $postRepository,
        private SocialMediaScheduler $scheduler,
    ) {}

    /**
     * Schedule a post for future publication.
     *
     * @throws RuntimeException if post is not found.
     */
    public function execute(int $postId, DateTimeImmutable $scheduledAt): array
    {
        $post = $this->postRepository->findById($postId);

        if ($post === null) {
            throw new RuntimeException("Post with ID {$postId} not found.");
        }

        $this->scheduler->validateScheduleTime($scheduledAt, $post->getPlatforms());

        $post->schedule($scheduledAt);
        $this->postRepository->save($post);

        $this->scheduler->queueForPublishing($post, $scheduledAt);

        return [
            'id' => $post->getId(),
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
        ];
    }
}
