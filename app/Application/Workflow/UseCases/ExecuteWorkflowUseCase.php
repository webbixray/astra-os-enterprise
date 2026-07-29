<?php

declare(strict_types=1);

namespace App\Application\Workflow\UseCases;

use App\Application\Workflow\Services\WorkflowEngineService;
use App\Domain\Workflow\Events\WorkflowExecuted;
use App\Domain\Workflow\Repositories\WorkflowRepositoryInterface;
use RuntimeException;

final readonly class ExecuteWorkflowUseCase
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
        private WorkflowEngineService $engineService,
    ) {}

    /**
     * Execute a workflow.
     *
     * Processes all nodes in the workflow graph, evaluating conditions
     * and executing actions, then returns the execution results.
     *
     * @return array{ workflow_id: int, status: string, results: array, execution_time_ms: float }
     *
     * @throws RuntimeException if workflow is not found or not active.
     */
    public function execute(int $workflowId, array $inputVariables = []): array
    {
        $workflow = $this->workflowRepository->findById($workflowId);

        if ($workflow === null) {
            throw new RuntimeException("Workflow with ID {$workflowId} not found.");
        }

        if ($workflow->getStatus() !== 'active') {
            throw new RuntimeException(
                "Workflow '{$workflow->getName()}' is not active (status: {$workflow->getStatus()})."
            );
        }

        $startTime = microtime(true);

        $results = $this->engineService->execute($workflow, $inputVariables);

        $executionTime = (microtime(true) - $startTime) * 1000;

        WorkflowExecuted::dispatch($workflow, $results);

        return [
            'workflow_id' => $workflowId,
            'status' => 'completed',
            'results' => $results,
            'execution_time_ms' => round($executionTime, 2),
        ];
    }
}
