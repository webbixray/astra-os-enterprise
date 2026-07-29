<?php

declare(strict_types=1);

namespace Ramsey\Uuid;

/**
 * Minimal stub for Ramsey\Uuid\Uuid to enable domain unit tests
 * without full Composer dependency installation.
 *
 * @method static UuidInterface uuid4()
 */
class Uuid
{
    private static ?string $fixedValue = null;

    public static function uuid4(): UuidInterface
    {
        if (self::$fixedValue !== null) {
            return UuidFactory::fromString(self::$fixedValue);
        }

        // Generate a UUID v4 using PHP 8+ native capabilities
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // Set version to 0100 (UUID v4)
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // Set variant to 10xx

        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        return UuidFactory::fromString($uuid);
    }

    /**
     * Set a fixed UUID for deterministic testing.
     */
    public static function setFixedValue(string $uuid): void
    {
        self::$fixedValue = $uuid;
    }

    /**
     * Reset to random generation.
     */
    public static function resetFixedValue(): void
    {
        self::$fixedValue = null;
    }
}

/**
 * Minimal UuidInterface implementation.
 */
class UuidFactory implements UuidInterface
{
    public function __construct(private readonly string $uuid)
    {
    }

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public function toString(): string
    {
        return $this->uuid;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }

    public function equals(UuidInterface $other): bool
    {
        return $this->uuid === $other->toString();
    }

    public function getHex(): string
    {
        return str_replace('-', '', $this->uuid);
    }
}
