<?php

declare(strict_types=1);

namespace App\Domain\Social\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: SocialAccount
 *
 * Represents a connected social media platform account within the Astra OS
 * domain. Each account stores platform-specific credentials, tokens, and
 * configuration needed to publish content and retrieve analytics.
 *
 * @package App\Domain\Social\Entities
 */
class SocialAccount
{
    use HasTimestamps;

    /** @var array<int, string> Valid social platforms. */
    public const array VALID_PLATFORMS = [
        'facebook', 'instagram', 'twitter', 'linkedin', 'tiktok',
    ];

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $organizationId;

    /**
     * @var string
     */
    private readonly string $platform;

    /**
     * @var string
     */
    private string $accountId;

    /**
     * @var string
     */
    private string $accountName;

    /**
     * @var string|null
     */
    private ?string $accessToken;

    /**
     * @var string|null
     */
    private ?string $refreshToken;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $tokenExpiresAt;

    /**
     * @var bool
     */
    private bool $isActive;

    /**
     * @var array<string, mixed>
     */
    private array $settings;

    /**
     * @param UuidInterface             $id
     * @param UuidInterface             $organizationId
     * @param string                    $platform
     * @param string                    $accountId
     * @param string                    $accountName
     * @param string|null               $accessToken
     * @param string|null               $refreshToken
     * @param DateTimeImmutable|null    $tokenExpiresAt
     * @param bool                      $isActive
     * @param array<string, mixed>     $settings
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $organizationId,
        string $platform,
        string $accountId,
        string $accountName,
        ?string $accessToken,
        ?string $refreshToken,
        ?DateTimeImmutable $tokenExpiresAt,
        bool $isActive,
        array $settings
    ) {
        $this->id = $id;
        $this->organizationId = $organizationId;
        $this->platform = $platform;
        $this->accountId = $accountId;
        $this->accountName = $accountName;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->tokenExpiresAt = $tokenExpiresAt;
        $this->isActive = $isActive;
        $this->settings = $settings;
    }

    /**
     * Create a new SocialAccount.
     *
     * @param UuidInterface          $organizationId
     * @param string                 $platform
     * @param string                 $accountId
     * @param string                 $accountName
     * @param string|null            $accessToken
     * @param string|null            $refreshToken
     * @param DateTimeImmutable|null $tokenExpiresAt
     * @param array<string, mixed>  $settings
     * @return self
     */
    public static function create(
        UuidInterface $organizationId,
        string $platform,
        string $accountId,
        string $accountName,
        ?string $accessToken = null,
        ?string $refreshToken = null,
        ?DateTimeImmutable $tokenExpiresAt = null,
        array $settings = []
    ): self {
        $platform = strtolower(trim($platform));
        if (!in_array($platform, self::VALID_PLATFORMS, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid platform: "%s". Valid platforms: %s.', $platform, implode(', ', self::VALID_PLATFORMS))
            );
        }

        $account = new self(
            Uuid::uuid4(),
            $organizationId,
            $platform,
            $accountId,
            $accountName,
            $accessToken,
            $refreshToken,
            $tokenExpiresAt,
            true,
            $settings
        );

        $account->initializeTimestamps();

        return $account;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface             $id
     * @param UuidInterface             $organizationId
     * @param string                    $platform
     * @param string                    $accountId
     * @param string                    $accountName
     * @param string|null               $accessToken
     * @param string|null               $refreshToken
     * @param DateTimeImmutable|null    $tokenExpiresAt
     * @param bool                      $isActive
     * @param array<string, mixed>     $settings
     * @param DateTimeImmutable         $createdAt
     * @param DateTimeImmutable         $updatedAt
     * @return self
     */
    public static function reconstitute(
        UuidInterface $id,
        UuidInterface $organizationId,
        string $platform,
        string $accountId,
        string $accountName,
        ?string $accessToken,
        ?string $refreshToken,
        ?DateTimeImmutable $tokenExpiresAt,
        bool $isActive,
        array $settings,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        $account = new self(
            $id, $organizationId, $platform, $accountId, $accountName,
            $accessToken, $refreshToken, $tokenExpiresAt, $isActive, $settings
        );
        $account->setCreatedAt($createdAt);
        $account->setUpdatedAt($updatedAt);

        return $account;
    }

    // ---- Commands ----

    /**
     * Update the access token.
     *
     * @param string      $accessToken
     * @param string|null $refreshToken
     * @param DateTimeImmutable|null $expiresAt
     * @return void
     */
    public function updateTokens(
        string $accessToken,
        ?string $refreshToken = null,
        ?DateTimeImmutable $expiresAt = null
    ): void {
        $this->accessToken = $accessToken;
        if ($refreshToken !== null) {
            $this->refreshToken = $refreshToken;
        }
        $this->tokenExpiresAt = $expiresAt;
        $this->markAsUpdated();
    }

    /**
     * Check if the token has expired.
     *
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        if ($this->tokenExpiresAt === null) {
            return false;
        }
        return $this->tokenExpiresAt < new DateTimeImmutable();
    }

    /**
     * Activate the account.
     *
     * @return void
     */
    public function activate(): void
    {
        $this->isActive = true;
        $this->markAsUpdated();
    }

    /**
     * Deactivate the account.
     *
     * @return void
     */
    public function deactivate(): void
    {
        $this->isActive = false;
        $this->markAsUpdated();
    }

    /**
     * Update the account settings.
     *
     * @param array<string, mixed> $settings
     * @return void
     */
    public function updateSettings(array $settings): void
    {
        $this->settings = $settings;
        $this->markAsUpdated();
    }

    // ---- Getters ----

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIdString(): string
    {
        return $this->id->toString();
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * @return string
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * @return string
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * @return string
     */
    public function getAccountName(): string
    {
        return $this->accountName;
    }

    /**
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getTokenExpiresAt(): ?DateTimeImmutable
    {
        return $this->tokenExpiresAt;
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }
}
