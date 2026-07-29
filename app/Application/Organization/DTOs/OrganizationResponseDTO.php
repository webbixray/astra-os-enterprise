<?php

declare(strict_types=1);

namespace App\Application\Organization\DTOs;

use DateTimeImmutable;

final readonly class OrganizationResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $logo,
        public ?string $website,
        public array $settings,
        public int $ownerId,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'],
            logo: $data['logo'],
            website: $data['website'],
            settings: $data['settings'] ?? [],
            ownerId: $data['owner_id'],
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo' => $this->logo,
            'website' => $this->website,
            'settings' => $this->settings,
            'owner_id' => $this->ownerId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
