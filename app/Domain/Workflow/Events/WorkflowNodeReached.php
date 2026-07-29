<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Events;

use DateTimeImmutable;

/**
 * Domain Event: WorkflowNodeReached
 *
 * Fired when a workflow execution reaches a specific node.
 * Useful for tracking progress, triggering node-specific logic,
 * and monitoring workflow execution flow.
 *
 * @package App\Domain\Workflow\Events
 */
final class WorkflowNodeReached
{
    /**
     * @param string            $executionId
     * @param string            $workflowId
     * @param string            $nodeId
     * @param string            $nodeType
     * @param DateTimeImmutable $reachedAt
     */
    public function __construct(
        public readonly string $executionId,
        public readonly string $workflowId,
        public readonly string $nodeId,
        public readonly string $nodeType,
        public readonly DateTimeImmutable $reachedAt
    ) {
    }
}
