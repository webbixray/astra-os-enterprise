<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Events;

use App\Domain\Workflow\Entities\Workflow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WorkflowExecuted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Workflow $workflow,
        public readonly array $results,
    ) {}
}
