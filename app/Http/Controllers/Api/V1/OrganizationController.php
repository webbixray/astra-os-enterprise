<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Organization\DTOs\CreateOrganizationDTO;
use App\Application\Organization\UseCases\CreateOrganizationUseCase;
use App\Application\Organization\UseCases\InviteMemberUseCase;
use App\Application\Organization\UseCases\UpdateOrganizationSettingsUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Organization\InviteMemberRequest;
use App\Http\Requests\V1\Organization\StoreOrganizationRequest;
use App\Http\Requests\V1\Organization\UpdateOrganizationRequest;
use App\Http\Resources\V1\OrganizationResource;
use App\Infrastructure\Persistence\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Organizations', description: 'Organization management — CRUD, members, and settings')]
#[OA\Schema(
    schema: 'Organization',
    description: 'Organization model',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Acme Corp'),
        new OA\Property(property: 'slug', type: 'string', example: 'acme-corp'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'A leading marketing company'),
        new OA\Property(property: 'logo', type: 'string', nullable: true, example: 'logos/acme.png'),
        new OA\Property(property: 'website', type: 'string', nullable: true, example: 'https://acme.com'),
        new OA\Property(property: 'owner_id', type: 'integer', example: 1),
        new OA\Property(property: 'settings', type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class OrganizationController extends Controller
{
    public function __construct(
        private readonly CreateOrganizationUseCase $createOrganizationUseCase,
        private readonly InviteMemberUseCase $inviteMemberUseCase,
        private readonly UpdateOrganizationSettingsUseCase $updateOrganizationSettingsUseCase,
    ) {}

    /**
     * List organizations for the authenticated user.
     */
    #[OA\Get(
        path: '/organizations',
        summary: 'List organizations',
        description: 'Return all organizations the authenticated user owns or is a member of.',
        tags: ['Organizations'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of organizations',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Organization')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizations = Organization::where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => OrganizationResource::collection($organizations),
        ]);
    }

    /**
     * Create a new organization.
     */
    #[OA\Post(
        path: '/organizations',
        summary: 'Create organization',
        description: 'Create a new organization owned by the authenticated user.',
        tags: ['Organizations'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'slug'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Acme Corp'),
                    new OA\Property(property: 'slug', type: 'string', maxLength: 255, example: 'acme-corp'),
                    new OA\Property(property: 'description', type: 'string', example: 'A leading marketing company'),
                    new OA\Property(property: 'logo', type: 'string', example: 'logos/acme.png'),
                    new OA\Property(property: 'website', type: 'string', example: 'https://acme.com'),
                    new OA\Property(property: 'settings', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Organization created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Organization created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Organization'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CreateOrganizationDTO(
            name: $validated['name'],
            slug: $validated['slug'],
            ownerId: $request->user()->id,
            description: $validated['description'] ?? null,
            logo: $validated['logo'] ?? null,
            website: $validated['website'] ?? null,
            settings: $validated['settings'] ?? [],
        );

        $result = $this->createOrganizationUseCase->execute($dto);

        return response()->json([
            'message' => 'Organization created successfully.',
            'data' => $result->toArray(),
        ], 201);
    }

    /**
     * Show an organization.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}',
        summary: 'Show organization',
        description: 'Return a single organization by ID.',
        tags: ['Organizations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Organization details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Organization'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(string $id): JsonResponse
    {
        $organization = Organization::with('owner')->findOrFail($id);

        Gate::authorize('view', $organization);

        return response()->json([
            'data' => new OrganizationResource($organization),
        ]);
    }

    /**
     * Update an organization.
     */
    #[OA\Put(
        path: '/organizations/{organizationId}',
        summary: 'Update organization',
        description: 'Update an existing organization's properties.',
        tags: ['Organizations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'logo', type: 'string'),
                    new OA\Property(property: 'website', type: 'string'),
                    new OA\Property(property: 'settings', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Organization updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Organization updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Organization'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateOrganizationRequest $request, string $id): JsonResponse
    {
        $organization = Organization::findOrFail($id);
        Gate::authorize('update', $organization);

        $validated = $request->validated();

        $organization->update([
            'name' => $validated['name'] ?? $organization->name,
            'description' => $validated['description'] ?? $organization->description,
            'logo' => $validated['logo'] ?? $organization->logo,
            'website' => $validated['website'] ?? $organization->website,
            'settings' => $validated['settings'] ?? $organization->settings,
        ]);

        return response()->json([
            'message' => 'Organization updated successfully.',
            'data' => new OrganizationResource($organization->fresh()),
        ]);
    }

    /**
     * Invite a member to the organization.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/members',
        summary: 'Invite member',
        description: 'Invite a user to become a member of the organization.',
        tags: ['Organizations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 2, description: 'User ID to invite'),
                    new OA\Property(property: 'role', type: 'string', enum: ['member', 'admin'], example: 'member', default: 'member'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Member invited',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Member invited successfully.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Organization not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function inviteMember(InviteMemberRequest $request, string $id): JsonResponse
    {
        $organization = Organization::findOrFail($id);
        Gate::authorize('update', $organization);

        $validated = $request->validated();

        $this->inviteMemberUseCase->execute(
            organizationId: $id,
            userId: $validated['user_id'],
            role: $validated['role'] ?? 'member',
        );

        return response()->json([
            'message' => 'Member invited successfully.',
        ]);
    }

    /**
     * Remove a member from the organization.
     */
    #[OA\Delete(
        path: '/organizations/{organizationId}/members/{memberId}',
        summary: 'Remove member',
        description: 'Remove a member from the organization. Cannot remove the owner.',
        tags: ['Organizations'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'memberId', description: 'Member user ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Member removed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Member removed successfully.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Cannot remove the owner'),
        ]
    )]
    public function removeMember(Request $request, string $id, string $memberId): JsonResponse
    {
        $organization = Organization::findOrFail($id);
        Gate::authorize('update', $organization);

        if ($organization->owner_id === $memberId) {
            return response()->json([
                'message' => 'Cannot remove the organization owner.',
            ], 422);
        }

        $organization->members()->detach($memberId);

        return response()->json([
            'message' => 'Member removed successfully.',
        ]);
    }
}
