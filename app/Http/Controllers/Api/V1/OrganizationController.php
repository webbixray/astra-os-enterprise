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
    public function show(int $id): JsonResponse
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
    public function update(UpdateOrganizationRequest $request, int $id): JsonResponse
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
    public function inviteMember(InviteMemberRequest $request, int $id): JsonResponse
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
    public function removeMember(Request $request, int $id, int $memberId): JsonResponse
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
