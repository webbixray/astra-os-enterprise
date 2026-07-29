<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Organization;

use App\Domain\Organization\Entities\Organization;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Organization domain entity.
 *
 * Covers entity creation, name/slug assignment, settings updates,
 * serialization round-trip, and edge cases (empty name, boundary values).
 * Note: The Organization entity is a simple data entity without
 * domain events or built-in member management (members are managed
 * via the OrganizationMember entity).
 *
 * @package Tests\Unit\Domain\Organization
 */
final class OrganizationTest extends TestCase
{
    // ---- Happy Path ----

    #[Test]
    public function it_creates_an_organization_with_required_fields(): void
    {
        $org = new Organization(
            name: 'Astra Corp',
            slug: 'astra-corp',
            ownerId: 1,
        );

        $this->assertInstanceOf(Organization::class, $org);
        $this->assertSame('Astra Corp', $org->getName());
        $this->assertSame('astra-corp', $org->getSlug());
        $this->assertSame(1, $org->getOwnerId());
        $this->assertNull($org->getId());
    }

    #[Test]
    public function it_creates_organization_with_all_optional_fields(): void
    {
        $org = new Organization(
            name: 'Full Org',
            slug: 'full-org',
            ownerId: 42,
            description: 'A full organization',
            logo: 'https://example.com/logo.png',
            website: 'https://example.com',
            settings: ['timezone' => 'UTC', 'locale' => 'en_US'],
        );

        $this->assertSame('Full Org', $org->getName());
        $this->assertSame('full-org', $org->getSlug());
        $this->assertSame(42, $org->getOwnerId());
        $this->assertSame('A full organization', $org->getDescription());
        $this->assertSame('https://example.com/logo.png', $org->getLogo());
        $this->assertSame('https://example.com', $org->getWebsite());
        $this->assertSame(['timezone' => 'UTC', 'locale' => 'en_US'], $org->getSettings());
    }

    #[Test]
    public function it_accepts_null_optional_fields_by_default(): void
    {
        $org = new Organization('Minimal', 'minimal', 1);

        $this->assertNull($org->getDescription());
        $this->assertNull($org->getLogo());
        $this->assertNull($org->getWebsite());
        $this->assertSame([], $org->getSettings());
    }

    #[Test]
    public function it_sets_and_gets_id(): void
    {
        $org = new Organization('Test', 'test', 1);
        $org->setId(99);

        $this->assertSame(99, $org->getId());
    }

    #[Test]
    public function it_has_timestamps_on_creation(): void
    {
        $org = new Organization('TS', 'ts', 1);

        $this->assertNotNull($org->getCreatedAt());
        $this->assertNotNull($org->getUpdatedAt());
    }

    // ---- Settings ----

    #[Test]
    public function it_updates_settings_with_merge(): void
    {
        $org = new Organization(
            name: 'Org',
            slug: 'org',
            ownerId: 1,
            settings: ['timezone' => 'UTC'],
        );

        $org->updateSettings(['locale' => 'en_US']);

        $this->assertSame([
            'timezone' => 'UTC',
            'locale' => 'en_US',
        ], $org->getSettings());
    }

    #[Test]
    public function it_overwrites_existing_setting_key(): void
    {
        $org = new Organization(
            name: 'Org',
            slug: 'org',
            ownerId: 1,
            settings: ['timezone' => 'UTC', 'locale' => 'fr_FR'],
        );

        $org->updateSettings(['locale' => 'de_DE']);

        $this->assertSame([
            'timezone' => 'UTC',
            'locale' => 'de_DE',
        ], $org->getSettings());
    }

    #[Test]
    public function it_updates_timestamp_when_settings_change(): void
    {
        $org = new Organization('Org', 'org', 1);
        $originalUpdatedAt = $org->getUpdatedAt();

        usleep(2000);
        $org->updateSettings(['new' => 'value']);

        $this->assertGreaterThan($originalUpdatedAt, $org->getUpdatedAt());
    }

    // ---- Serialization ----

    #[Test]
    public function it_serializes_to_array_with_all_fields(): void
    {
        $org = new Organization(
            name: 'My Org',
            slug: 'my-org',
            ownerId: 7,
            description: 'Desc',
            logo: 'logo.png',
            website: 'https://my.org',
            settings: ['key' => 'val'],
        );
        $org->setId(5);
        $array = $org->toArray();

        $this->assertSame(5, $array['id']);
        $this->assertSame('My Org', $array['name']);
        $this->assertSame('my-org', $array['slug']);
        $this->assertSame('Desc', $array['description']);
        $this->assertSame('logo.png', $array['logo']);
        $this->assertSame('https://my.org', $array['website']);
        $this->assertSame(['key' => 'val'], $array['settings']);
        $this->assertSame(7, $array['owner_id']);
        $this->assertIsString($array['created_at']);
        $this->assertIsString($array['updated_at']);
    }

    #[Test]
    public function it_serializes_with_null_id_before_set(): void
    {
        $org = new Organization('New', 'new', 1);
        $array = $org->toArray();

        $this->assertNull($array['id']);
    }

    // ---- Edge Cases ----

    #[Test]
    public function it_accepts_empty_name(): void
    {
        // The domain entity does not validate name emptiness — test the boundary
        $org = new Organization('', 'empty-name', 1);

        $this->assertSame('', $org->getName());
    }

    #[Test]
    public function it_accepts_slug_with_special_characters(): void
    {
        $org = new Organization('Special', 'special_slug-1.0', 1);

        $this->assertSame('special_slug-1.0', $org->getSlug());
    }

    #[Test]
    public function it_accepts_very_long_name(): void
    {
        $longName = str_repeat('A', 1000);
        $org = new Organization($longName, 'long-name', 1);

        $this->assertSame($longName, $org->getName());
    }

    #[Test]
    public function it_accepts_zero_owner_id(): void
    {
        $org = new Organization('Zero', 'zero', 0);

        $this->assertSame(0, $org->getOwnerId());
    }

    #[Test]
    public function it_accepts_empty_settings(): void
    {
        $org = new Organization('Empty', 'empty', 1, settings: []);

        $this->assertSame([], $org->getSettings());
    }

    #[Test]
    public function it_replaces_timestamp_on_update_settings(): void
    {
        $org = new Organization('Name', 'name', 1);
        $originalCreatedAt = $org->getCreatedAt();

        usleep(2000);
        $org->updateSettings(['foo' => 'bar']);

        // createdAt must remain unchanged
        $this->assertSame($originalCreatedAt, $org->getCreatedAt());
        // updatedAt must advance
        $this->assertGreaterThan($originalCreatedAt, $org->getUpdatedAt());
    }
}
