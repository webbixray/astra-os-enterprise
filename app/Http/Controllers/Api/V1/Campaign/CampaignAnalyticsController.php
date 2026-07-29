<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Campaign;

use App\Application\Analytics\DTOs\AnalyticsQueryDTO;
use App\Application\Analytics\UseCases\GetCampaignAnalyticsUseCase;
use App\Application\Analytics\UseCases\GenerateReportUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\CampaignAnalytics;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Campaign Analytics', description: 'Campaign analytics & reporting')]
final class CampaignAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetCampaignAnalyticsUseCase $getAnalyticsUseCase,
        private readonly GenerateReportUseCase $generateReportUseCase,
    ) {}

    /**
     * Get analytics for a specific campaign.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/analytics',
        summary: 'Campaign analytics',
        description: 'Return time-series analytics data for a single campaign.',
        tags: ['Campaign Analytics'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'start_date', description: 'Start date', required: false, schema: new OA\Schema(type: 'string', format: 'date', default: '7 days ago')),
            new OA\QueryParameter(name: 'end_date', description: 'End date', required: false, schema: new OA\Schema(type: 'string', format: 'date', default: 'now')),
            new OA\QueryParameter(name: 'granularity', description: 'Data granularity', required: false, schema: new OA\Schema(type: 'string', enum: ['hour', 'day', 'week', 'month'], default: 'day')),
            new OA\QueryParameter(name: 'platform', description: 'Filter by platform', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Analytics data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(Request $request, string $organizationId, string $campaignId): JsonResponse
    {
        $dto = new AnalyticsQueryDTO(
            campaignId: $campaignId,
            startDate: new DateTimeImmutable($request->start_date ?? '7 days ago'),
            endDate: new DateTimeImmutable($request->end_date ?? 'now'),
            granularity: $request->granularity ?? 'day',
            platform: $request->platform,
        );

        $result = $this->getAnalyticsUseCase->execute($dto);

        return response()->json(['data' => $result]);
    }

    /**
     * Get analytics overview for all campaigns in an organization.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/analytics/overview',
        summary: 'Analytics overview',
        description: 'Aggregated analytics overview across all campaigns in an organization.',
        tags: ['Campaign Analytics'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'period', description: 'Analysis period', required: false, schema: new OA\Schema(type: 'string', default: '30d')),
            new OA\QueryParameter(name: 'campaign_id', description: 'Filter by campaign', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aggregated analytics',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_impressions', type: 'integer', example: 150000),
                                new OA\Property(property: 'total_clicks', type: 'integer', example: 4500),
                                new OA\Property(property: 'total_conversions', type: 'integer', example: 230),
                                new OA\Property(property: 'total_spend', type: 'number', format: 'float', example: 12500.00),
                                new OA\Property(property: 'total_revenue', type: 'number', format: 'float', example: 45000.00),
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

        $analytics = CampaignAnalytics::whereHas('campaign', fn ($q) => $q->where('organization_id', $organizationId))
            ->when($request->campaign_id, fn ($q, $id) => $q->where('campaign_id', $id))
            ->selectRaw('
                COALESCE(SUM(impressions), 0) as total_impressions,
                COALESCE(SUM(clicks), 0) as total_clicks,
                COALESCE(SUM(conversions), 0) as total_conversions,
                COALESCE(SUM(spend), 0) as total_spend,
                COALESCE(SUM(revenue), 0) as total_revenue
            ')
            ->first();

        return response()->json(['data' => $analytics]);
    }

    /**
     * Export analytics report.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/campaigns/{campaignId}/analytics/export',
        summary: 'Export analytics',
        description: 'Export campaign analytics report in the requested format.',
        tags: ['Campaign Analytics'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'campaignId', description: 'Campaign ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'start_date', description: 'Start date', required: false, schema: new OA\Schema(type: 'string', format: 'date', default: '30 days ago')),
            new OA\QueryParameter(name: 'end_date', description: 'End date', required: false, schema: new OA\Schema(type: 'string', format: 'date', default: 'now')),
            new OA\QueryParameter(name: 'granularity', description: 'Data granularity', required: false, schema: new OA\Schema(type: 'string', enum: ['hour', 'day', 'week', 'month'], default: 'day')),
            new OA\QueryParameter(name: 'platform', description: 'Filter by platform', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'format', description: 'Export format', required: false, schema: new OA\Schema(type: 'string', enum: ['json', 'csv', 'xlsx'], default: 'json')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Exported report',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function export(Request $request, string $organizationId, string $campaignId): JsonResponse
    {
        $dto = new AnalyticsQueryDTO(
            campaignId: $campaignId,
            startDate: new DateTimeImmutable($request->start_date ?? '30 days ago'),
            endDate: new DateTimeImmutable($request->end_date ?? 'now'),
            granularity: $request->granularity ?? 'day',
            platform: $request->platform,
        );

        $result = $this->generateReportUseCase->execute($dto, $request->format ?? 'json');

        return response()->json(['data' => $result]);
    }
}
