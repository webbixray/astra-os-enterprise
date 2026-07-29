<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Events;

use DateTimeImmutable;

/**
 * Domain Event: WorkflowCompleted
 *
 * Fired when a workflow execution completes successfully.
 *
 * @package App\Domain\Workflow\Events
 */
final class WorkflowCompleted
{
    /**
     * @param string            $executionId
     * @param string            $workflowId
     * @param DateTimeImmutable $completedAt
     */
    public function __construct(
        public readonly string $executionId,
        public readonly string $workflowId,
        public readonly DateTimeImmutable $completedAt
    ) {
    }
}
