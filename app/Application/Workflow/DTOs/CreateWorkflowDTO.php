<?php

declare(strict_types=1);

namespace App\Application\Workflow\DTOs;

final readonly class CreateWorkflowDTO
{
    public function __construct(
        public string $name,
        public string $description,
        public array $nodes,
        public array $edges,
        public int $organizationId,
        public array $triggers = [],
        public array $variables = [],
    ) {}
}
