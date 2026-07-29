<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Events;

use App\Domain\Workflow\Entities\Workflow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class WorkflowDeactivated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Workflow $workflow,
    ) {}
}
