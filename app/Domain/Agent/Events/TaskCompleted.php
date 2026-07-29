<?php

declare(strict_types=1);

namespace App\Domain\Agent\Events;

use DateTimeImmutable;

/**
 * Domain Event: TaskCompleted
 *
 * Fired when an agent task is successfully completed.
 *
 * @package App\Domain\Agent\Events
 */
final class TaskCompleted
{
    /**
     * @param string      $taskId
     * @param string      $agentId
     * @param DateTimeImmutable $completedAt
     */
    public function __construct(
        public readonly string $taskId,
        public readonly string $agentId,
        public readonly DateTimeImmutable $completedAt
    ) {
    }
}
