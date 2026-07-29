<?php

declare(strict_types=1);

namespace App\Domain\Organization\Repositories;

use App\Domain\Organization\Entities\Organization;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface OrganizationRepositoryInterface
 *
 * Repository contract for Organization aggregate root persistence.
 * Defines domain-specific query operations beyond the generic CRUD
 * operations inherited from RepositoryInterface.
 *
 * @package App\Domain\Organization\Repositories
 */
interface OrganizationRepositoryInterface
{
    /**
     * Find an organization by its UUID.
     *
     * @param UuidInterface $id The organization UUID.
     * @return Organization|null The organization, or null if not found.
     */
    public function findById(UuidInterface $id): ?Organization;

    /**
     * Find an organization by its URL slug.
     *
     * @param string $slug The URL slug.
     * @return Organization|null The organization, or null if not found.
     */
    public function findBySlug(string $slug): ?Organization;

    /**
     * Find all active organizations.
     *
     * @return array<int, Organization>
     */
    public function findAllActive(): array;

    /**
     * Find all organizations.
     *
     * @return array<int, Organization>
     */
    public function findAll(): array;

    /**
     * Persist an organization.
     *
     * @param Organization $organization
     * @return Organization
     */
    public function save(Organization $organization): Organization;

    /**
     * Delete an organization.
     *
     * @param Organization $organization
     * @return void
     */
    public function delete(Organization $organization): void;
}
