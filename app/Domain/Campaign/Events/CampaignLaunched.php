<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Events;

use DateTimeImmutable;

/**
 * Domain Event: CampaignLaunched
 *
 * Fired when a campaign transitions from draft/paused to active status.
 *
 * @package App\Domain\Campaign\Events
 */
final class CampaignLaunched
{
    /**
     * @param string            $campaignId
     * @param DateTimeImmutable $launchedAt
     */
    public function __construct(
        public readonly string $campaignId,
        public readonly DateTimeImmutable $launchedAt
    ) {
    }
}
