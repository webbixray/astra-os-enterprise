<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Repositories;

use App\Domain\Workflow\Entities\WorkflowExecution;
use Ramsey\Uuid\UuidInterface;

/**
 * Interface WorkflowExecutionRepositoryInterface
 *
 * Repository contract for WorkflowExecution entity persistence.
 *
 * @package App\Domain\Workflow\Repositories
 */
interface WorkflowExecutionRepositoryInterface
{
    /**
     * Find an execution by its UUID.
     *
     * @param UuidInterface $id
     * @return WorkflowExecution|null
     */
    public function findById(UuidInterface $id): ?WorkflowExecution;

    /**
     * Find executions for a workflow.
     *
     * @param UuidInterface $workflowId
     * @return array<int, WorkflowExecution>
     */
    public function findByWorkflowId(UuidInterface $workflowId): array;

    /**
     * Find executions by status.
     *
     * @param string $status
     * @return array<int, WorkflowExecution>
     */
    public function findByStatus(string $status): array;

    /**
     * Find running executions for a workflow.
     *
     * @param UuidInterface $workflowId
     * @return array<int, WorkflowExecution>
     */
    public function findRunningByWorkflowId(UuidInterface $workflowId): array;

    /**
     * Persist an execution.
     *
     * @param WorkflowExecution $execution
     * @return WorkflowExecution
     */
    public function save(WorkflowExecution $execution): WorkflowExecution;

    /**
     * Delete an execution.
     *
     * @param WorkflowExecution $execution
     * @return void
     */
    public function delete(WorkflowExecution $execution): void;
}
