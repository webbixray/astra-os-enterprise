<?php

declare(strict_types=1);

namespace App\Application\Workflow\UseCases;

use App\Domain\Workflow\Events\WorkflowActivated;
use App\Domain\Workflow\Events\WorkflowDeactivated;
use App\Domain\Workflow\Repositories\WorkflowRepositoryInterface;
use RuntimeException;

final readonly class ActivateWorkflowUseCase
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    /**
     * Activate or deactivate a workflow.
     *
     * @throws RuntimeException if workflow is not found.
     */
    public function execute(int $workflowId, bool $activate = true): void
    {
        $workflow = $this->workflowRepository->findById($workflowId);

        if ($workflow === null) {
            throw new RuntimeException("Workflow with ID {$workflowId} not found.");
        }

        if ($activate) {
            $workflow->activate();
            WorkflowActivated::dispatch($workflow);
        } else {
            $workflow->deactivate();
            WorkflowDeactivated::dispatch($workflow);
        }

        $this->workflowRepository->save($workflow);
    }
}
