<?php

declare(strict_types=1);

namespace App\Application\Agent\UseCases;

use App\Domain\Agent\Repositories\AgentRepositoryInterface;
use App\Domain\Agent\Repositories\AgentTaskRepositoryInterface;
use RuntimeException;

final readonly class GetAgentMemoryUseCase
{
    public function __construct(
        private AgentRepositoryInterface $agentRepository,
        private AgentTaskRepositoryInterface $taskRepository,
    ) {}

    /**
     * Retrieve an agent's memory context from completed tasks.
     *
     * @return array{ agent: array, memory: array, recent_tasks: array }
     *
     * @throws RuntimeException if agent is not found.
     */
    public function execute(int $agentId): array
    {
        $agent = $this->agentRepository->findById($agentId);

        if ($agent === null) {
            throw new RuntimeException("Agent with ID {$agentId} not found.");
        }

        $tasks = $this->taskRepository->findByAgentId($agentId);

        $completedTasks = array_values(array_filter($tasks, fn ($t) => $t->getStatus() === 'completed'));

        $memory = [];
        foreach ($completedTasks as $task) {
            $memory[] = [
                'task_id' => $task->getId(),
                'type' => $task->getType(),
                'input' => $task->getInput(),
                'output' => $task->getOutput(),
                'completed_at' => $task->getCompletedAt()?->format('Y-m-d H:i:s'),
            ];
        }

        $recentTasks = array_map(fn ($t) => $t->toArray(), $tasks);

        return [
            'agent' => $agent->toArray(),
            'memory' => $memory,
            'recent_tasks' => array_slice($recentTasks, -10),
        ];
    }
}
