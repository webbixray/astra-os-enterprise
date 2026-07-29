<?php

declare(strict_types=1);

namespace App\Domain\Campaign\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object: TargetAudience
 *
 * Represents the target audience configuration for a campaign as an
 * immutable value object. Encapsulates demographic, geographic, and
 * behavioral targeting criteria used to define who should see the
 * campaign's advertisements.
 *
 * @package App\Domain\Campaign\ValueObjects
 */
final class TargetAudience
{
    /**
     * @var array{min: int|null, max: int|null}|null
     */
    private readonly ?array $ageRange;

    /**
     * @var string|null
     */
    private readonly ?string $gender;

    /**
     * @var array<int, string>
     */
    private readonly array $locations;

    /**
     * @var array<int, string>
     */
    private readonly array $interests;

    /**
     * @var array<int, string>
     */
    private readonly array $behaviors;

    /**
     * @var array<int, string>
     */
    private readonly array $customAudiences;

    /**
     * @param array{min?: int, max?: int}|null $ageRange
     * @param string|null                       $gender
     * @param array<int, string>               $locations
     * @param array<int, string>               $interests
     * @param array<int, string>               $behaviors
     * @param array<int, string>               $customAudiences
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        ?array $ageRange = null,
        ?string $gender = null,
        array $locations = [],
        array $interests = [],
        array $behaviors = [],
        array $customAudiences = []
    ) {
        if ($ageRange !== null) {
            $min = $ageRange['min'] ?? null;
            $max = $ageRange['max'] ?? null;

            if ($min !== null && ($min < 0 || $min > 120)) {
                throw new InvalidArgumentException('Minimum age must be between 0 and 120.');
            }
            if ($max !== null && ($max < 0 || $max > 120)) {
                throw new InvalidArgumentException('Maximum age must be between 0 and 120.');
            }
            if ($min !== null && $max !== null && $min > $max) {
                throw new InvalidArgumentException('Minimum age cannot exceed maximum age.');
            }

            $this->ageRange = ['min' => $min, 'max' => $max];
        } else {
            $this->ageRange = null;
        }

        $validGenders = ['male', 'female', 'all'];
        if ($gender !== null && !in_array(strtolower($gender), $validGenders, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid gender: "%s". Valid values: %s.', $gender, implode(', ', $validGenders))
            );
        }

        $this->gender = $gender !== null ? strtolower($gender) : null;
        $this->locations = $locations;
        $this->interests = $interests;
        $this->behaviors = $behaviors;
        $this->customAudiences = $customAudiences;
    }

    /**
     * @return array{min: int|null, max: int|null}|null
     */
    public function getAgeRange(): ?array
    {
        return $this->ageRange;
    }

    /**
     * @return int|null
     */
    public function getMinAge(): ?int
    {
        return $this->ageRange['min'] ?? null;
    }

    /**
     * @return int|null
     */
    public function getMaxAge(): ?int
    {
        return $this->ageRange['max'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @return array<int, string>
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @return array<int, string>
     */
    public function getInterests(): array
    {
        return $this->interests;
    }

    /**
     * @return array<int, string>
     */
    public function getBehaviors(): array
    {
        return $this->behaviors;
    }

    /**
     * @return array<int, string>
     */
    public function getCustomAudiences(): array
    {
        return $this->customAudiences;
    }

    /**
     * Convert to an array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'age_range' => $this->ageRange,
            'gender' => $this->gender,
            'locations' => $this->locations,
            'interests' => $this->interests,
            'behaviors' => $this->behaviors,
            'custom_audiences' => $this->customAudiences,
        ];
    }

    /**
     * Check equality with another TargetAudience.
     *
     * @param TargetAudience $other
     * @return bool
     */
    public function equals(TargetAudience $other): bool
    {
        return $this->ageRange === $other->ageRange
            && $this->gender === $other->gender
            && $this->locations === $other->locations
            && $this->interests === $other->interests
            && $this->behaviors === $other->behaviors
            && $this->customAudiences === $other->customAudiences;
    }
}
