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
