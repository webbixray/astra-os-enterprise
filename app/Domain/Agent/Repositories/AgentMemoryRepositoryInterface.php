<?php

declare(strict_types=1);

namespace App\Domain\Agent\Repositories;

use App\Domain\Agent\Entities\AgentMemory;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface AgentMemoryRepositoryInterface
 *
 * Repository contract for AgentMemory entity persistence.
 *
 * @package App\Domain\Agent\Repositories
 */
interface AgentMemoryRepositoryInterface
{
    /**
     * Find a memory by its UUID.
     *
     * @param UuidInterface $id
     * @return AgentMemory|null
     */
    public function findById(UuidInterface $id): ?AgentMemory;

    /**
     * Find all memories for an agent.
     *
     * @param UuidInterface $agentId
     * @return array<int, AgentMemory>
     */
    public function findByAgentId(UuidInterface $agentId): array;

    /**
     * Find memories by type for an agent.
     *
     * @param UuidInterface $agentId
     * @param string        $type
     * @return array<int, AgentMemory>
     */
    public function findByAgentIdAndType(UuidInterface $agentId, string $type): array;

    /**
     * Find a specific memory by agent ID and key.
     *
     * @param UuidInterface $agentId
     * @param string        $key
     * @return AgentMemory|null
     */
    public function findByAgentIdAndKey(UuidInterface $agentId, string $key): ?AgentMemory;

    /**
     * Find high-importance memories for an agent.
     *
     * @param UuidInterface $agentId
     * @param int           $minImportance
     * @return array<int, AgentMemory>
     */
    public function findImportantByAgentId(UuidInterface $agentId, int $minImportance = 7): array;

    /**
     * Persist a memory.
     *
     * @param AgentMemory $memory
     * @return AgentMemory
     */
    public function save(AgentMemory $memory): AgentMemory;

    /**
     * Delete a memory.
     *
     * @param AgentMemory $memory
     * @return void
     */
    public function delete(AgentMemory $memory): void;
}
