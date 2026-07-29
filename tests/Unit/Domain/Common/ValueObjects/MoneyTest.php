<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Common\ValueObjects;

use App\Domain\Common\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Money value object.
 *
 * Covers creation with valid and edge inputs, arithmetic operations
 * (add, subtract, multiply), comparison, currency mismatch protection,
 * immutability, formatting, and serialization.
 *
 * @package Tests\Unit\Domain\Common\ValueObjects
 */
final class MoneyTest extends TestCase
{
    // ---- Happy Path ----

    #[Test]
    public function it_creates_money_with_default_currency(): void
    {
        $money = new Money(100.0);

        $this->assertSame(100.0, $money->getAmount());
        $this->assertSame('USD', $money->getCurrency());
    }

    #[Test]
    public function it_creates_money_with_custom_currency(): void
    {
        $money = new Money(250.50, 'EUR');

        $this->assertSame(250.50, $money->getAmount());
        $this->assertSame('EUR', $money->getCurrency());
    }

    #[Test]
    public function it_creates_zero_amount(): void
    {
        $money = new Money(0.0);

        $this->assertSame(0.0, $money->getAmount());
    }

    #[Test]
    public function it_adds_same_currency(): void
    {
        $a = new Money(100.0, 'USD');
        $b = new Money(50.0, 'USD');
        $result = $a->add($b);

        $this->assertSame(150.0, $result->getAmount());
        $this->assertSame('USD', $result->getCurrency());
    }

    #[Test]
    public function it_subtracts_same_currency(): void
    {
        $a = new Money(100.0, 'USD');
        $b = new Money(30.0, 'USD');
        $result = $a->subtract($b);

        $this->assertSame(70.0, $result->getAmount());
        $this->assertSame('USD', $result->getCurrency());
    }

    #[Test]
    public function it_multiplies(): void
    {
        $money = new Money(100.0, 'USD');
        $result = $money->multiply(2.5);

        $this->assertSame(250.0, $result->getAmount());
        $this->assertSame('USD', $result->getCurrency());
    }

    #[Test]
    public function it_multiplies_by_zero(): void
    {
        $money = new Money(100.0, 'USD');
        $result = $money->multiply(0.0);

        $this->assertSame(0.0, $result->getAmount());
    }

    #[Test]
    public function it_compares_greater_than(): void
    {
        $a = new Money(200.0);
        $b = new Money(100.0);

        $this->assertTrue($a->isGreaterThan($b));
        $this->assertFalse($b->isGreaterThan($a));
    }

    #[Test]
    public function it_compares_less_than(): void
    {
        $a = new Money(50.0);
        $b = new Money(100.0);

        $this->assertTrue($a->isLessThan($b));
        $this->assertFalse($b->isLessThan($a));
    }

    #[Test]
    public function it_checks_equality(): void
    {
        $a = new Money(100.0, 'USD');
        $b = new Money(100.0, 'USD');

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function it_detects_inequality_by_amount(): void
    {
        $a = new Money(100.0, 'USD');
        $b = new Money(200.0, 'USD');

        $this->assertFalse($a->equals($b));
    }

    #[Test]
    public function it_detects_inequality_by_currency(): void
    {
        $a = new Money(100.0, 'USD');
        $b = new Money(100.0, 'EUR');

        $this->assertFalse($a->equals($b));
    }

    #[Test]
    public function it_serializes_to_array(): void
    {
        $money = new Money(99.99, 'GBP');
        $array = $money->toArray();

        $this->assertSame(['amount' => 99.99, 'currency' => 'GBP'], $array);
    }

    // ---- Immutability ----

    #[Test]
    public function it_is_immutable_on_add(): void
    {
        $original = new Money(100.0);
        $original->add(new Money(50.0));

        $this->assertSame(100.0, $original->getAmount());
    }

    #[Test]
    public function it_is_immutable_on_subtract(): void
    {
        $original = new Money(100.0);
        $original->subtract(new Money(30.0));

        $this->assertSame(100.0, $original->getAmount());
    }

    #[Test]
    public function it_is_immutable_on_multiply(): void
    {
        $original = new Money(100.0);
        $original->multiply(3.0);

        $this->assertSame(100.0, $original->getAmount());
    }

    // ---- Edge Cases & Exceptions ----

    #[Test]
    public function it_throws_for_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount cannot be negative.');

        new Money(-1.0);
    }

    #[Test]
    public function it_throws_for_empty_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency cannot be empty.');

        new Money(100.0, '');
    }

    #[Test]
    public function it_throws_for_whitespace_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Money(100.0, '   ');
    }

    #[Test]
    public function it_throws_when_adding_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add amounts with different currencies.');

        $usd = new Money(100.0, 'USD');
        $eur = new Money(50.0, 'EUR');
        $usd->add($eur);
    }

    #[Test]
    public function it_throws_when_subtracting_different_currencies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot subtract amounts with different currencies.');

        $usd = new Money(100.0, 'USD');
        $eur = new Money(30.0, 'EUR');
        $usd->subtract($eur);
    }
}
