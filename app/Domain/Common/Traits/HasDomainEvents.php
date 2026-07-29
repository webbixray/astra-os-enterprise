<?php

declare(strict_types=1);

namespace App\Domain\Common\Traits;

/**
 * Trait HasDomainEvents
 *
 * Provides domain event recording capabilities for aggregate roots.
 * Implements the event-sourcing contract, allowing entities to record,
 * retrieve, and clear domain events that occur during business operations.
 *
 * @package App\Domain\Common\Traits
 */
trait HasDomainEvents
{
    /**
     * Internal collection of recorded domain events.
     *
     * @var array<int, object>
     */
    private array $domainEvents = [];

    /**
     * Retrieve all recorded domain events.
     *
     * @return array<int, object>
     */
    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }

    /**
     * Clear all recorded domain events.
     *
     * @return void
     */
    public function clearDomainEvents(): void
    {
        $this->domainEvents = [];
    }

    /**
     * Record a new domain event within this aggregate.
     *
     * @param object $event The domain event to record.
     * @return void
     */
    public function recordDomainEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
