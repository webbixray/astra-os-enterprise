<?php

declare(strict_types=1);

namespace App\Application\Social\UseCases;

use App\Application\Social\DTOs\CreatePostDTO;
use App\Application\Social\DTOs\SocialResponseDTO;
use App\Domain\Social\Entities\SocialPost;
use App\Domain\Social\Events\SocialPostCreated;
use App\Domain\Social\Repositories\SocialPostRepositoryInterface;
use DateTimeImmutable;

final readonly class CreatePostUseCase
{
    public function __construct(
        private SocialPostRepositoryInterface $postRepository,
    ) {}

    /**
     * Create a new social media post.
     *
     * Persists the post and dispatches the SocialPostCreated event.
     */
    public function execute(CreatePostDTO $dto): SocialResponseDTO
    {
        if (empty(trim($dto->content))) {
            throw new \InvalidArgumentException('Post content cannot be empty.');
        }

        if (empty($dto->platforms)) {
            throw new \InvalidArgumentException('At least one platform must be specified.');
        }

        $post = new SocialPost(
            organizationId: $dto->organizationId,
            socialAccountId: $dto->socialAccountId,
            content: $dto->content,
            platforms: $dto->platforms,
            mediaUrl: $dto->mediaUrl,
            scheduledAt: $dto->scheduledAt,
        );

        $post = $this->postRepository->save($post);

        SocialPostCreated::dispatch($post);

        return SocialResponseDTO::fromArray($post->toArray());
    }
}
