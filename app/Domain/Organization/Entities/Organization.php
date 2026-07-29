<?php

declare(strict_types=1);

namespace App\Domain\Organization\Entities;

use DateTimeImmutable;

final class Organization
{
    private ?int $id = null;
    private string $name;
    private string $slug;
    private ?string $description = null;
    private ?string $logo = null;
    private ?string $website = null;
    private array $settings;
    private int $ownerId;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct(
        string $name,
        string $slug,
        int $ownerId,
        ?string $description = null,
        ?string $logo = null,
        ?string $website = null,
        array $settings = [],
    ) {
        $this->name = $name;
        $this->slug = $slug;
        $this->ownerId = $ownerId;
        $this->description = $description;
        $this->logo = $logo;
        $this->website = $website;
        $this->settings = $settings;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateSettings(array $settings): void
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->updatedAt = new DateTimeImmutable();
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
