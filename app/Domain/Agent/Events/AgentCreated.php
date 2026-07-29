<?php

declare(strict_types=1);

namespace App\Domain\Agent\Events;

use App\Domain\Agent\Entities\Agent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AgentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Agent $agent,
    ) {}
}
