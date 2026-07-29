<?php

declare(strict_types=1);

namespace App\Domain\Social\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: PlatformType
 *
 * Represents a supported social media platform as an immutable value object.
 * Provides type-safe platform identification and validation.
 *
 * @package App\Domain\Social\ValueObjects
 */
final class PlatformType
{
    public const string FACEBOOK = 'facebook';
    public const string INSTAGRAM = 'instagram';
    public const string TWITTER = 'twitter';
    public const string LINKEDIN = 'linkedin';
    public const string TIKTOK = 'tiktok';
    public const string YOUTUBE = 'youtube';

    /** @var array<string, string> */
    public const array VALID_PLATFORMS = [
        self::FACEBOOK => 'Facebook',
        self::INSTAGRAM => 'Instagram',
        self::TWITTER => 'Twitter',
        self::LINKEDIN => 'LinkedIn',
        self::TIKTOK => 'TikTok',
        self::YOUTUBE => 'YouTube',
    ];

    /**
     * @var string
     */
    private readonly string $value;

    /**
     * @param string $value
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $value)
    {
        $value = strtolower(trim($value));

        if (!isset(self::VALID_PLATFORMS[$value])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid platform: "%s". Valid platforms: %s.',
                    $value,
                    implode(', ', array_keys(self::VALID_PLATFORMS))
                )
            );
        }

        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return self::VALID_PLATFORMS[$this->value];
    }

    /**
     * @return bool
     */
    public function isFacebook(): bool
    {
        return $this->value === self::FACEBOOK;
    }

    /**
     * @return bool
     */
    public function isInstagram(): bool
    {
        return $this->value === self::INSTAGRAM;
    }

    /**
     * @return bool
     */
    public function isTwitter(): bool
    {
        return $this->value === self::TWITTER;
    }

    /**
     * @return bool
     */
    public function isLinkedIn(): bool
    {
        return $this->value === self::LINKEDIN;
    }

    /**
     * @return bool
     */
    public function isTikTok(): bool
    {
        return $this->value === self::TIKTOK;
    }

    /**
     * @return bool
     */
    public function isYouTube(): bool
    {
        return $this->value === self::YOUTUBE;
    }

    /**
     * @param PlatformType $other
     * @return bool
     */
    public function equals(PlatformType $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
