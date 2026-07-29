<?php

declare(strict_types=1);

namespace App\Domain\Agent\Repositories;

use App\Domain\Agent\Entities\AgentTask;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface AgentTaskRepositoryInterface
 *
 * Repository contract for AgentTask entity persistence.
 *
 * @package App\Domain\Agent\Repositories
 */
interface AgentTaskRepositoryInterface
{
    /**
     * Find a task by its UUID.
     *
     * @param UuidInterface $id
     * @return AgentTask|null
     */
    public function findById(UuidInterface $id): ?AgentTask;

    /**
     * Find tasks assigned to a specific agent.
     *
     * @param UuidInterface $agentId
     * @return array<int, AgentTask>
     */
    public function findByAgentId(UuidInterface $agentId): array;

    /**
     * Find tasks for a specific campaign.
     *
     * @param UuidInterface $campaignId
     * @return array<int, AgentTask>
     */
    public function findByCampaignId(UuidInterface $campaignId): array;

    /**
     * Find tasks by status.
     *
     * @param string $status
     * @return array<int, AgentTask>
     */
    public function findByStatus(string $status): array;

    /**
     * Find pending tasks for an agent.
     *
     * @param UuidInterface $agentId
     * @return array<int, AgentTask>
     */
    public function findPendingByAgentId(UuidInterface $agentId): array;

    /**
     * Persist a task.
     *
     * @param AgentTask $task
     * @return AgentTask
     */
    public function save(AgentTask $task): AgentTask;

    /**
     * Delete a task.
     *
     * @param AgentTask $task
     * @return void
     */
    public function delete(AgentTask $task): void;
}
