<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Entities;

use App\Domain\Common\ValueObjects\Money;
use DateTimeImmutable;

final class Campaign
{
    private ?int $id = null;
    private string $name;
    private string $objective;
    private Money $budget;
    private array $targetAudience;
    private array $platforms;
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;
    private string $status;
    private array $metadata;
    private int $organizationId;
    private ?int $createdBy;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $launchedAt = null;
    private ?DateTimeImmutable $pausedAt = null;
    private ?DateTimeImmutable $archivedAt = null;

    public function __construct(
        string $name,
        string $objective,
        Money $budget,
        array $targetAudience,
        array $platforms,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        int $organizationId,
        ?int $createdBy = null,
        array $metadata = [],
    ) {
        $this->name = $name;
        $this->objective = $objective;
        $this->budget = $budget;
        $this->targetAudience = $targetAudience;
        $this->platforms = $platforms;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->organizationId = $organizationId;
        $this->createdBy = $createdBy;
        $this->metadata = $metadata;
        $this->status = 'draft';
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getObjective(): string
    {
        return $this->objective;
    }

    public function getBudget(): Money
    {
        return $this->budget;
    }

    public function getTargetAudience(): array
    {
        return $this->targetAudience;
    }

    public function getPlatforms(): array
    {
        return $this->platforms;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getOrganizationId(): int
    {
        return $this->organizationId;
    }

    public function getCreatedBy(): ?int
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getLaunchedAt(): ?DateTimeImmutable
    {
        return $this->launchedAt;
    }

    public function getPausedAt(): ?DateTimeImmutable
    {
        return $this->pausedAt;
    }

    public function getArchivedAt(): ?DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function launch(): void
    {
        if ($this->status !== 'draft' && $this->status !== 'paused') {
            throw new \RuntimeException('Only draft or paused campaigns can be launched.');
        }

        if ($this->startDate > new DateTimeImmutable()) {
            $this->status = 'scheduled';
        } else {
            $this->status = 'active';
        }

        $this->launchedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function pause(): void
    {
        if ($this->status !== 'active' && $this->status !== 'scheduled') {
            throw new \RuntimeException('Only active or scheduled campaigns can be paused.');
        }

        $this->status = 'paused';
        $this->pausedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function archive(): void
    {
        if ($this->status === 'archived') {
            throw new \RuntimeException('Campaign is already archived.');
        }

        if ($this->status === 'active') {
            $this->pause();
        }

        $this->status = 'archived';
        $this->archivedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateBudget(Money $budget): void
    {
        $this->budget = $budget;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'objective' => $this->objective,
            'budget' => $this->budget->toArray(),
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
