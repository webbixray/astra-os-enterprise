<?php

declare(strict_types=1);

namespace App\Domain\Agent\Repositories;

use App\Domain\Agent\Entities\Agent;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface AgentRepositoryInterface
 *
 * Repository contract for Agent aggregate root persistence.
 *
 * @package App\Domain\Agent\Repositories
 */
interface AgentRepositoryInterface
{
    /**
     * Find an agent by its UUID.
     *
     * @param UuidInterface $id
     * @return Agent|null
     */
    public function findById(UuidInterface $id): ?Agent;

    /**
     * Find all agents for an organization.
     *
     * @param UuidInterface $organizationId
     * @return array<int, Agent>
     */
    public function findByOrganizationId(UuidInterface $organizationId): array;

    /**
     * Find agents by role.
     *
     * @param string $role
     * @return array<int, Agent>
     */
    public function findByRole(string $role): array;

    /**
     * Find child agents of a given parent.
     *
     * @param UuidInterface $parentAgentId
     * @return array<int, Agent>
     */
    public function findChildrenOf(UuidInterface $parentAgentId): array;

    /**
     * Find all active agents.
     *
     * @return array<int, Agent>
     */
    public function findAllActive(): array;

    /**
     * Find all agents.
     *
     * @return array<int, Agent>
     */
    public function findAll(): array;

    /**
     * Persist an agent.
     *
     * @param Agent $agent
     * @return Agent
     */
    public function save(Agent $agent): Agent;

    /**
     * Delete an agent.
     *
     * @param Agent $agent
     * @return void
     */
    public function delete(Agent $agent): void;
}
