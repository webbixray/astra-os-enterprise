<?php

declare(strict_types=1);

namespace App\Application\Agent\DTOs;

final readonly class CreateAgentDTO
{
    public function __construct(
        public string $name,
        public string $role,
        public string $description,
        public string $model,
        public int $organizationId,
        public array $capabilities = [],
        public array $configuration = [],
    ) {}
}
