<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Events;

/**
 * Domain Event: WorkflowActivated
 *
 * Fired when a workflow transitions from draft to active status.
 *
 * @package App\Domain\Workflow\Events
 */
final class WorkflowActivated
{
    /**
     * @param string $workflowId
     */
    public function __construct(
        public readonly string $workflowId
    ) {
    }
}
