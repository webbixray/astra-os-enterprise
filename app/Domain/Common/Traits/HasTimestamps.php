<?php

declare(strict_types=1);

namespace App\Domain\Common\Traits;

use DateTimeImmutable;

/**
 * Trait HasTimestamps
 *
 * Provides timestamp management for entities that require creation and
 * modification tracking. Designed to be mixed into domain entities that
 * need auditable time fields.
 *
 * The createdAt timestamp is set once upon initialization and remains
 * immutable. The updatedAt timestamp is refreshed on every state-modifying
 * operation to reflect the last point of change.
 *
 * @package App\Domain\Common\Traits
 */
trait HasTimestamps
{
    /**
     * The date and time when the entity was originally created.
     *
     * @var DateTimeImmutable|null
     */
    protected ?DateTimeImmutable $createdAt = null;

    /**
     * The date and time of the last modification to the entity.
     *
     * @var DateTimeImmutable|null
     */
    protected ?DateTimeImmutable $updatedAt = null;

    /**
     * Initialize the timestamps for a new entity.
     *
     * Sets both createdAt and updatedAt to the current point in time.
     * Should be called from the entity's constructor.
     *
     * @return void
     */
    public function initializeTimestamps(): void
    {
        $now = new DateTimeImmutable();
        $this->createdAt ??= $now;
        $this->updatedAt = $now;
    }

    /**
     * Mark the entity as having been updated.
     *
     * Refreshes the updatedAt timestamp to the current time.
     *
     * @return void
     */
    public function markAsUpdated(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Get the creation timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Get the last modification timestamp.
     *
     * @return DateTimeImmutable|null
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Set the created-at timestamp explicitly (used during reconstitution).
     *
     * @param DateTimeImmutable $createdAt
     * @return void
     */
    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Set the updated-at timestamp explicitly (used during reconstitution).
     *
     * @param DateTimeImmutable $updatedAt
     * @return void
     */
    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
