<?php

declare(strict_types=1);

namespace App\Application\Social\DTOs;

use DateTimeImmutable;

final readonly class CreatePostDTO
{
    public function __construct(
        public int $organizationId,
        public int $socialAccountId,
        public string $content,
        public array $platforms,
        public ?string $mediaUrl = null,
        public ?DateTimeImmutable $scheduledAt = null,
    ) {}
}
