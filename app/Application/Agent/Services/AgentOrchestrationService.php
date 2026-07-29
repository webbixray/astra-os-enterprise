<?php

declare(strict_types=1);

namespace App\Application\Agent\Services;

use App\Application\Agent\DTOs\AssignTaskDTO;
use App\Application\Agent\UseCases\AssignTaskUseCase;
use App\Domain\Agent\Repositories\AgentRepositoryInterface;
use RuntimeException;

final readonly class AgentOrchestrationService
{
    private const array ROLE_HIERARCHY = [
        'ceo' => ['director', 'manager'],
        'director' => ['manager', 'specialist'],
        'manager' => ['specialist'],
        'specialist' => [],
    ];

    public function __construct(
        private AgentRepositoryInterface $agentRepository,
        private AssignTaskUseCase $assignTaskUseCase,
    ) {}

    /**
     * Decompose a high-level task hierarchically (CEO -> Director -> Manager -> Specialist).
     *
     * @return array{ parent_task_id: int, sub_tasks: array }
     */
    public function decomposeTask(int $organizationId, string $taskType, array $input): array
    {
        $agents = $this->agentRepository->findByOrganizationId($organizationId);
        $ceoAgent = null;

        foreach ($agents as $agent) {
            if ($agent->getRole() === 'ceo') {
                $ceoAgent = $agent;
                break;
            }
        }

        if ($ceoAgent === null) {
            throw new RuntimeException('No CEO agent found for task decomposition.');
        }

        $ceoResult = $this->assignTaskUseCase->execute(
            new AssignTaskDTO(
                agentId: $ceoAgent->getId(),
                type: $taskType,
                input: $input,
            )
        );

        $subTasks = $this->decomposeToSubAgents(
            parentAgentRole: 'ceo',
            parentTaskId: $ceoResult['task_id'],
            agents: $agents,
            organizationId: $organizationId,
            input: $input,
        );

        return [
            'parent_task_id' => $ceoResult['task_id'],
            'sub_tasks' => $subTasks,
        ];
    }

    private function decomposeToSubAgents(
        string $parentAgentRole,
        int $parentTaskId,
        array $agents,
        int $organizationId,
        array $input,
    ): array {
        $subTasks = [];
        $childRoles = self::ROLE_HIERARCHY[$parentAgentRole] ?? [];

        foreach ($childRoles as $childRole) {
            foreach ($agents as $agent) {
                if ($agent->getRole() !== $childRole) {
                    continue;
                }

                $result = $this->assignTaskUseCase->execute(
                    new AssignTaskDTO(
                        agentId: $agent->getId(),
                        type: 'sub_task',
                        input: array_merge($input, ['delegated_by_task' => $parentTaskId]),
                        parentTaskId: $parentTaskId,
                    )
                );

                $subTasks[] = [
                    'task_id' => $result['task_id'],
                    'agent_id' => $result['agent_id'],
                    'agent_role' => $childRole,
                    'sub_tasks' => $this->decomposeToSubAgents(
                        parentAgentRole: $childRole,
                        parentTaskId: $result['task_id'],
                        agents: $agents,
                        organizationId: $organizationId,
                        input: $input,
                    ),
                ];
            }
        }

        return $subTasks;
    }
}
