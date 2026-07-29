<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Social;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Social\StoreSocialAccountRequest;
use App\Http\Resources\V1\SocialAccountResource;
use App\Infrastructure\Persistence\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SocialAccountController extends Controller
{
    /**
     * List social accounts for an organization.
     */
    public function index(string $organizationId): JsonResponse
    {
        $accounts = SocialAccount::where('organization_id', $organizationId)
            ->orderBy('platform')
            ->get();

        return response()->json([
            'data' => SocialAccountResource::collection($accounts),
        ]);
    }

    /**
     * Connect a new social account.
     */
    public function store(StoreSocialAccountRequest $request, string $organizationId): JsonResponse
    {
        $validated = $request->validated();
        $validated['organization_id'] = $organizationId;

        $account = SocialAccount::create($validated);

        return response()->json([
            'message' => 'Social account connected successfully.',
            'data' => new SocialAccountResource($account),
        ], 201);
    }

    /**
     * Show a social account.
     */
    public function show(string $organizationId, string $accountId): JsonResponse
    {
        $account = SocialAccount::where('organization_id', $organizationId)
            ->findOrFail($accountId);

        return response()->json([
            'data' => new SocialAccountResource($account),
        ]);
    }

    /**
     * Update a social account.
     */
    public function update(StoreSocialAccountRequest $request, string $organizationId, string $accountId): JsonResponse
    {
        $account = SocialAccount::where('organization_id', $organizationId)
            ->findOrFail($accountId);

        $account->update($request->validated());

        return response()->json([
            'message' => 'Social account updated successfully.',
            'data' => new SocialAccountResource($account->fresh()),
        ]);
    }

    /**
     * Disconnect a social account.
     */
    public function disconnect(string $organizationId, string $accountId): JsonResponse
    {
        $account = SocialAccount::where('organization_id', $organizationId)
            ->findOrFail($accountId);

        $account->update([
            'is_connected' => false,
            'access_token' => null,
            'refresh_token' => null,
        ]);

        return response()->json([
            'message' => 'Social account disconnected.',
        ]);
    }

    /**
     * Sync posts from the connected social account.
     */
    public function sync(string $organizationId, string $accountId): JsonResponse
    {
        $account = SocialAccount::where('organization_id', $organizationId)
            ->findOrFail($accountId);

        if (!$account->is_connected) {
            return response()->json([
                'message' => 'Account is not connected. Please connect first.',
            ], 422);
        }

        // In production: dispatch a job to sync posts from the platform API
        // SyncSocialAccountPostsJob::dispatch($account);

        return response()->json([
            'message' => 'Sync initiated for account: ' . $account->account_name,
        ]);
    }
}
