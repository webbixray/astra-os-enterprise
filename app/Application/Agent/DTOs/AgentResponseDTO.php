<?php

declare(strict_types=1);

namespace App\Application\Agent\DTOs;

use DateTimeImmutable;

final readonly class AgentResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $role,
        public string $description,
        public string $model,
        public array $capabilities,
        public array $configuration,
        public string $status,
        public int $organizationId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            role: $data['role'],
            description: $data['description'],
            model: $data['model'],
            capabilities: $data['capabilities'] ?? [],
            configuration: $data['configuration'] ?? [],
            status: $data['status'],
            organizationId: $data['organization_id'],
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $this->role,
            'description' => $this->description,
            'model' => $this->model,
            'capabilities' => $this->capabilities,
            'configuration' => $this->configuration,
            'status' => $this->status,
            'organization_id' => $this->organizationId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
