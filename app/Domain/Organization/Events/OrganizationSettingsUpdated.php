<?php

declare(strict_types=1);

namespace App\Domain\Organization\Events;

/**
 * Domain Event: OrganizationSettingsUpdated
 *
 * Fired when the settings of an organization are modified. Carries
 * the full new settings data so subscribers can react to specific
 * configuration changes without querying the aggregate.
 *
 * @package App\Domain\Organization\Events
 */
final class OrganizationSettingsUpdated
{
    /**
     * @param string               $organizationId The UUID of the organization.
     * @param array<string, mixed> $settings        The updated settings payload.
     */
    public function __construct(
        public readonly string $organizationId,
        public readonly array $settings
    ) {
    }
}
