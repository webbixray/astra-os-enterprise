<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

final readonly class CreateOrganizationDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public int $ownerId,
        public ?string $description = null,
        public ?string $logo = null,
        public ?string $website = null,
        public array $settings = [],
    ) {}
}
