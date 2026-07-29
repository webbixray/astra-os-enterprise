<?php

declare(strict_types=1);

namespace App\Domain\Organization\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: OrganizationMember
 *
 * Represents a user's membership within an organization, including their
 * assigned role and associated permissions. Each membership links a user
 * to an organization and determines what actions they can perform within
 * that organization's context.
 *
 * @package App\Domain\Organization\Entities
 */
class OrganizationMember
{
    use HasTimestamps;

    /** @var string Role constant for organization owner. */
    public const string ROLE_OWNER = 'owner';

    /** @var string Role constant for organization admin. */
    public const string ROLE_ADMIN = 'admin';

    /** @var string Role constant for regular member. */
    public const string ROLE_MEMBER = 'member';

    /** @var array<int, string> Valid roles. */
    public const array VALID_ROLES = [
        self::ROLE_OWNER,
        self::ROLE_ADMIN,
        self::ROLE_MEMBER,
    ];

    /**
     * The unique identifier for this membership record.
     *
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * The organization ID this membership belongs to.
     *
     * @var UuidInterface
     */
    private readonly UuidInterface $organizationId;

    /**
     * The user ID who holds this membership.
     *
     * @var UuidInterface
     */
    private readonly UuidInterface $userId;

    /**
     * The role assigned to this member within the organization.
     *
     * @var string
     */
    private string $role;

    /**
     * The specific permissions granted to this member.
     *
     * @var array<string, bool>
     */
    private array $permissions;

    /**
     * The user ID who invited this member, if applicable.
     *
     * @var string|null
     */
    private ?string $invitedBy;

    /**
     * Private constructor.
     *
     * @param UuidInterface $id
     * @param UuidInterface $organizationId
     * @param UuidInterface $userId
     * @param string        $role
     * @param array<string, bool> $permissions
     * @param string|null   $invitedBy
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $organizationId,
        UuidInterface $userId,
        string $role,
        array $permissions,
        ?string $invitedBy = null
    ) {
        $this->id = $id;
        $this->organizationId = $organizationId;
        $this->userId = $userId;
        $this->role = $role;
        $this->permissions = $permissions;
        $this->invitedBy = $invitedBy;
    }

    /**
     * Create a new OrganizationMember.
     *
     * @param UuidInterface      $organizationId
     * @param UuidInterface      $userId
     * @param string             $role
     * @param array<string, bool> $permissions
     * @param string|null        $invitedBy
     * @return self
     *
     * @throws InvalidArgumentException If role is invalid.
     */
    public static function create(
        UuidInterface $organizationId,
        UuidInterface $userId,
        string $role = self::ROLE_MEMBER,
        array $permissions = [],
        ?string $invitedBy = null
    ): self {
        self::validateRole($role);

        $member = new self(
            Uuid::uuid4(),
            $organizationId,
            $userId,
            $role,
            $permissions,
            $invitedBy
        );

        $member->initializeTimestamps();

        return $member;
    }

    /**
     * Reconstitute an OrganizationMember from persistence.
     *
     * @param UuidInterface      $id
     * @param UuidInterface      $organizationId
     * @param UuidInterface      $userId
     * @param string             $role
     * @param array<string, bool> $permissions
     * @param string|null        $invitedBy
     * @param DateTimeImmutable  $createdAt
     * @param DateTimeImmutable  $updatedAt
     * @return self
     */
    public static function reconstitute(
        UuidInterface $id,
        UuidInterface $organizationId,
        UuidInterface $userId,
        string $role,
        array $permissions,
        ?string $invitedBy,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        $member = new self($id, $organizationId, $userId, $role, $permissions, $invitedBy);
        $member->setCreatedAt($createdAt);
        $member->setUpdatedAt($updatedAt);

        return $member;
    }

    /**
     * Get the membership ID.
     *
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * Get the organization ID.
     *
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * Get the user ID.
     *
     * @return UuidInterface
     */
    public function getUserId(): UuidInterface
    {
        return $this->userId;
    }

    /**
     * Get the member's role.
     *
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Change the member's role.
     *
     * @param string $role
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function changeRole(string $role): void
    {
        self::validateRole($role);
        $this->role = $role;
        $this->markAsUpdated();
    }

    /**
     * Get the member's permissions.
     *
     * @return array<string, bool>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Set permissions for this member.
     *
     * @param array<string, bool> $permissions
     * @return void
     */
    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
        $this->markAsUpdated();
    }

    /**
     * Check if the member has a specific permission.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions[$permission] ?? false;
    }

    /**
     * Get the inviter's user ID.
     *
     * @return string|null
     */
    public function getInvitedBy(): ?string
    {
        return $this->invitedBy;
    }

    /**
     * Check if this member is an owner.
     *
     * @return bool
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Check if this member is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Validate the role string.
     *
     * @param string $role
     * @return void
     *
     * @throws InvalidArgumentException
     */
    private static function validateRole(string $role): void
    {
        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid organization role: "%s". Valid roles: %s.',
                    $role,
                    implode(', ', self::VALID_ROLES)
                )
            );
        }
    }
}
