<?php

declare(strict_types=1);

namespace App\Domain\Agent\Events;

use DateTimeImmutable;

/**
 * Domain Event: TaskFailed
 *
 * Fired when an agent task fails during processing.
 *
 * @package App\Domain\Agent\Events
 */
final class TaskFailed
{
    /**
     * @param string      $taskId
     * @param string      $agentId
     * @param string      $reason
     * @param DateTimeImmutable $failedAt
     */
    public function __construct(
        public readonly string $taskId,
        public readonly string $agentId,
        public readonly string $reason,
        public readonly DateTimeImmutable $failedAt
    ) {
    }
}
