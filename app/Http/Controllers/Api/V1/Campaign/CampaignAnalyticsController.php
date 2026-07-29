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

final class CampaignAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetCampaignAnalyticsUseCase $getAnalyticsUseCase,
        private readonly GenerateReportUseCase $generateReportUseCase,
    ) {}

    /**
     * Get analytics for a specific campaign.
     */
    public function show(Request $request, int $organizationId, int $campaignId): JsonResponse
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
    public function overview(Request $request, int $organizationId): JsonResponse
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
    public function export(Request $request, int $organizationId, int $campaignId): JsonResponse
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
