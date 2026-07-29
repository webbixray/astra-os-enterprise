<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Repositories;

use App\Domain\Campaign\Entities\Campaign;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface CampaignRepositoryInterface
 *
 * Repository contract for Campaign aggregate root persistence.
 *
 * @package App\Domain\Campaign\Repositories
 */
interface CampaignRepositoryInterface
{
    /**
     * Find a campaign by its UUID.
     *
     * @param UuidInterface $id
     * @return Campaign|null
     */
    public function findById(UuidInterface $id): ?Campaign;

    /**
     * Find all campaigns for an organization.
     *
     * @param UuidInterface $organizationId
     * @return array<int, Campaign>
     */
    public function findByOrganizationId(UuidInterface $organizationId): array;

    /**
     * Find campaigns by status.
     *
     * @param string $status
     * @return array<int, Campaign>
     */
    public function findByStatus(string $status): array;

    /**
     * Find active campaigns for an organization.
     *
     * @param UuidInterface $organizationId
     * @return array<int, Campaign>
     */
    public function findActiveByOrganization(UuidInterface $organizationId): array;

    /**
     * Find all campaigns.
     *
     * @return array<int, Campaign>
     */
    public function findAll(): array;

    /**
     * Persist a campaign.
     *
     * @param Campaign $campaign
     * @return Campaign
     */
    public function save(Campaign $campaign): Campaign;

    /**
     * Delete a campaign.
     *
     * @param Campaign $campaign
     * @return void
     */
    public function delete(Campaign $campaign): void;
}
