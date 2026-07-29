<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common\ValueObjects;

use App\Domain\Common\ValueObjects\Email;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Email value object.
 *
 * Covers creation, validation, normalization, equality, string conversion,
 * domain/local-part extraction, and edge cases (empty, invalid formats).
 *
 * @package Tests\Unit\Domain\Common\ValueObjects
 */
final class EmailTest extends TestCase
{
    // ---- Happy Path ----

    #[Test]
    public function it_creates_a_valid_email(): void
    {
        $email = new Email('user@example.com');

        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame('user@example.com', $email->getValue());
    }

    #[Test]
    public function it_normalizes_to_lowercase(): void
    {
        $email = new Email('User@Example.COM');

        $this->assertSame('user@example.com', $email->getValue());
    }

    #[Test]
    public function it_trims_whitespace(): void
    {
        $email = new Email('  user@example.com  ');

        $this->assertSame('user@example.com', $email->getValue());
    }

    #[Test]
    public function it_returns_value_via_toString(): void
    {
        $email = new Email('user@example.com');

        $this->assertSame('user@example.com', $email->toString());
    }

    #[Test]
    public function it_returns_value_via_magic_toString(): void
    {
        $email = new Email('user@example.com');

        $this->assertSame('user@example.com', (string) $email);
    }

    #[Test]
    public function it_extracts_local_part(): void
    {
        $email = new Email('john.doe@example.com');

        $this->assertSame('john.doe', $email->getLocalPart());
    }

    #[Test]
    public function it_extracts_domain(): void
    {
        $email = new Email('john.doe@example.co.uk');

        $this->assertSame('example.co.uk', $email->getDomain());
    }

    #[Test]
    public function it_detects_equality(): void
    {
        $a = new Email('user@example.com');
        $b = new Email('user@example.com');

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function it_detects_inequality(): void
    {
        $a = new Email('user@example.com');
        $b = new Email('other@example.com');

        $this->assertFalse($a->equals($b));
    }

    #[Test]
    public function it_considers_case_insensitively_equal(): void
    {
        $a = new Email('User@Example.COM');
        $b = new Email('user@example.com');

        $this->assertTrue($a->equals($b));
    }

    // ---- Edge Cases & Exceptions ----

    #[Test]
    public function it_throws_for_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email');

        new Email('');
    }

    #[Test]
    public function it_throws_for_missing_at_symbol(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('userexample.com');
    }

    #[Test]
    public function it_throws_for_missing_domain(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('user@');
    }

    #[Test]
    public function it_throws_for_missing_local_part(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('@example.com');
    }

    #[Test]
    public function it_throws_for_domain_without_dot(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('user@example');
    }

    #[Test]
    public function it_throws_for_spaces_in_middle(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('user @example.com');
    }

    #[Test]
    public function it_throws_for_only_whitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('   ');
    }
}
