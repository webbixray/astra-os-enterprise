<?php

declare(strict_types=1);

namespace App\Application\Organization\UseCases;

use App\Domain\Organization\Events\MemberInvited;
use App\Domain\Organization\Repositories\OrganizationRepositoryInterface;
use RuntimeException;

final readonly class InviteMemberUseCase
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository,
    ) {}

    /**
     * Invite a member to an organization.
     *
     * @throws RuntimeException if organization is not found.
     */
    public function execute(int $organizationId, int $userId, string $role = 'member'): void
    {
        $organization = $this->organizationRepository->findById($organizationId);

        if ($organization === null) {
            throw new RuntimeException("Organization with ID {$organizationId} not found.");
        }

        if (!in_array($role, ['admin', 'member', 'viewer'], true)) {
            throw new \InvalidArgumentException("Invalid role: {$role}. Must be one of: admin, member, viewer.");
        }

        $this->organizationRepository->addMember($organizationId, $userId, $role);

        MemberInvited::dispatch($organizationId, $userId, $role);
    }
}
