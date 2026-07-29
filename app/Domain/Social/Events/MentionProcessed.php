<?php

declare(strict_types=1);

namespace App\Domain\Social\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MentionProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $mentionId,
        public readonly string $platform,
        public readonly string $response,
    ) {}
}
