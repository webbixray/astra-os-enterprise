<?php

declare(strict_types=1);

namespace App\Domain\Social\Repositories;

use App\Domain\Social\Entities\SocialAccount;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface SocialAccountRepositoryInterface
 *
 * Repository contract for SocialAccount entity persistence.
 *
 * @package App\Domain\Social\Repositories
 */
interface SocialAccountRepositoryInterface
{
    /**
     * Find an account by its UUID.
     *
     * @param UuidInterface $id
     * @return SocialAccount|null
     */
    public function findById(UuidInterface $id): ?SocialAccount;

    /**
     * Find accounts for an organization.
     *
     * @param UuidInterface $organizationId
     * @return array<int, SocialAccount>
     */
    public function findByOrganizationId(UuidInterface $organizationId): array;

    /**
     * Find accounts by platform.
     *
     * @param string $platform
     * @return array<int, SocialAccount>
     */
    public function findByPlatform(string $platform): array;

    /**
     * Find active accounts for an organization.
     *
     * @param UuidInterface $organizationId
     * @return array<int, SocialAccount>
     */
    public function findActiveByOrganization(UuidInterface $organizationId): array;

    /**
     * Persist an account.
     *
     * @param SocialAccount $account
     * @return SocialAccount
     */
    public function save(SocialAccount $account): SocialAccount;

    /**
     * Delete an account.
     *
     * @param SocialAccount $account
     * @return void
     */
    public function delete(SocialAccount $account): void;
}
