<?php

declare(strict_types=1);

namespace App\Domain\Organization\Events;

use DateTimeImmutable;

/**
 * Domain Event: OrganizationCreated
 *
 * Fired when a new organization is created in the system. This event
 * carries the essential identifying information about the new organization
 * and can be used to trigger downstream processes such as provisioning
 * default resources, sending welcome notifications, or initializing
 * billing accounts.
 *
 * @package App\Domain\Organization\Events
 */
final class OrganizationCreated
{
    /**
     * @param string            $organizationId The UUID of the created organization.
     * @param string            $name           The display name of the organization.
     * @param DateTimeImmutable $createdAt      The timestamp when the organization was created.
     */
    public function __construct(
        public readonly string $organizationId,
        public readonly string $name,
        public readonly DateTimeImmutable $createdAt
    ) {
    }
}
