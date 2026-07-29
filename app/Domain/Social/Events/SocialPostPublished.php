<?php

declare(strict_types=1);

namespace App\Domain\Social\Events;

use App\Domain\Social\Entities\SocialPost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SocialPostPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SocialPost $post,
    ) {}
}
