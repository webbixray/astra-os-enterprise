<?php

declare(strict_types=1);

namespace App\Application\Agent\UseCases;

use App\Application\Agent\Services\ModelRouterService;
use App\Domain\Agent\Events\TaskCompleted;
use App\Domain\Agent\Repositories\AgentRepositoryInterface;
use App\Domain\Agent\Repositories\AgentTaskRepositoryInterface;
use RuntimeException;

final readonly class ProcessTaskUseCase
{
    public function __construct(
        private AgentRepositoryInterface $agentRepository,
        private AgentTaskRepositoryInterface $taskRepository,
        private ModelRouterService $modelRouter,
    ) {}

    /**
     * Process a task assigned to an agent.
     *
     * Simulates agent processing by routing the task input to an AI model provider
     * via the ModelRouterService, then persisting the result.
     *
     * @return array{ output: array, execution_time_ms: float }
     *
     * @throws RuntimeException if task or agent is not found.
     */
    public function execute(int $taskId): array
    {
        $task = $this->taskRepository->findById($taskId);

        if ($task === null) {
            throw new RuntimeException("Task with ID {$taskId} not found.");
        }

        $agent = $this->agentRepository->findById($task->getAgentId());

        if ($agent === null) {
            throw new RuntimeException("Agent with ID {$task->getAgentId()} not found.");
        }

        $task->markAsProcessing();
        $this->taskRepository->save($task);

        $startTime = microtime(true);

        try {
            $output = $this->modelRouter->route(
                model: $agent->getModel(),
                prompt: $task->getInput(),
                systemContext: [
                    'agent_role' => $agent->getRole(),
                    'agent_capabilities' => $agent->getCapabilities(),
                    'agent_configuration' => $agent->getConfiguration(),
                ],
            );

            $task->markAsCompleted($output);
        } catch (\Throwable $e) {
            $task->markAsFailed($e->getMessage());
            $this->taskRepository->save($task);

            throw new RuntimeException("Task processing failed: {$e->getMessage()}", 0, $e);
        }

        $this->taskRepository->save($task);

        $agent->setStatus('idle');
        $this->agentRepository->save($agent);

        $executionTime = (microtime(true) - $startTime) * 1000;

        TaskCompleted::dispatch($task);

        return [
            'output' => $output,
            'execution_time_ms' => round($executionTime, 2),
        ];
    }
}
