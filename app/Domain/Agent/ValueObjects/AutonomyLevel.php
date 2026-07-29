<?php

declare(strict_types=1);

namespace App\Domain\Agent\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: AutonomyLevel
 *
 * Represents the degree of autonomous decision-making authority granted
 * to an AI agent. Levels range from fully supervised (advisory) through
 * semi-autonomous to fully autonomous operation.
 *
 * @package App\Domain\Agent\ValueObjects
 */
final class AutonomyLevel
{
    /** @var string Agent provides recommendations only; human must approve. */
    public const string ADVISORY = 'advisory';

    /** @var string Agent can act independently within defined boundaries. */
    public const string SEMI_AUTO = 'semi_auto';

    /** @var string Agent can act fully independently. */
    public const string FULL_AUTO = 'full_auto';

    /** @var array<string, string> Valid levels with labels. */
    public const array VALID_LEVELS = [
        self::ADVISORY => 'Advisory',
        self::SEMI_AUTO => 'Semi-Autonomous',
        self::FULL_AUTO => 'Fully Autonomous',
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
    public function __construct(string $value = self::ADVISORY)
    {
        $value = strtolower(trim($value));

        if (!isset(self::VALID_LEVELS[$value])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid autonomy level: "%s". Valid levels: %s.',
                    $value,
                    implode(', ', array_keys(self::VALID_LEVELS))
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
        return self::VALID_LEVELS[$this->value];
    }

    /**
     * @return bool
     */
    public function isAdvisory(): bool
    {
        return $this->value === self::ADVISORY;
    }

    /**
     * @return bool
     */
    public function isSemiAuto(): bool
    {
        return $this->value === self::SEMI_AUTO;
    }

    /**
     * @return bool
     */
    public function isFullAuto(): bool
    {
        return $this->value === self::FULL_AUTO;
    }

    /**
     * @param AutonomyLevel $other
     * @return bool
     */
    public function equals(AutonomyLevel $other): bool
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
