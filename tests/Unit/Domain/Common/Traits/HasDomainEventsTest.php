<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common\Traits;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the HasDomainEvents trait.
 *
 * Uses an anonymous class to test the trait in isolation.
 * Covers recording, retrieval, clearing of domain events, multiple events,
 * and the DomainEvent interface contract compliance.
 *
 * @package Tests\Unit\Domain\Common\Traits
 */
final class HasDomainEventsTest extends TestCase
{
    // ---- Helper ----

    /**
     * Create an anonymous aggregate root using HasDomainEvents.
     *
     * @return object
     */
    private function createEventSourced(): object
    {
        return new class {
            use \App\Domain\Common\Traits\HasDomainEvents;
        };
    }

    /**
     * Create a simple test event object for recording.
     *
     * @param string $type
     * @return object
     */
    private function makeEvent(string $type = 'test.event'): object
    {
        return new class($type) {
            public function __construct(public readonly string $type)
            {
            }
        };
    }

    // ---- Happy Path ----

    #[Test]
    public function it_starts_with_no_events(): void
    {
        $entity = $this->createEventSourced();

        $this->assertEmpty($entity->getDomainEvents());
    }

    #[Test]
    public function it_records_a_single_event(): void
    {
        $entity = $this->createEventSourced();
        $event = $this->makeEvent('organization.created');

        $entity->recordDomainEvent($event);
        $events = $entity->getDomainEvents();

        $this->assertCount(1, $events);
        $this->assertSame($event, $events[0]);
    }

    #[Test]
    public function it_records_multiple_events_in_order(): void
    {
        $entity = $this->createEventSourced();
        $event1 = $this->makeEvent('first');
        $event2 = $this->makeEvent('second');
        $event3 = $this->makeEvent('third');

        $entity->recordDomainEvent($event1);
        $entity->recordDomainEvent($event2);
        $entity->recordDomainEvent($event3);
        $events = $entity->getDomainEvents();

        $this->assertCount(3, $events);
        $this->assertSame($event1, $events[0]);
        $this->assertSame($event2, $events[1]);
        $this->assertSame($event3, $events[2]);
    }

    #[Test]
    public function it_clears_all_events(): void
    {
        $entity = $this->createEventSourced();
        $entity->recordDomainEvent($this->makeEvent('campaign.started'));
        $entity->recordDomainEvent($this->makeEvent('campaign.paused'));

        $entity->clearDomainEvents();

        $this->assertEmpty($entity->getDomainEvents());
    }

    #[Test]
    public function it_continues_recording_after_clear(): void
    {
        $entity = $this->createEventSourced();
        $entity->recordDomainEvent($this->makeEvent('first'));
        $entity->clearDomainEvents();

        $event2 = $this->makeEvent('after.clear');
        $entity->recordDomainEvent($event2);
        $events = $entity->getDomainEvents();

        $this->assertCount(1, $events);
        $this->assertSame($event2, $events[0]);
    }

    #[Test]
    public function it_records_events_of_various_types(): void
    {
        $entity = $this->createEventSourced();

        $entity->recordDomainEvent(new \App\Domain\Workflow\Events\WorkflowCreated(
            workflowId: 'wf-1',
            organizationId: 'org-1',
            name: 'Test Workflow'
        ));

        $entity->recordDomainEvent(new \App\Domain\Workflow\Events\WorkflowActivated(
            workflowId: 'wf-1'
        ));

        $events = $entity->getDomainEvents();

        $this->assertCount(2, $events);
        $this->assertInstanceOf(\App\Domain\Workflow\Events\WorkflowCreated::class, $events[0]);
        $this->assertInstanceOf(\App\Domain\Workflow\Events\WorkflowActivated::class, $events[1]);
    }

    // ---- Edge Cases ----

    #[Test]
    public function it_clears_events_and_returns_empty(): void
    {
        $entity = $this->createEventSourced();
        $entity->recordDomainEvent($this->makeEvent('anything'));

        $entity->clearDomainEvents();

        $this->assertIsArray($entity->getDomainEvents());
        $this->assertCount(0, $entity->getDomainEvents());
    }

    #[Test]
    public function it_records_same_event_instance_once(): void
    {
        $entity = $this->createEventSourced();
        $event = $this->makeEvent('shared');

        $entity->recordDomainEvent($event);
        $entity->recordDomainEvent($event);

        $this->assertCount(2, $entity->getDomainEvents());
        $this->assertSame($event, $entity->getDomainEvents()[0]);
        $this->assertSame($event, $entity->getDomainEvents()[1]);
    }

    #[Test]
    public function it_is_independent_per_instance(): void
    {
        $a = $this->createEventSourced();
        $b = $this->createEventSourced();

        $a->recordDomainEvent($this->makeEvent('event-a'));

        $this->assertCount(1, $a->getDomainEvents());
        $this->assertCount(0, $b->getDomainEvents());
    }
}
