<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Campaign;

use App\Application\Campaign\DTOs\CreateCampaignDTO;
use App\Application\Campaign\UseCases\CreateCampaignUseCase;
use App\Application\Campaign\UseCases\LaunchCampaignUseCase;
use App\Application\Campaign\UseCases\PauseCampaignUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Campaign\StoreCampaignRequest;
use App\Http\Requests\V1\Campaign\UpdateCampaignRequest;
use App\Http\Resources\V1\CampaignResource;
use App\Infrastructure\Persistence\Models\Campaign;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Campaigns', description: 'Campaign management — CRUD, launch, pause, archive, duplicate')]
#[OA\Schema(
    schema: 'Campaign',
    description: 'Campaign model',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Summer Sale 2026'),
        new OA\Property(property: 'objective', type: 'string', example: 'brand_awareness'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'active', 'paused', 'archived'], example: 'draft'),
        new OA\Property(property: 'budget_amount', type: 'number', format: 'float', example: 5000.00),
        new OA\Property(property: 'budget_currency', type: 'string', example: 'USD'),
        new OA\Property(property: 'target_audience', type: 'object', nullable: true),
        new OA\Property(property: 'platforms', type: 'array', items: new OA\Items(type: 'string'), example: ['facebook', 'instagram']),
        new OA\Property(property: 'start_date', type: 'string', format: 'date-time'),
        new OA\Property(property: 'end_date', type: 'string', format: 'date-time'),
        new OA\Property(property: 'organization_id', type: 'integer', example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class CampaignController extends Controller
{
    public function __construct(
        private readonly CreateCampaignUseCase $createCampaignUseCase,
        private readonly LaunchCampaignUseCase $launchCampaignUseCase,
        private readonly PauseCampaignUseCase $pauseCampaignUseCase,
    ) {}

    /**
     * List campaigns for an organization.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/campaigns',
        summary: 'List campaigns',
        description: 'Paginated list of campaigns scoped to an organization.',
        tags: ['Campaigns'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'status', description: 'Filter by status', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'active', 'paused', 'archived'])),
            new OA\QueryParameter(name: 'per_page', description: 'Items per page', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\QueryParameter(name: 'page', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of campaigns',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Campaign')),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request, string $organizationId): JsonResponse
    {
        $campaigns = Campaign::where('organization_id', $organizationId)
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => CampaignResource::collection($campaigns),
            'meta' => [
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
                'per_page' => $campaigns->perPage(),
                'total' => $campaigns->total(),
            ],
        ]);
    }

    /**
     * Create a new campaign.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/campaigns',
        summary: 'Create campaign',
        description: 'Create a new campaign within an organization.',
        tags: ['Campaigns'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'objective', 'budget_amount', 'platforms', 'start_date', 'end_date'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Summer Sale 2026'),
                    new OA\Property(property: 'objective', type: 'string', example: 'brand_awareness'),
                    new OA\Property(property: 'budget_amount', type: 'number', format: 'float', example: 5000.00),
                    new OA\Property(property: 'budget_currency', type: 'string', default: 'USD', example: 'USD'),
                    new OA\Property(property: 'target_audience', type: 'object'),
                    new OA\Property(property: 'platforms', type: 'array', items: new OA\Items(type: 'string'), example: ['facebook', 'instagram']),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time', example: '2026-06-01T00:00:00Z'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date-time', example: '2026-08-31T23:59:59Z'),
                    new OA\Property(property: 'metadata', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Campaign created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Campaign created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Campaign'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreCampaignRequest $request, string $organizationId): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CreateCampaignDTO(
            name: $validated['name'],
            objective: $validated['objective'],
            budgetAmount: (float) $validated['budget_amount'],
            budgetCurrency: $validated['budget_currency'] ?? 'USD',
            targetAudience: $validated['target_audience'] ?? [],
            platforms: $validated['platforms'],
            startDate: new DateTimeImmutable($validated['start_date']),
            endDate: new DateTimeImmutable($validated['end_date']),
            organizationId: $organizationId,
            createdBy: $request->user()->id,
            metadata: $validated['metadata'] ?? [],
        );

        $result = $this->createCampaignUseCase->execute($dto);

        return response()->json([
            'message' => 'Campaign created successfully.',
            'data' => $result->toArray(),
        ], 201);
    }

    /**
     * Show a campaign.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/campaigns/{campaignId}',
        summary: 'Show campaign',
        description: 'Return a single campaign with analytics.',
        tags: ['Campaigns'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Campaign details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Campaign'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(string $organizationId, string $campaignId): JsonResponse
    {
        $campaign = Campaign::with('analytics')
            ->where('organization_id', $organizationId)
            ->findOrFail($campaignId);

        return response()->json([
            'data' => new CampaignResource($campaign),
        ]);
    }

    /**
     * Update a campaign.
     */
    #[OA\Put(
        path: '/organizations/{organizationId}/campaigns/{campaignId}',
        summary: 'Update campaign',
        description: 'Update an existing campaign's properties.',
        tags: ['Campaigns'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'objective', type: 'string'),
                    new OA\Property(property: 'target_audience', type: 'object'),
                    new OA\Property(property: 'platforms', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'start_date', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'metadata', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Campaign updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Campaign updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Campaign'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateCampaignRequest $request, string $organizationId, string $campaignId): JsonResponse
    {
        $campaign = Campaign::where('organization_id', $organizationId)
            ->findOrFail($campaignId);

        $validated = $request->validated();

        $campaign->update([
            'name' => $validated['name'] ?? $campaign->name,
            'objective' => $validated['objective'] ?? $campaign->objective,
            'target_audience' => $validated['target_audience'] ?? $campaign->target_audience,
            'platforms' => $validated['platforms'] ?? $campaign->platforms,
            'start_date' => isset($validated['start_date'])
                ? new DateTimeImmutable($validated['start_date'])
                : $campaign->start_date,
            'end_date' => isset($validated['end_date'])
                ? new DateTimeImmutable($validated['end_date'])
                : $campaign->end_date,
            'metadata' => $validated['metadata'] ?? $campaign->metadata,
        ]);

        return response()->json([
            'message' => 'Campaign updated successfully.',
            'data' => new CampaignResource($campaign->fresh()),
        ]);
    }

    /**
     * Launch a campaign.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/launch',
        summary: 'Launch campaign',
        description: 'Transition campaign status from draft to active.',
        tags: ['Campaigns'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Campaign launched', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Campaign launched successfully.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function launch(string $organizationId, string $campaignId): JsonResponse
    {
        Campaign::where('organization_id', $organizationId)->findOrFail($campaignId);

        $this->launchCampaignUseCase->execute($campaignId);

        return response()->json([
            'message' => 'Campaign launched successfully.',
        ]);
    }

    /**
     * Pause a campaign.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/pause',
        summary: 'Pause campaign',
        description: 'Pause an active campaign.',
        tags: ['Campaigns'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Campaign paused', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Campaign paused successfully.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function pause(string $organizationId, string $campaignId): JsonResponse
    {
        Campaign::where('organization_id', $organizationId)->findOrFail($campaignId);

        $this->pauseCampaignUseCase->execute($campaignId);

        return response()->json([
            'message' => 'Campaign paused successfully.',
        ]);
    }

    /**
     * Archive a campaign.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/archive',
        summary: 'Archive campaign',
        description: 'Archive a campaign. Archived campaigns are read-only.',
        tags: ['Campaigns'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Campaign archived', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Campaign archived successfully.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function archive(string $organizationId, string $campaignId): JsonResponse
    {
        $campaign = Campaign::where('organization_id', $organizationId)
            ->findOrFail($campaignId);

        $campaign->update(['status' => 'archived', 'archived_at' => now()]);

        return response()->json([
            'message' => 'Campaign archived successfully.',
        ]);
    }

    /**
     * Duplicate a campaign.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/duplicate',
        summary: 'Duplicate campaign',
        description: 'Create a copy of an existing campaign as a new draft.',
        tags: ['Campaigns'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Campaign duplicated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Campaign duplicated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Campaign'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function duplicate(string $organizationId, string $campaignId): JsonResponse
    {
        $campaign = Campaign::where('organization_id', $organizationId)
            ->findOrFail($campaignId);

        $duplicate = $campaign->replicate();
        $duplicate->name = $campaign->name . ' (Copy)';
        $duplicate->status = 'draft';
        $duplicate->launched_at = null;
        $duplicate->paused_at = null;
        $duplicate->archived_at = null;
        $duplicate->save();

        return response()->json([
            'message' => 'Campaign duplicated successfully.',
            'data' => new CampaignResource($duplicate->fresh()),
        ]);
    }

    /**
     * Delete a campaign.
     */
    #[OA\Delete(
        path: '/organizations/{organizationId}/campaigns/{campaignId}',
        summary: 'Delete campaign',
        description: 'Permanently delete a campaign.',
        tags: ['Campaigns'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Campaign deleted', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Campaign deleted successfully.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(string $organizationId, string $campaignId): JsonResponse
    {
        $campaign = Campaign::where('organization_id', $organizationId)
            ->findOrFail($campaignId);

        $campaign->delete();

        return response()->json([
            'message' => 'Campaign deleted successfully.',
        ]);
    }
}
