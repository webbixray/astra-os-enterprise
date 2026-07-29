<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Events;

use App\Domain\Campaign\Entities\Campaign;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class CampaignArchived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Campaign $campaign,
    ) {}
}
