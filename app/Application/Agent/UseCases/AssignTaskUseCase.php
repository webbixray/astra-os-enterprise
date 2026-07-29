<?php

declare(strict_types=1);

namespace App\Application\Agent\UseCases;

use App\Application\Agent\DTOs\AssignTaskDTO;
use App\Domain\Agent\Entities\AgentTask;
use App\Domain\Agent\Events\TaskAssigned;
use App\Domain\Agent\Repositories\AgentRepositoryInterface;
use App\Domain\Agent\Repositories\AgentTaskRepositoryInterface;
use RuntimeException;

final readonly class AssignTaskUseCase
{
    public function __construct(
        private AgentRepositoryInterface $agentRepository,
        private AgentTaskRepositoryInterface $taskRepository,
    ) {}

    /**
     * Assign a task to an agent.
     *
     * @return array{ task_id: int, agent_id: int }
     *
     * @throws RuntimeException if agent is not found.
     */
    public function execute(AssignTaskDTO $dto): array
    {
        $agent = $this->agentRepository->findById($dto->agentId);

        if ($agent === null) {
            throw new RuntimeException("Agent with ID {$dto->agentId} not found.");
        }

        $task = new AgentTask(
            agentId: $dto->agentId,
            type: $dto->type,
            input: $dto->input,
            parentTaskId: $dto->parentTaskId,
        );

        $task = $this->taskRepository->save($task);

        $agent->setStatus('busy');
        $this->agentRepository->save($agent);

        TaskAssigned::dispatch($task);

        return [
            'task_id' => $task->getId(),
            'agent_id' => $dto->agentId,
        ];
    }
}
