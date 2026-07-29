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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Dashboard', description: 'Dashboard metrics and overview')]
final class DashboardController extends Controller
{
    /**
     * Get overview metrics for the dashboard.
     */
    #[OA\Get(
        path: '/dashboard',
        summary: 'Dashboard overview',
        description: 'Return high-level aggregate metrics across all organizations the user has access to.',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard overview metrics',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_organizations', type: 'integer', example: 3),
                                new OA\Property(property: 'total_campaigns', type: 'integer', example: 12),
                                new OA\Property(property: 'active_campaigns', type: 'integer', example: 5),
                                new OA\Property(property: 'total_agents', type: 'integer', example: 8),
                                new OA\Property(property: 'active_workflows', type: 'integer', example: 4),
                                new OA\Property(property: 'scheduled_posts', type: 'integer', example: 7),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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
    #[OA\Get(
        path: '/dashboard/metrics',
        summary: 'Dashboard detailed metrics',
        description: 'Return detailed breakdown metrics for campaigns, agents, workflows, and social posts.',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detailed dashboard metrics',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'campaigns',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total', type: 'integer', example: 12),
                                        new OA\Property(property: 'draft', type: 'integer', example: 3),
                                        new OA\Property(property: 'active', type: 'integer', example: 5),
                                        new OA\Property(property: 'paused', type: 'integer', example: 2),
                                        new OA\Property(property: 'archived', type: 'integer', example: 2),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'agents',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total', type: 'integer', example: 8),
                                        new OA\Property(property: 'idle', type: 'integer', example: 5),
                                        new OA\Property(property: 'busy', type: 'integer', example: 3),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'workflows',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total', type: 'integer', example: 6),
                                        new OA\Property(property: 'active', type: 'integer', example: 4),
                                        new OA\Property(property: 'draft', type: 'integer', example: 2),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'social',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_posts', type: 'integer', example: 45),
                                        new OA\Property(property: 'published', type: 'integer', example: 30),
                                        new OA\Property(property: 'scheduled', type: 'integer', example: 7),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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
    #[OA\Get(
        path: '/dashboard/activity',
        summary: 'Recent activity',
        description: "Return recent campaign and social post activity across the user's organizations.",
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Recent activity feed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'type', type: 'string', enum: ['campaign', 'social_post']),
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string', nullable: true),
                                    new OA\Property(property: 'content', type: 'string', nullable: true),
                                    new OA\Property(property: 'status', type: 'string'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
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
