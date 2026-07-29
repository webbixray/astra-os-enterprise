<?php

declare(strict_types=1);

namespace App\Domain\Organization\Events;

/**
 * Domain Event: MemberInvited
 *
 * Fired when a user is invited to join an organization. This event
 * can trigger notification workflows, permission provisioning, and
 * onboarding sequences for the invited user.
 *
 * @package App\Domain\Organization\Events
 */
final class MemberInvited
{
    /**
     * @param string $organizationId The UUID of the organization.
     * @param string $userId         The UUID of the invited user.
     * @param string $role           The role assigned to the invited member.
     * @param string $invitedBy      The UUID of the user who sent the invitation.
     */
    public function __construct(
        public readonly string $organizationId,
        public readonly string $userId,
        public readonly string $role,
        public readonly string $invitedBy
    ) {
    }
}
