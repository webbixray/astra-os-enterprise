<?php

declare(strict_types=1);

namespace App\Application\Organization\Services;

use InvalidArgumentException;

final class OrganizationValidationService
{
    private const int MAX_NAME_LENGTH = 255;
    private const int MAX_SLUG_LENGTH = 100;
    private const int MAX_DESCRIPTION_LENGTH = 2000;

    public function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidArgumentException('Organization name is required and cannot be empty.');
        }

        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Organization name must not exceed %d characters.', self::MAX_NAME_LENGTH)
            );
        }
    }

    public function validateSlug(string $slug): void
    {
        if (empty(trim($slug))) {
            throw new InvalidArgumentException('Organization slug is required and cannot be empty.');
        }

        if (mb_strlen($slug) > self::MAX_SLUG_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Organization slug must not exceed %d characters.', self::MAX_SLUG_LENGTH)
            );
        }

        if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException(
                'Organization slug must contain only lowercase letters, numbers, and hyphens.'
            );
        }
    }

    public function validateDescription(?string $description): void
    {
        if ($description !== null && mb_strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Organization description must not exceed %d characters.', self::MAX_DESCRIPTION_LENGTH)
            );
        }
    }
}
