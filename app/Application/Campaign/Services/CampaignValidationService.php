<?php

declare(strict_types=1);

namespace App\Application\Campaign\Services;

use App\Application\Campaign\DTOs\CreateCampaignDTO;
use App\Domain\Campaign\Entities\Campaign;
use App\Domain\Common\ValueObjects\Money;
use DateTimeImmutable;
use InvalidArgumentException;

final class CampaignValidationService
{
    private const array VALID_OBJECTIVES = [
        'brand_awareness', 'lead_generation', 'conversions', 'engagement', 'traffic', 'sales',
    ];

    private const array VALID_PLATFORMS = [
        'google_ads', 'meta_ads', 'linkedin_ads', 'twitter_ads', 'tiktok_ads', 'programmatic',
    ];

    public function validateCampaignData(CreateCampaignDTO $dto): void
    {
        if (empty(trim($dto->name))) {
            throw new InvalidArgumentException('Campaign name is required and cannot be empty.');
        }

        if (mb_strlen($dto->name) > 255) {
            throw new InvalidArgumentException('Campaign name must not exceed 255 characters.');
        }

        if (empty(trim($dto->objective))) {
            throw new InvalidArgumentException('Campaign objective is required.');
        }

        if (!in_array($dto->objective, self::VALID_OBJECTIVES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid objective "%s". Valid objectives: %s.',
                    $dto->objective,
                    implode(', ', self::VALID_OBJECTIVES)
                )
            );
        }
    }

    public function validateDateRange(DateTimeImmutable $startDate, DateTimeImmutable $endDate): void
    {
        $now = new DateTimeImmutable();

        if ($startDate >= $endDate) {
            throw new InvalidArgumentException('Start date must be before end date.');
        }

        if ($endDate <= $now) {
            throw new InvalidArgumentException('End date must be in the future.');
        }
    }

    public function validatePlatforms(array $platforms): void
    {
        if (empty($platforms)) {
            throw new InvalidArgumentException('At least one platform must be specified.');
        }

        foreach ($platforms as $platform) {
            if (!in_array($platform, self::VALID_PLATFORMS, true)) {
                throw new InvalidArgumentException(
                    sprintf('Unsupported platform "%s". Valid platforms: %s.', $platform, implode(', ', self::VALID_PLATFORMS))
                );
            }
        }
    }

    public function validateCampaignCanLaunch(Campaign $campaign): void
    {
        $status = $campaign->getStatus();

        if (!in_array($status, ['draft', 'paused'], true)) {
            throw new InvalidArgumentException(
                sprintf('Cannot launch campaign in status "%s". Must be "draft" or "paused".', $status)
            );
        }

        if (empty($campaign->getPlatforms())) {
            throw new InvalidArgumentException('Campaign must have at least one platform configured before launching.');
        }

        $budget = $campaign->getBudget();
        if ($budget->getAmount() <= 0) {
            throw new InvalidArgumentException('Campaign budget must be greater than zero.');
        }
    }
}
