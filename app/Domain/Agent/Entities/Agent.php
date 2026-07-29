<?php

declare(strict_types=1);

namespace App\Domain\Agent\Entities;

use DateTimeImmutable;

final class Agent
{
    private ?int $id = null;
    private string $name;
    private string $role;
    private string $description;
    private string $model;
    private array $capabilities;
    private array $configuration;
    private string $status;
    private int $organizationId;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $name,
        string $role,
        string $description,
        string $model,
        int $organizationId,
        array $capabilities = [],
        array $configuration = [],
    ) {
        $this->name = $name;
        $this->role = $role;
        $this->description = $description;
        $this->model = $model;
        $this->organizationId = $organizationId;
        $this->capabilities = $capabilities;
        $this->configuration = $configuration;
        $this->status = 'idle';
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string { return $this->name; }
    public function getRole(): string { return $this->role; }
    public function getDescription(): string { return $this->description; }
    public function getModel(): string { return $this->model; }
    public function getCapabilities(): array { return $this->capabilities; }
    public function getConfiguration(): array { return $this->configuration; }
    public function getStatus(): string { return $this->status; }
    public function getOrganizationId(): int { return $this->organizationId; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
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
