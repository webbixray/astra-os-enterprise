<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common\ValueObjects;

use App\Domain\Common\ValueObjects\Address;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Address value object.
 *
 * Covers creation with all fields, partial address construction,
 * equality comparisons, string formatting, array serialization,
 * and edge cases including empty strings and boundary values.
 *
 * @package Tests\Unit\Domain\Common\ValueObjects
 */
final class AddressTest extends TestCase
{
    // ---- Happy Path ----

    #[Test]
    public function it_creates_a_full_address(): void
    {
        $address = new Address(
            street: '123 Main St',
            city: 'San Francisco',
            state: 'CA',
            postalCode: '94105',
            country: 'US',
        );

        $this->assertSame('123 Main St', $address->getStreet());
        $this->assertSame('San Francisco', $address->getCity());
        $this->assertSame('CA', $address->getState());
        $this->assertSame('94105', $address->getPostalCode());
        $this->assertSame('US', $address->getCountry());
    }

    #[Test]
    public function it_accepts_long_country_name(): void
    {
        $address = new Address('1 High St', 'London', 'Greater London', 'EC1A 1BB', 'United Kingdom');

        $this->assertSame('United Kingdom', $address->getCountry());
    }

    #[Test]
    public function it_accepts_two_character_country_code(): void
    {
        $address = new Address('1 Main St', 'New York', 'NY', '10001', 'US');

        $this->assertSame('US', $address->getCountry());
    }

    #[Test]
    public function it_accepts_empty_optional_fields(): void
    {
        $address = new Address(
            street: '',
            city: '',
            state: '',
            postalCode: '',
            country: 'US',
        );

        $this->assertSame('', $address->getStreet());
        $this->assertSame('US', $address->getCountry());
    }

    #[Test]
    public function it_serializes_to_array(): void
    {
        $address = new Address('456 Oak Ave', 'Austin', 'TX', '73301', 'US');
        $array = $address->toArray();

        $this->assertSame([
            'street' => '456 Oak Ave',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '73301',
            'country' => 'US',
        ], $array);
    }

    #[Test]
    public function it_formats_as_string(): void
    {
        $address = new Address('123 Main St', 'San Francisco', 'CA', '94105', 'US');
        $formatted = (string) $address;

        $this->assertSame('123 Main St, San Francisco, CA 94105, US', $formatted);
    }

    #[Test]
    public function it_handles_partial_address_string(): void
    {
        $address = new Address('', 'New York', 'NY', '', 'US');
        $formatted = (string) $address;

        $this->assertSame(', New York, NY , US', $formatted);
    }

    // ---- Equality ----

    #[Test]
    public function it_detects_equality(): void
    {
        $a = new Address('1 Main St', 'NYC', 'NY', '10001', 'US');
        $b = new Address('1 Main St', 'NYC', 'NY', '10001', 'US');

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function it_detects_inequality_different_street(): void
    {
        $a = new Address('1 Main St', 'NYC', 'NY', '10001', 'US');
        $b = new Address('2 Main St', 'NYC', 'NY', '10001', 'US');

        $this->assertFalse($a->equals($b));
    }

    #[Test]
    public function it_detects_inequality_different_country(): void
    {
        $a = new Address('1 Main St', 'NYC', 'NY', '10001', 'US');
        $b = new Address('1 Main St', 'NYC', 'NY', '10001', 'CA');

        $this->assertFalse($a->equals($b));
    }

    // ---- Edge Cases ----

    #[Test]
    public function it_accepts_very_long_values(): void
    {
        $longStreet = str_repeat('A', 500);
        $address = new Address($longStreet, 'City', 'ST', '12345', 'XY');

        $this->assertSame($longStreet, $address->getStreet());
    }

    #[Test]
    public function it_accepts_special_characters_in_fields(): void
    {
        $address = new Address(
            'Calle 123 #4-56',
            'São Paulo',
            'SP',
            '01001-000',
            'BR',
        );

        $this->assertSame('Calle 123 #4-56', $address->getStreet());
        $this->assertSame('São Paulo', $address->getCity());
    }

    #[Test]
    public function it_equals_self(): void
    {
        $address = new Address('1 Main St', 'NYC', 'NY', '10001', 'US');

        $this->assertTrue($address->equals($address));
    }
}
