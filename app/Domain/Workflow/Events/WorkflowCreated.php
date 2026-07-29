<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Events;

/**
 * Domain Event: WorkflowCreated
 *
 * Fired when a new workflow is created.
 *
 * @package App\Domain\Workflow\Events
 */
final class WorkflowCreated
{
    /**
     * @param string $workflowId
     * @param string $organizationId
     * @param string $name
     */
    public function __construct(
        public readonly string $workflowId,
        public readonly string $organizationId,
        public readonly string $name
    ) {
    }
}
