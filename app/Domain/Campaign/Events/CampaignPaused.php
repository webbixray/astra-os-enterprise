<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Events;

use DateTimeImmutable;

/**
 * Domain Event: CampaignPaused
 *
 * Fired when an active campaign is paused.
 *
 * @package App\Domain\Campaign\Events
 */
final class CampaignPaused
{
    /**
     * @param string            $campaignId
     * @param DateTimeImmutable $pausedAt
     */
    public function __construct(
        public readonly string $campaignId,
        public readonly DateTimeImmutable $pausedAt
    ) {
    }
}
