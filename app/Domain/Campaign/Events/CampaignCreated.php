<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Events;

use DateTimeImmutable;

/**
 * Domain Event: CampaignCreated
 *
 * Fired when a new campaign is created within an organization.
 *
 * @package App\Domain\Campaign\Events
 */
final class CampaignCreated
{
    /**
     * @param string $campaignId
     * @param string $organizationId
     * @param string $name
     * @param string $objective
     */
    public function __construct(
        public readonly string $campaignId,
        public readonly string $organizationId,
        public readonly string $name,
        public readonly string $objective
    ) {
    }
}
