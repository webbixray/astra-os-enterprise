<?php

declare(strict_types=1);

namespace App\Domain\Social\Repositories;

use App\Domain\Social\Entities\SocialMention;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface SocialMentionRepositoryInterface
 *
 * Repository contract for SocialMention entity persistence.
 *
 * @package App\Domain\Social\Repositories
 */
interface SocialMentionRepositoryInterface
{
    /**
     * Find a mention by its UUID.
     *
     * @param UuidInterface $id
     * @return SocialMention|null
     */
    public function findById(UuidInterface $id): ?SocialMention;

    /**
     * Find mentions by platform.
     *
     * @param string $platform
     * @return array<int, SocialMention>
     */
    public function findByPlatform(string $platform): array;

    /**
     * Find unread mentions.
     *
     * @return array<int, SocialMention>
     */
    public function findUnread(): array;

    /**
     * Find mentions by status.
     *
     * @param string $status
     * @return array<int, SocialMention>
     */
    public function findByStatus(string $status): array;

    /**
     * Find recent mentions.
     *
     * @param int $limit
     * @return array<int, SocialMention>
     */
    public function findRecent(int $limit = 50): array;

    /**
     * Persist a mention.
     *
     * @param SocialMention $mention
     * @return SocialMention
     */
    public function save(SocialMention $mention): SocialMention;

    /**
     * Delete a mention.
     *
     * @param SocialMention $mention
     * @return void
     */
    public function delete(SocialMention $mention): void;
}
