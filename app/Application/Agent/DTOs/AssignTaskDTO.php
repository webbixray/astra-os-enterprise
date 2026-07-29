<?php

declare(strict_types=1);

namespace App\Application\Agent\DTOs;

final readonly class AssignTaskDTO
{
    public function __construct(
        public int $agentId,
        public string $type,
        public array $input,
        public ?int $parentTaskId = null,
    ) {}
}
