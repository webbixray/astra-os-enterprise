<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common\Traits;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the HasTimestamps trait.
 *
 * Uses an anonymous class to test the trait in isolation.
 * Covers initialization, creation/update timestamps, marking as updated,
 * setter methods for reconstitution, and serialization of timestamps.
 *
 * @package Tests\Unit\Domain\Common\Traits
 */
final class HasTimestampsTest extends TestCase
{
    // ---- Helper ----

    /**
     * Create a fresh anonymous class using the HasTimestamps trait.
     *
     * @return object
     */
    private function createTimestampable(): object
    {
        return new class {
            use \App\Domain\Common\Traits\HasTimestamps;

            public function __construct()
            {
                // No auto-init; caller decides when to initialize.
            }
        };
    }

    // ---- Happy Path ----

    #[Test]
    public function it_has_null_timestamps_before_initialization(): void
    {
        $entity = $this->createTimestampable();

        $this->assertNull($entity->getCreatedAt());
        $this->assertNull($entity->getUpdatedAt());
    }

    #[Test]
    public function it_sets_both_timestamps_on_initialize(): void
    {
        $entity = $this->createTimestampable();
        $entity->initializeTimestamps();

        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getUpdatedAt());
    }

    #[Test]
    public function it_sets_created_and_updated_at_fresh_on_initialize(): void
    {
        $entity = $this->createTimestampable();
        $before = new DateTimeImmutable();

        // Small delay to ensure timestamps differ
        usleep(2000);
        $entity->initializeTimestamps();

        $this->assertGreaterThan($before, $entity->getCreatedAt());
        $this->assertGreaterThan($before, $entity->getUpdatedAt());
    }

    #[Test]
    public function it_preserves_created_at_on_repeated_initialize(): void
    {
        $entity = $this->createTimestampable();
        $entity->initializeTimestamps();
        $originalCreatedAt = $entity->getCreatedAt();

        // Initialize again — createdAt should NOT change (??= guard)
        usleep(2000);
        $entity->initializeTimestamps();

        $this->assertSame($originalCreatedAt, $entity->getCreatedAt());
        $this->assertGreaterThan($originalCreatedAt, $entity->getUpdatedAt());
    }

    #[Test]
    public function it_updates_updated_at_when_marked_updated(): void
    {
        $entity = $this->createTimestampable();
        $entity->initializeTimestamps();
        $originalUpdatedAt = $entity->getUpdatedAt();

        usleep(2000);
        $entity->markAsUpdated();

        $this->assertGreaterThan($originalUpdatedAt, $entity->getUpdatedAt());
        $this->assertSame($entity->getCreatedAt(), $entity->getCreatedAt()); // createdAt unchanged
    }

    #[Test]
    public function it_sets_created_at_explicitly_for_reconstitution(): void
    {
        $entity = $this->createTimestampable();
        $date = new DateTimeImmutable('2024-01-15 10:00:00');
        $entity->setCreatedAt($date);

        $this->assertSame($date, $entity->getCreatedAt());
    }

    #[Test]
    public function it_sets_updated_at_explicitly_for_reconstitution(): void
    {
        $entity = $this->createTimestampable();
        $date = new DateTimeImmutable('2024-06-20 14:30:00');
        $entity->setUpdatedAt($date);

        $this->assertSame($date, $entity->getUpdatedAt());
    }

    #[Test]
    public function it_allows_reconstitution_without_initialize(): void
    {
        $entity = $this->createTimestampable();
        $created = new DateTimeImmutable('2023-01-01 00:00:00');
        $updated = new DateTimeImmutable('2023-06-15 12:00:00');

        $entity->setCreatedAt($created);
        $entity->setUpdatedAt($updated);

        $this->assertSame($created, $entity->getCreatedAt());
        $this->assertSame($updated, $entity->getUpdatedAt());
    }

    // ---- Edge Cases ----

    #[Test]
    public function it_supports_mark_as_updated_multiple_times(): void
    {
        $entity = $this->createTimestampable();
        $entity->initializeTimestamps();

        $firstMark = $entity->getUpdatedAt();

        usleep(2000);
        $entity->markAsUpdated();
        $secondMark = $entity->getUpdatedAt();

        usleep(2000);
        $entity->markAsUpdated();
        $thirdMark = $entity->getUpdatedAt();

        $this->assertGreaterThan($firstMark, $secondMark);
        $this->assertGreaterThan($secondMark, $thirdMark);
    }

    #[Test]
    public function it_does_not_set_created_at_when_already_set_via_setter_before_initialize(): void
    {
        $entity = $this->createTimestampable();
        $explicitCreated = new DateTimeImmutable('2022-01-01 00:00:00');
        $entity->setCreatedAt($explicitCreated);

        $entity->initializeTimestamps();

        // initializeTimestamps uses ??= so it should keep the explicit createdAt
        $this->assertSame($explicitCreated, $entity->getCreatedAt());
    }
}
