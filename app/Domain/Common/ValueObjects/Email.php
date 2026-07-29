<?php

declare(strict_types=1);

namespace App\Domain\Common\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object Email
 *
 * Represents an immutable email address value object with built-in
 * validation and canonical formatting. Two Email instances are equal
 * if they contain the same normalized email address string.
 *
 * @package App\Domain\Common\ValueObjects
 */
final class Email
{
    /**
     * The validated and normalized email address.
     *
     * @var string
     */
    private readonly string $value;

    /**
     * Create a new Email value object.
     *
     * @param string $value The raw email address.
     *
     * @throws InvalidArgumentException If the email is invalid.
     */
    public function __construct(string $value)
    {
        $normalized = $this->normalize($value);

        if (!$this->isValid($normalized)) {
            throw new InvalidArgumentException(
                sprintf('Invalid email address: "%s"', $value)
            );
        }

        $this->value = $normalized;
    }

    /**
     * Get the canonical email address string.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Return the email address as a string.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * String conversion alias.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Get the local part of the email address.
     *
     * @return string
     */
    public function getLocalPart(): string
    {
        return explode('@', $this->value)[0];
    }

    /**
     * Get the domain part of the email address.
     *
     * @return string
     */
    public function getDomain(): string
    {
        return explode('@', $this->value)[1];
    }

    /**
     * Check equality with another Email value object.
     *
     * @param Email $other
     * @return bool
     */
    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Normalize the email address.
     *
     * @param string $value
     * @return string
     */
    private function normalize(string $value): string
    {
        return trim(strtolower($value));
    }

    /**
     * Validate the email address format.
     *
     * @param string $value
     * @return bool
     */
    private function isValid(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = explode('@', $value)[1] ?? '';
        if (!str_contains($domain, '.')) {
            return false;
        }

        return true;
    }
}
