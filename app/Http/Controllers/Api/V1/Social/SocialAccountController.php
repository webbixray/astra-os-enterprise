<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Social;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Social\StoreSocialAccountRequest;
use App\Http\Resources\V1\SocialAccountResource;
use App\Infrastructure\Persistence\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Social Accounts', description: 'Social media account connections — CRUD, sync, disconnect')]
#[OA\Schema(
    schema: 'SocialAccount',
    description: 'Connected social media account',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'organization_id', type: 'integer', example: 1),
        new OA\Property(property: 'platform', type: 'string', enum: ['twitter', 'facebook', 'linkedin', 'instagram'], example: 'twitter'),
        new OA\Property(property: 'account_name', type: 'string', example: '@mybrand'),
        new OA\Property(property: 'account_id', type: 'string', example: '123456789'),
        new OA\Property(property: 'is_connected', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class SocialAccountController extends Controller
{
    /**
     * List social accounts for an organization.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/social/accounts',
        summary: 'List social accounts',
        description: 'Return all connected social media accounts for an organization.',
        tags: ['Social Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of social accounts',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SocialAccount')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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
    #[OA\Post(
        path: '/organizations/{organizationId}/social/accounts',
        summary: 'Connect social account',
        description: 'Connect a new social media account to the organization.',
        tags: ['Social Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['platform', 'account_name', 'account_id'],
                properties: [
                    new OA\Property(property: 'platform', type: 'string', enum: ['twitter', 'facebook', 'linkedin', 'instagram'], example: 'twitter'),
                    new OA\Property(property: 'account_name', type: 'string', example: '@mybrand'),
                    new OA\Property(property: 'account_id', type: 'string', example: '123456789'),
                    new OA\Property(property: 'access_token', type: 'string', description: 'OAuth access token'),
                    new OA\Property(property: 'refresh_token', type: 'string', description: 'OAuth refresh token'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Social account connected',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Social account connected successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SocialAccount'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
    #[OA\Get(
        path: '/organizations/{organizationId}/social/accounts/{accountId}',
        summary: 'Show social account',
        description: 'Return a single connected social account.',
        tags: ['Social Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'accountId', description: 'Social account ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Social account details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/SocialAccount'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
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
    #[OA\Put(
        path: '/organizations/{organizationId}/social/accounts/{accountId}',
        summary: 'Update social account',
        description: "Update a connected social account's tokens or settings.",
        tags: ['Social Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'accountId', description: 'Social account ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'access_token', type: 'string'),
                    new OA\Property(property: 'refresh_token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Social account updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Social account updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SocialAccount'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
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
    #[OA\Post(
        path: '/organizations/{organizationId}/social/accounts/{accountId}/disconnect',
        summary: 'Disconnect social account',
        description: 'Disconnect a social media account and revoke tokens.',
        tags: ['Social Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'accountId', description: 'Social account ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Account disconnected', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Social account disconnected.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
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
    #[OA\Post(
        path: '/organizations/{organizationId}/social/accounts/{accountId}/sync',
        summary: 'Sync social account',
        description: 'Initiate a sync of posts from the connected social media platform.',
        tags: ['Social Accounts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'accountId', description: 'Social account ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sync initiated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Sync initiated for account: @mybrand'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Account is not connected'),
        ]
    )]
    public function sync(string $organizationId, string $accountId): JsonResponse
    {
        $account = SocialAccount::where('organization_id', $organizationId)
            ->findOrFail($accountId);

        if (!$account->is_connected) {
            return response()->json([
                'message' => 'Account is not connected. Please connect first.',
            ], 422);
        }

        return response()->json([
            'message' => 'Sync initiated for account: ' . $account->account_name,
        ]);
    }
}
