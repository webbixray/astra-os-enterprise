<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Social Analytics', description: 'Social media analytics — overview, platform breakdown, best times')]
final class SocialAnalyticsController extends Controller
{
    /**
     * Get social analytics overview.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/social/analytics/overview',
        summary: 'Social analytics overview',
        description: 'Aggregated social media analytics including impressions, engagements, and sentiment.',
        tags: ['Social Analytics'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'period', description: 'Analysis period', required: false, schema: new OA\Schema(type: 'string', default: '30d')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Analytics overview',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'period', type: 'string', example: '30d'),
                                new OA\Property(property: 'total_posts', type: 'integer', example: 45),
                                new OA\Property(property: 'total_impressions', type: 'integer', example: 12500),
                                new OA\Property(property: 'total_engagements', type: 'integer', example: 890),
                                new OA\Property(property: 'total_mentions', type: 'integer', example: 23),
                                new OA\Property(
                                    property: 'sentiment_breakdown',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'positive', type: 'integer', example: 15),
                                        new OA\Property(property: 'neutral', type: 'integer', example: 6),
                                        new OA\Property(property: 'negative', type: 'integer', example: 2),
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
    public function overview(Request $request, string $organizationId): JsonResponse
    {
        $period = $request->period ?? '30d';

        return response()->json([
            'data' => [
                'period' => $period,
                'total_posts' => 0,
                'total_impressions' => 0,
                'total_engagements' => 0,
                'total_mentions' => 0,
                'sentiment_breakdown' => [
                    'positive' => 0,
                    'neutral' => 0,
                    'negative' => 0,
                ],
            ],
        ]);
    }

    /**
     * Get analytics breakdown by platform.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/social/analytics/platforms',
        summary: 'Platform breakdown',
        description: 'Social media analytics grouped by platform with engagement rates.',
        tags: ['Social Analytics'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Platform breakdown',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'platform', type: 'string', example: 'twitter'),
                                    new OA\Property(property: 'posts', type: 'integer', example: 12),
                                    new OA\Property(property: 'impressions', type: 'integer', example: 4500),
                                    new OA\Property(property: 'engagement_rate', type: 'number', format: 'float', example: 3.2),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function platformBreakdown(Request $request, string $organizationId): JsonResponse
    {
        return response()->json([
            'data' => [
                ['platform' => 'twitter', 'posts' => 0, 'impressions' => 0, 'engagement_rate' => 0],
                ['platform' => 'linkedin', 'posts' => 0, 'impressions' => 0, 'engagement_rate' => 0],
                ['platform' => 'facebook', 'posts' => 0, 'impressions' => 0, 'engagement_rate' => 0],
                ['platform' => 'instagram', 'posts' => 0, 'impressions' => 0, 'engagement_rate' => 0],
            ],
        ]);
    }

    /**
     * Get best posting times based on historical data.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/social/analytics/best-times',
        summary: 'Best posting times',
        description: 'Recommended posting times per platform based on historical engagement data.',
        tags: ['Social Analytics'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Best posting times',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'platform', type: 'string', example: 'twitter'),
                                    new OA\Property(property: 'times', type: 'array', items: new OA\Items(type: 'string'), example: ['09:00', '12:00', '17:00']),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function bestPostingTimes(string $organizationId): JsonResponse
    {
        return response()->json([
            'data' => [
                ['platform' => 'twitter', 'times' => ['09:00', '12:00', '17:00']],
                ['platform' => 'linkedin', 'times' => ['10:00', '12:00']],
                ['platform' => 'facebook', 'times' => ['13:00', '15:00']],
                ['platform' => 'instagram', 'times' => ['11:00', '15:00', '19:00']],
            ],
        ]);
    }
}
