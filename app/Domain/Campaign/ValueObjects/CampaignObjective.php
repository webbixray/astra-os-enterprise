<?php

declare(strict_types=1);

namespace App\Domain\Campaign\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: CampaignObjective
 *
 * Represents the marketing objective of a campaign as a type-safe
 * immutable value object. Encapsulates the strategic goal that the
 * campaign is designed to achieve, such as brand awareness, traffic
 * generation, or conversions.
 *
 * This is implemented as an enum-like class with named static constructors
 * rather than a PHP enum to maintain consistent domain patterns and
 * allow for future extensibility if needed.
 *
 * @package App\Domain\Campaign\ValueObjects
 */
final class CampaignObjective
{
    /** @var string Drive brand recognition and recall. */
    public const string BRAND_AWARENESS = 'brand_awareness';

    /** @var string Drive visitors to a website or landing page. */
    public const string TRAFFIC = 'traffic';

    /** @var string Drive purchases, sign-ups, or other conversions. */
    public const string CONVERSIONS = 'conversions';

    /** @var string Drive likes, shares, comments, and social engagement. */
    public const string ENGAGEMENT = 'engagement';

    /** @var string Collect leads and contact information. */
    public const string LEAD_GENERATION = 'lead_generation';

    /** @var string Drive direct sales through the platform. */
    public const string SALES = 'sales';

    /** @var array<string, string> All valid objectives with display labels. */
    public const array VALID_OBJECTIVES = [
        self::BRAND_AWARENESS => 'Brand Awareness',
        self::TRAFFIC => 'Traffic',
        self::CONVERSIONS => 'Conversions',
        self::ENGAGEMENT => 'Engagement',
        self::LEAD_GENERATION => 'Lead Generation',
        self::SALES => 'Sales',
    ];

    /**
     * The internal objective value.
     *
     * @var string
     */
    private readonly string $value;

    /**
     * Private constructor.
     *
     * @param string $value
     */
    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Create from a string value, validating it.
     *
     * @param string $value
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (!isset(self::VALID_OBJECTIVES[$value])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid campaign objective: "%s". Valid objectives: %s.',
                    $value,
                    implode(', ', array_keys(self::VALID_OBJECTIVES))
                )
            );
        }

        return new self($value);
    }

    /**
     * @return self
     */
    public static function brandAwareness(): self
    {
        return new self(self::BRAND_AWARENESS);
    }

    /**
     * @return self
     */
    public static function traffic(): self
    {
        return new self(self::TRAFFIC);
    }

    /**
     * @return self
     */
    public static function conversions(): self
    {
        return new self(self::CONVERSIONS);
    }

    /**
     * @return self
     */
    public static function engagement(): self
    {
        return new self(self::ENGAGEMENT);
    }

    /**
     * @return self
     */
    public static function leadGeneration(): self
    {
        return new self(self::LEAD_GENERATION);
    }

    /**
     * @return self
     */
    public static function sales(): self
    {
        return new self(self::SALES);
    }

    /**
     * Get the string value.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the human-readable label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return self::VALID_OBJECTIVES[$this->value];
    }

    /**
     * Check equality.
     *
     * @param CampaignObjective $other
     * @return bool
     */
    public function equals(CampaignObjective $other): bool
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
