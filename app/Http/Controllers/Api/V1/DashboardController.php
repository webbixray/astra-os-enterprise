<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Agent;
use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\Organization;
use App\Infrastructure\Persistence\Models\SocialPost;
use App\Infrastructure\Persistence\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    /**
     * Get overview metrics for the dashboard.
     */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        $organizationIds = Organization::where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        return response()->json([
            'data' => [
                'total_organizations' => $organizationIds->count(),
                'total_campaigns' => Campaign::whereIn('organization_id', $organizationIds)->count(),
                'active_campaigns' => Campaign::whereIn('organization_id', $organizationIds)
                    ->where('status', 'active')
                    ->count(),
                'total_agents' => Agent::whereIn('organization_id', $organizationIds)->count(),
                'active_workflows' => Workflow::whereIn('organization_id', $organizationIds)
                    ->where('status', 'active')
                    ->count(),
                'scheduled_posts' => SocialPost::whereIn('organization_id', $organizationIds)
                    ->where('status', 'scheduled')
                    ->count(),
            ],
        ]);
    }

    /**
     * Get detailed metrics for the dashboard.
     */
    public function metrics(Request $request): JsonResponse
    {
        $user = $request->user();

        $organizationIds = Organization::where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $campaigns = Campaign::whereIn('organization_id', $organizationIds);

        return response()->json([
            'data' => [
                'campaigns' => [
                    'total' => (clone $campaigns)->count(),
                    'draft' => (clone $campaigns)->where('status', 'draft')->count(),
                    'active' => (clone $campaigns)->where('status', 'active')->count(),
                    'paused' => (clone $campaigns)->where('status', 'paused')->count(),
                    'archived' => (clone $campaigns)->where('status', 'archived')->count(),
                ],
                'agents' => [
                    'total' => Agent::whereIn('organization_id', $organizationIds)->count(),
                    'idle' => Agent::whereIn('organization_id', $organizationIds)->where('status', 'idle')->count(),
                    'busy' => Agent::whereIn('organization_id', $organizationIds)->where('status', 'busy')->count(),
                ],
                'workflows' => [
                    'total' => Workflow::whereIn('organization_id', $organizationIds)->count(),
                    'active' => Workflow::whereIn('organization_id', $organizationIds)->where('status', 'active')->count(),
                    'draft' => Workflow::whereIn('organization_id', $organizationIds)->where('status', 'draft')->count(),
                ],
                'social' => [
                    'total_posts' => SocialPost::whereIn('organization_id', $organizationIds)->count(),
                    'published' => SocialPost::whereIn('organization_id', $organizationIds)->where('status', 'published')->count(),
                    'scheduled' => SocialPost::whereIn('organization_id', $organizationIds)->where('status', 'scheduled')->count(),
                ],
            ],
        ]);
    }

    /**
     * Get recent activity for the dashboard.
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $user = $request->user();

        $organizationIds = Organization::where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $recentCampaigns = Campaign::whereIn('organization_id', $organizationIds)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn ($c) => [
                'type' => 'campaign',
                'id' => $c->id,
                'name' => $c->name,
                'status' => $c->status,
                'updated_at' => $c->updated_at,
            ]);

        $recentPosts = SocialPost::whereIn('organization_id', $organizationIds)
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn ($p) => [
                'type' => 'social_post',
                'id' => $p->id,
                'content' => substr($p->content, 0, 100),
                'status' => $p->status,
                'updated_at' => $p->updated_at,
            ]);

        $activity = $recentCampaigns
            ->concat($recentPosts)
            ->sortByDesc('updated_at')
            ->take(10)
            ->values()
            ->all();

        return response()->json([
            'data' => $activity,
        ]);
    }
}
