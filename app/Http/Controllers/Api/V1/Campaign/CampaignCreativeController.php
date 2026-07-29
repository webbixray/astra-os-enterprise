<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Campaign\StoreCreativeRequest;
use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\Creative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Campaign Creatives', description: 'Campaign creative asset management — CRUD, approve, reject')]
#[OA\Schema(
    schema: 'Creative',
    description: 'Campaign creative asset',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'campaign_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Summer Banner A'),
        new OA\Property(property: 'type', type: 'string', enum: ['image', 'video', 'text', 'carousel'], example: 'image'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'rejected'], example: 'pending'),
        new OA\Property(property: 'url', type: 'string', example: 'https://cdn.example.com/creative.jpg'),
        new OA\Property(property: 'rejection_reason', type: 'string', nullable: true),
        new OA\Property(property: 'approved_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class CampaignCreativeController extends Controller
{
    /**
     * List creatives for a campaign.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/creatives',
        summary: 'List creatives',
        description: 'Return all creative assets for a campaign.',
        tags: ['Campaign Creatives'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of creatives',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Creative')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function index(string $organizationId, string $campaignId): JsonResponse
    {
        Campaign::where('organization_id', $organizationId)->findOrFail($campaignId);

        $creatives = Creative::where('campaign_id', $campaignId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $creatives]);
    }

    /**
     * Create a new creative for a campaign.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/creatives',
        summary: 'Create creative',
        description: 'Upload or link a new creative asset to a campaign.',
        tags: ['Campaign Creatives'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'type'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Summer Banner A'),
                    new OA\Property(property: 'type', type: 'string', enum: ['image', 'video', 'text', 'carousel'], example: 'image'),
                    new OA\Property(property: 'url', type: 'string', format: 'uri', example: 'https://cdn.example.com/creative.jpg'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Creative created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Creative created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Creative'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreCreativeRequest $request, string $organizationId, string $campaignId): JsonResponse
    {
        Campaign::where('organization_id', $organizationId)->findOrFail($campaignId);

        $validated = $request->validated();
        $validated['campaign_id'] = $campaignId;

        $creative = Creative::create($validated);

        return response()->json([
            'message' => 'Creative created successfully.',
            'data' => $creative,
        ], 201);
    }

    /**
     * Show a creative.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/creatives/{creativeId}',
        summary: 'Show creative',
        description: 'Return a single creative asset.',
        tags: ['Campaign Creatives'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'creativeId', description: 'Creative ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Creative details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Creative'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(string $organizationId, string $campaignId, string $creativeId): JsonResponse
    {
        $creative = Creative::where('campaign_id', $campaignId)->findOrFail($creativeId);

        return response()->json(['data' => $creative]);
    }

    /**
     * Update a creative.
     */
    #[OA\Put(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/creatives/{creativeId}',
        summary: 'Update creative',
        description: 'Update a creative asset's properties.',
        tags: ['Campaign Creatives'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'creativeId', description: 'Creative ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'type', type: 'string', enum: ['image', 'video', 'text', 'carousel']),
                    new OA\Property(property: 'url', type: 'string', format: 'uri'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Creative updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Creative updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Creative'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(StoreCreativeRequest $request, string $organizationId, string $campaignId, string $creativeId): JsonResponse
    {
        $creative = Creative::where('campaign_id', $campaignId)->findOrFail($creativeId);

        $creative->update($request->validated());

        return response()->json([
            'message' => 'Creative updated successfully.',
            'data' => $creative->fresh(),
        ]);
    }

    /**
     * Approve a creative.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/creatives/{creativeId}/approve',
        summary: 'Approve creative',
        description: 'Mark a creative as approved for use in campaigns.',
        tags: ['Campaign Creatives'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'creativeId', description: 'Creative ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Creative approved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Creative approved successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Creative'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function approve(string $organizationId, string $campaignId, string $creativeId): JsonResponse
    {
        $creative = Creative::where('campaign_id', $campaignId)->findOrFail($creativeId);
        $creative->update(['status' => 'approved', 'approved_at' => now()]);

        return response()->json([
            'message' => 'Creative approved successfully.',
            'data' => $creative->fresh(),
        ]);
    }

    /**
     * Reject a creative.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/creatives/{creativeId}/reject',
        summary: 'Reject creative',
        description: 'Reject a creative with an optional reason.',
        tags: ['Campaign Creatives'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'creativeId', description: 'Creative ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'reason', type: 'string', example: 'Does not match brand guidelines'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Creative rejected',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Creative rejected.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Creative'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function reject(Request $request, string $organizationId, string $campaignId, string $creativeId): JsonResponse
    {
        $creative = Creative::where('campaign_id', $campaignId)->findOrFail($creativeId);
        $creative->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason ?? 'No reason provided.',
        ]);

        return response()->json([
            'message' => 'Creative rejected.',
            'data' => $creative->fresh(),
        ]);
    }
}
