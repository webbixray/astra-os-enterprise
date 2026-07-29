<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Service TokenRotationService
 *
 * Detects stale API tokens (older than the configured rotation window),
 * rotates them transparently, and logs every rotation event.
 *
 * Token rotation reduces the blast radius of a leaked credential by
 * ensuring tokens have a limited useful lifespan.
 */
final class TokenRotationService
{
    /**
     * The number of days after which a token is considered stale.
     */
    private int $rotationDays;

    public function __construct()
    {
        $this->rotationDays = (int) config('security.secret_rotation.token_rotation_days', 30);
    }

    /**
     * Rotate all expired or stale tokens for a given user.
     *
     * Returns an array of rotated token IDs so the calling process (CLI
     * command, scheduled task, or middleware hook) can inspect the result.
     *
     * @return list<array{old_token_id: string, new_token_id: string, expires_at: string}>
     */
    public function rotateUserTokens(User $user): array
    {
        $staleTokens = $this->getStaleTokens($user);
        $rotated = [];

        DB::beginTransaction();

        try {
            foreach ($staleTokens as $token) {
                $newToken = $this->rotateToken($user, $token);
                $rotated[] = [
                    'old_token_id' => (string) $token->getKey(),
                    'new_token_id' => $newToken['id'],
                    'expires_at' => $newToken['expires_at'],
                ];
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Token rotation failed for user ' . $user->getKey() . ': ' . $e->getMessage());

            throw $e;
        }

        if ($rotated !== []) {
            $this->logRotation($user, $rotated);
        }

        return $rotated;
    }

    /**
     * Rotate all stale tokens across the entire application.
     *
     * Intended for use by the scheduler (e.g. daily "security:rotate-tokens"
     * command).
     *
     * @return array<string, list<array{old_token_id: string, new_token_id: string, expires_at: string}>>
     */
    public function rotateAllStaleTokens(): array
    {
        $results = [];
        $cutoff = Carbon::now()->subDays($this->rotationDays);

        $staleTokenIds = PersonalAccessToken::query()
            ->where('created_at', '<', $cutoff)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->pluck('id');

        $userIds = PersonalAccessToken::query()
            ->whereIn('id', $staleTokenIds)
            ->distinct()
            ->pluck('tokenable_id');

        foreach ($userIds as $userId) {
            $user = User::find($userId);

            if ($user === null) {
                continue;
            }

            $rotated = $this->rotateUserTokens($user);

            if ($rotated !== []) {
                $results[$userId] = $rotated;
            }
        }

        return $results;
    }

    /**
     * Validate that a token's access scopes satisfy the required scopes.
     *
     * @param  list<string>  $requiredScopes
     */
    public function validateTokenScopes(PersonalAccessToken $token, array $requiredScopes): bool
    {
        $tokenAbilities = $token->abilities;

        // Tokens with '*' have all abilities.
        if (in_array('*', $tokenAbilities, true)) {
            return true;
        }

        foreach ($requiredScopes as $scope) {
            if (! in_array($scope, $tokenAbilities, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Revoke all tokens for a user (e.g. after password change).
     */
    public function revokeAllTokens(User $user, ?string $exceptTokenId = null): int
    {
        $query = PersonalAccessToken::query()
            ->where('tokenable_id', $user->getKey())
            ->where('tokenable_type', $user::class);

        if ($exceptTokenId !== null) {
            $query->where('id', '!=', $exceptTokenId);
        }

        $count = $query->delete();

        if ($count > 0) {
            Log::info('Revoked ' . $count . ' tokens for user ' . $user->getKey(), [
                'user_id' => $user->getKey(),
                'count' => $count,
                'except_token' => $exceptTokenId,
            ]);
        }

        return $count;
    }

    // ------------------------------------------------------------------ private

    /**
     * Retrieve tokens that are older than the rotation threshold and still
     * valid (not expired).
     *
     * @return \Illuminate\Support\Collection<int, PersonalAccessToken>
     */
    private function getStaleTokens(User $user): \Illuminate\Support\Collection
    {
        $cutoff = Carbon::now()->subDays($this->rotationDays);

        return PersonalAccessToken::query()
            ->where('tokenable_id', $user->getKey())
            ->where('tokenable_type', $user::class)
            ->where('created_at', '<', $cutoff)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Rotate a single token: delete the old one and create a new one with
     * the same abilities.
     *
     * @return array{id: string, expires_at: string}
     */
    private function rotateToken(User $user, PersonalAccessToken $oldToken): array
    {
        $tokenName = $oldToken->name ?: 'rotated-token';
        $abilities = $oldToken->abilities ?: ['*'];
        $expiration = $oldToken->expires_at
            ? Carbon::parse($oldToken->expires_at)
            : Carbon::now()->addDays($this->rotationDays);

        // Delete the stale token.
        $oldToken->delete();

        // Issue a fresh token with the same abilities.
        $plainTextToken = $user->createToken($tokenName, $abilities, $expiration);

        /** @var PersonalAccessToken $newToken */
        $newToken = $plainTextToken->accessToken;

        return [
            'id' => (string) $newToken->getKey(),
            'expires_at' => $newToken->expires_at?->toIso8601String() ?? 'never',
        ];
    }

    /**
     * Log token rotation events to the audit channel.
     *
     * @param  list<array{old_token_id: string, new_token_id: string, expires_at: string}>  $rotated
     */
    private function logRotation(User $user, array $rotated): void
    {
        Log::channel('audit')->info('Token rotation completed', [
            'user_id' => $user->getKey(),
            'rotated_count' => count($rotated),
            'tokens' => $rotated,
        ]);
    }
}
