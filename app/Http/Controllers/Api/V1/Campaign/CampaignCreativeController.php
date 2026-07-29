<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Campaign\StoreCreativeRequest;
use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\Creative;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CampaignCreativeController extends Controller
{
    /**
     * List creatives for a campaign.
     */
    public function index(int $organizationId, int $campaignId): JsonResponse
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
    public function store(StoreCreativeRequest $request, int $organizationId, int $campaignId): JsonResponse
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
    public function show(int $organizationId, int $campaignId, int $creativeId): JsonResponse
    {
        $creative = Creative::where('campaign_id', $campaignId)->findOrFail($creativeId);

        return response()->json(['data' => $creative]);
    }

    /**
     * Update a creative.
     */
    public function update(StoreCreativeRequest $request, int $organizationId, int $campaignId, int $creativeId): JsonResponse
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
    public function approve(int $organizationId, int $campaignId, int $creativeId): JsonResponse
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
    public function reject(Request $request, int $organizationId, int $campaignId, int $creativeId): JsonResponse
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
