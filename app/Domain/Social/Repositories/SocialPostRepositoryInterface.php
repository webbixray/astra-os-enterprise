<?php

declare(strict_types=1);

namespace App\Domain\Social\Repositories;

use App\Domain\Social\Entities\SocialPost;
use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface SocialPostRepositoryInterface
 *
 * Repository contract for SocialPost entity persistence.
 *
 * @package App\Domain\Social\Repositories
 */
interface SocialPostRepositoryInterface
{
    /**
     * Find a post by its UUID.
     *
     * @param UuidInterface $id
     * @return SocialPost|null
     */
    public function findById(UuidInterface $id): ?SocialPost;

    /**
     * Find posts by account.
     *
     * @param UuidInterface $accountId
     * @return array<int, SocialPost>
     */
    public function findByAccountId(UuidInterface $accountId): array;

    /**
     * Find posts by campaign.
     *
     * @param UuidInterface $campaignId
     * @return array<int, SocialPost>
     */
    public function findByCampaignId(UuidInterface $campaignId): array;

    /**
     * Find scheduled posts due for publication.
     *
     * @param DateTimeImmutable $before
     * @return array<int, SocialPost>
     */
    public function findScheduledDue(DateTimeImmutable $before): array;

    /**
     * Find posts by status.
     *
     * @param string $status
     * @return array<int, SocialPost>
     */
    public function findByStatus(string $status): array;

    /**
     * Persist a post.
     *
     * @param SocialPost $post
     * @return SocialPost
     */
    public function save(SocialPost $post): SocialPost;

    /**
     * Delete a post.
     *
     * @param SocialPost $post
     * @return void
     */
    public function delete(SocialPost $post): void;
}
