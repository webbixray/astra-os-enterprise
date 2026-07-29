<?php

declare(strict_types=1);

namespace App\Domain\Agent\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: AgentRole
 *
 * Represents the role of an AI agent within the organizational hierarchy
 * as an immutable value object. Roles determine an agent's authority,
 * responsibilities, and position in the agent hierarchy.
 *
 * Implemented as an enum-like class with named static constructors.
 *
 * @package App\Domain\Agent\ValueObjects
 */
final class AgentRole
{
    public const string CEO = 'ceo';
    public const string MARKETING_DIRECTOR = 'marketing_director';
    public const string CREATIVE_DIRECTOR = 'creative_director';
    public const string AD_DIRECTOR = 'ad_director';
    public const string RESEARCH_DIRECTOR = 'research_director';
    public const string ANALYTICS_DIRECTOR = 'analytics_director';
    public const string COMPLIANCE_DIRECTOR = 'compliance_director';
    public const string WORKFLOW_DIRECTOR = 'workflow_director';
    public const string SPECIALIST = 'specialist';

    /** @var array<string, string> Valid roles with display labels. */
    public const array VALID_ROLES = [
        self::CEO => 'CEO',
        self::MARKETING_DIRECTOR => 'Marketing Director',
        self::CREATIVE_DIRECTOR => 'Creative Director',
        self::AD_DIRECTOR => 'Ad Director',
        self::RESEARCH_DIRECTOR => 'Research Director',
        self::ANALYTICS_DIRECTOR => 'Analytics Director',
        self::COMPLIANCE_DIRECTOR => 'Compliance Director',
        self::WORKFLOW_DIRECTOR => 'Workflow Director',
        self::SPECIALIST => 'Specialist',
    ];

    /**
     * @var string
     */
    private readonly string $value;

    /**
     * @param string $value
     */
    private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Create from a string value.
     *
     * @param string $value
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public static function fromString(string $value): self
    {
        $value = strtolower(trim($value));

        if (!isset(self::VALID_ROLES[$value])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid agent role: "%s". Valid roles: %s.',
                    $value,
                    implode(', ', array_keys(self::VALID_ROLES))
                )
            );
        }

        return new self($value);
    }

    /** @return self */
    public static function ceo(): self { return new self(self::CEO); }
    /** @return self */
    public static function marketingDirector(): self { return new self(self::MARKETING_DIRECTOR); }
    /** @return self */
    public static function creativeDirector(): self { return new self(self::CREATIVE_DIRECTOR); }
    /** @return self */
    public static function adDirector(): self { return new self(self::AD_DIRECTOR); }
    /** @return self */
    public static function researchDirector(): self { return new self(self::RESEARCH_DIRECTOR); }
    /** @return self */
    public static function analyticsDirector(): self { return new self(self::ANALYTICS_DIRECTOR); }
    /** @return self */
    public static function complianceDirector(): self { return new self(self::COMPLIANCE_DIRECTOR); }
    /** @return self */
    public static function workflowDirector(): self { return new self(self::WORKFLOW_DIRECTOR); }
    /** @return self */
    public static function specialist(): self { return new self(self::SPECIALIST); }

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
        return self::VALID_ROLES[$this->value];
    }

    /**
     * @param AgentRole $other
     * @return bool
     */
    public function equals(AgentRole $other): bool
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
