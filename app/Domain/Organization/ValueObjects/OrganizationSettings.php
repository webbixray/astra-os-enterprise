<?php

declare(strict_types=1);

namespace App\Domain\Organization\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: OrganizationSettings
 *
 * Represents the configuration settings for an organization as an immutable
 * value object. Provides type-safe access to common organization settings
 * such as theme, locale, timezone, and feature flags.
 *
 * @package App\Domain\Organization\ValueObjects
 */
final class OrganizationSettings
{
    /**
     * The raw settings data.
     *
     * @var array<string, mixed>
     */
    private readonly array $data;

    /**
     * Create a new OrganizationSettings value object.
     *
     * @param array<string, mixed> $data The settings key-value pairs.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Get all settings as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get a specific setting value by key, with optional default.
     *
     * @param string $key     The setting key.
     * @param mixed  $default Default value if key not found.
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a setting key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Merge these settings with additional settings.
     *
     * @param array<string, mixed> $overrides Settings to merge on top.
     * @return self A new instance with merged data.
     */
    public function merge(array $overrides): self
    {
        return new self(array_merge($this->data, $overrides));
    }

    /**
     * Get the theme setting.
     *
     * @return string
     */
    public function getTheme(): string
    {
        return $this->get('theme', 'default');
    }

    /**
     * Get the locale setting.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->get('locale', 'en_US');
    }

    /**
     * Get the timezone setting.
     *
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->get('timezone', 'UTC');
    }

    /**
     * Check if a feature flag is enabled.
     *
     * @param string $feature The feature flag name.
     * @return bool
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $features = $this->get('features', []);
        return (bool) ($features[$feature] ?? false);
    }

    /**
     * Get the default currency for this organization.
     *
     * @return string
     */
    public function getDefaultCurrency(): string
    {
        return $this->get('default_currency', 'USD');
    }

    /**
     * Get the maximum number of members allowed.
     *
     * @return int
     */
    public function getMaxMembers(): int
    {
        return (int) $this->get('max_members', 10);
    }

    /**
     * Check equality with another OrganizationSettings.
     *
     * @param OrganizationSettings $other
     * @return bool
     */
    public function equals(OrganizationSettings $other): bool
    {
        return $this->data === $other->data;
    }
}
