<?php

declare(strict_types=1);

use App\Domain\Common\ValueObjects\Email;
use App\Domain\Common\ValueObjects\Money;
use Ramsey\Uuid\Uuid;

// Domain-level helper functions for Astra OS.
// These are framework-agnostic and operate exclusively on domain objects.

if (!function_exists('domain_uuid')) {
    /**
     * Generate a UUID v4 string for domain entities.
     *
     * @return string A version 4 (random) UUID string.
     */
    function domain_uuid(): string
    {
        return Uuid::uuid4()->toString();
    }
}

if (!function_exists('domain_money')) {
    /**
     * Create a Money value object from major units (e.g., dollars).
     *
     * @param float  $amount   Amount in major currency units.
     * @param string $currency ISO 4217 three-letter code (default: USD).
     * @return Money
     */
    function domain_money(float $amount, string $currency = 'USD'): Money
    {
        return Money::fromMajorUnits($amount, $currency);
    }
}

if (!function_exists('domain_email')) {
    /**
     * Create an Email value object.
     *
     * @param string $address The email address.
     * @return Email
     */
    function domain_email(string $address): Email
    {
        return new Email($address);
    }
}

if (!function_exists('domain_array_get')) {
    /**
     * Get a value from a nested array using dot notation safely.
     *
     * @param array<string, mixed> $array   The array to search.
     * @param string               $key     Dot-notation key path.
     * @param mixed                $default Default if key not found.
     * @return mixed
     */
    function domain_array_get(array $array, string $key, mixed $default = null): mixed
    {
        if ($key === '') {
            return $default;
        }

        $segments = explode('.', $key);
        $current = $array;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }
}

if (!function_exists('domain_array_set')) {
    /**
     * Set a value in a nested array using dot notation.
     *
     * @param array<string, mixed> $array The array to modify.
     * @param string               $key   Dot-notation key path.
     * @param mixed                $value The value to assign.
     * @return array<string, mixed>
     */
    function domain_array_set(array $array, string $key, mixed $value): array
    {
        if ($key === '') {
            return $array;
        }

        $segments = explode('.', $key);
        $result = &$array;

        foreach ($segments as $segment) {
            if (!isset($result[$segment]) || !is_array($result[$segment])) {
                $result[$segment] = [];
            }
            $result = &$result[$segment];
        }

        $result = $value;

        return $array;
    }
}

if (!function_exists('domain_array_has')) {
    /**
     * Check if a key exists in a nested array using dot notation.
     *
     * @param array<string, mixed> $array
     * @param string               $key
     * @return bool
     */
    function domain_array_has(array $array, string $key): bool
    {
        if ($key === '') {
            return false;
        }

        $segments = explode('.', $key);
        $current = $array;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }
}

if (!function_exists('domain_slug')) {
    /**
     * Generate a URL-friendly slug from a string.
     *
     * @param string $string The input string.
     * @return string The generated slug.
     */
    function domain_slug(string $string): string
    {
        $string = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $string) ?? $string;
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        $string = trim($string, '-');

        return $string;
    }
}

if (!function_exists('domain_truncate')) {
    /**
     * Safely truncate a string with an ellipsis.
     *
     * @param string $text     The text to truncate.
     * @param int    $length   Maximum length (default: 100).
     * @param string $ellipsis Suffix when truncated (default: "...").
     * @return string
     */
    function domain_truncate(string $text, int $length = 100, string $ellipsis = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $truncated = mb_substr($text, 0, $length - mb_strlen($ellipsis));

        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $length / 2) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }

        return $truncated . $ellipsis;
    }
}
