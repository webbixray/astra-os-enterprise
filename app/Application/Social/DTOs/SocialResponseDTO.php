<?php

declare(strict_types=1);

namespace App\Application\Social\DTOs;

use DateTimeImmutable;

final readonly class SocialResponseDTO
{
    public function __construct(
        public int $id,
        public int $organizationId,
        public int $socialAccountId,
        public string $content,
        public ?string $mediaUrl,
        public array $platforms,
        public string $status,
        public ?DateTimeImmutable $scheduledAt,
        public ?DateTimeImmutable $publishedAt,
        public array $analytics,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            organizationId: $data['organization_id'],
            socialAccountId: $data['social_account_id'],
            content: $data['content'],
            mediaUrl: $data['media_url'],
            platforms: $data['platforms'],
            status: $data['status'],
            scheduledAt: isset($data['scheduled_at']) ? new DateTimeImmutable($data['scheduled_at']) : null,
            publishedAt: isset($data['published_at']) ? new DateTimeImmutable($data['published_at']) : null,
            analytics: $data['analytics'] ?? [],
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organizationId,
            'social_account_id' => $this->socialAccountId,
            'content' => $this->content,
            'media_url' => $this->mediaUrl,
            'platforms' => $this->platforms,
            'status' => $this->status,
            'scheduled_at' => $this->scheduledAt?->format('Y-m-d H:i:s'),
            'published_at' => $this->publishedAt?->format('Y-m-d H:i:s'),
            'analytics' => $this->analytics,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
