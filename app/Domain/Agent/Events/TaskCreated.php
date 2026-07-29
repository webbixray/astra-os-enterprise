<?php

declare(strict_types=1);

namespace App\Domain\Agent\Events;

use DateTimeImmutable;

/**
 * Domain Event: TaskCreated
 *
 * Fired when a new task is created and assigned to an agent.
 *
 * @package App\Domain\Agent\Events
 */
final class TaskCreated
{
    /**
     * @param string      $taskId
     * @param string      $agentId
     * @param string|null $campaignId
     * @param string      $type
     * @param DateTimeImmutable $createdAt
     */
    public function __construct(
        public readonly string $taskId,
        public readonly string $agentId,
        public readonly ?string $campaignId,
        public readonly string $type,
        public readonly DateTimeImmutable $createdAt
    ) {
    }
}
