<?php

declare(strict_types=1);

namespace App\Domain\Common\Contracts;

/**
 * Interface AggregateRoot
 *
 * Defines the contract for aggregate root entities in the domain layer.
 * Aggregate roots are consistency boundaries that ensure transactional
 * integrity and are the only objects that can be loaded from and saved
 * to repositories. They are responsible for recording and retrieving
 * domain events that occur within the aggregate boundary.
 *
 * @package App\Domain\Common\Contracts
 */
interface AggregateRoot
{
    /**
     * Retrieve all recorded domain events that have occurred within this aggregate.
     *
     * Domain events represent state changes that have business significance
     * and should be dispatched to interested subscribers for side-effect processing.
     *
     * @return array<int, object> An array of domain event objects.
     */
    public function getDomainEvents(): array;

    /**
     * Clear all recorded domain events from the aggregate.
     *
     * This is typically called after the events have been dispatched and
     * processed by the event bus or message queue. After clearing, subsequent
     * calls to getDomainEvents() should return an empty array until new events
     * are recorded.
     *
     * @return void
     */
    public function clearDomainEvents(): void;

    /**
     * Record a new domain event within this aggregate.
     *
     * The event is stored internally and can be retrieved via getDomainEvents().
     * Events should be recorded only when the aggregate's state has been
     * successfully modified, ensuring eventual consistency across bounded contexts.
     *
     * @param object $event The domain event to record.
     * @return void
     */
    public function recordDomainEvent(object $event): void;
}
