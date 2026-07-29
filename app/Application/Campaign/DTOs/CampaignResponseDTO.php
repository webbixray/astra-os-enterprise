<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

use DateTimeImmutable;

final readonly class CampaignResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $objective,
        public array $budget,
        public array $targetAudience,
        public array $platforms,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public string $status,
        public array $metadata,
        public int $organizationId,
        public ?int $createdBy,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $launchedAt = null,
        public ?DateTimeImmutable $pausedAt = null,
        public ?DateTimeImmutable $archivedAt = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            objective: $data['objective'],
            budget: $data['budget'],
            targetAudience: $data['target_audience'],
            platforms: $data['platforms'],
            startDate: new DateTimeImmutable($data['start_date']),
            endDate: new DateTimeImmutable($data['end_date']),
            status: $data['status'],
            metadata: $data['metadata'] ?? [],
            organizationId: $data['organization_id'],
            createdBy: $data['created_by'],
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
            launchedAt: isset($data['launched_at']) ? new DateTimeImmutable($data['launched_at']) : null,
            pausedAt: isset($data['paused_at']) ? new DateTimeImmutable($data['paused_at']) : null,
            archivedAt: isset($data['archived_at']) ? new DateTimeImmutable($data['archived_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'objective' => $this->objective,
            'budget' => $this->budget,
            'target_audience' => $this->targetAudience,
            'platforms' => $this->platforms,
            'start_date' => $this->startDate->format('Y-m-d H:i:s'),
            'end_date' => $this->endDate->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'metadata' => $this->metadata,
            'organization_id' => $this->organizationId,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'launched_at' => $this->launchedAt?->format('Y-m-d H:i:s'),
            'paused_at' => $this->pausedAt?->format('Y-m-d H:i:s'),
            'archived_at' => $this->archivedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
