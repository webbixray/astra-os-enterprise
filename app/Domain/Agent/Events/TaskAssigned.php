<?php

declare(strict_types=1);

namespace App\Domain\Agent\Events;

use App\Domain\Agent\Entities\AgentTask;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TaskAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AgentTask $task,
    ) {}
}
