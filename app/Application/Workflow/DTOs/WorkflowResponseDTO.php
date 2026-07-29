<?php

declare(strict_types=1);

namespace App\Application\Workflow\DTOs;

use DateTimeImmutable;

final readonly class WorkflowResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $description,
        public array $nodes,
        public array $edges,
        public array $triggers,
        public array $variables,
        public string $status,
        public int $organizationId,
        public int $version,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            description: $data['description'],
            nodes: $data['nodes'] ?? [],
            edges: $data['edges'] ?? [],
            triggers: $data['triggers'] ?? [],
            variables: $data['variables'] ?? [],
            status: $data['status'],
            organizationId: $data['organization_id'],
            version: $data['version'] ?? 1,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'nodes' => $this->nodes,
            'edges' => $this->edges,
            'triggers' => $this->triggers,
            'variables' => $this->variables,
            'status' => $this->status,
            'organization_id' => $this->organizationId,
            'version' => $this->version,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
