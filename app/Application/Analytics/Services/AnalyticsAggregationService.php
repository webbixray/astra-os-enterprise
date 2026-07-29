<?php

declare(strict_types=1);

namespace App\Application\Analytics\Services;

use DateTimeImmutable;

final class AnalyticsAggregationService
{
    /**
     * Aggregate analytics data for a campaign.
     *
     * @return array{ time_series: array, totals: array, platforms: array }
     */
    public function aggregate(
        int $campaignId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $granularity = 'day',
        ?string $platform = null,
    ): array {
        $timeSeries = $this->buildTimeSeries($campaignId, $startDate, $endDate, $granularity, $platform);
        $totals = $this->calculateTotals($timeSeries);
        $platforms = $this->aggregateByPlatform($campaignId, $startDate, $endDate, $platform);

        return [
            'time_series' => $timeSeries,
            'totals' => $totals,
            'platforms' => $platforms,
        ];
    }

    /**
     * Summarize analytics data with key performance indicators.
     *
     * @return array{ impressions: int, clicks: int, conversions: int, spend: float, revenue: float, ctr: float, cvr: float, roas: float, cpc: float }
     */
    public function summarize(array $analytics): array
    {
        $totals = $analytics['totals'] ?? [];

        $impressions = $totals['impressions'] ?? 0;
        $clicks = $totals['clicks'] ?? 0;
        $conversions = $totals['conversions'] ?? 0;
        $spend = $totals['spend'] ?? 0.0;
        $revenue = $totals['revenue'] ?? 0.0;

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'spend' => $spend,
            'revenue' => $revenue,
            'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0,
            'cvr' => $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0.0,
            'roas' => $spend > 0 ? round($revenue / $spend, 2) : 0.0,
            'cpc' => $spend > 0 ? round($spend / $clicks, 4) : 0.0,
        ];
    }

    private function buildTimeSeries(
        int $campaignId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        string $granularity,
        ?string $platform,
    ): array {
        $series = [];
        $current = $startDate;

        $interval = match ($granularity) {
            'hour' => 'PT1H',
            'day' => 'P1D',
            'week' => 'P7D',
            'month' => 'P1M',
            default => 'P1D',
        };

        while ($current <= $endDate) {
            $series[] = [
                'date' => $current->format('Y-m-d H:i:s'),
                'impressions' => 0,
                'clicks' => 0,
                'conversions' => 0,
                'spend' => 0.0,
                'revenue' => 0.0,
            ];

            $current = $current->add(new \DateInterval($interval));
        }

        return $series;
    }

    private function calculateTotals(array $timeSeries): array
    {
        $totals = [
            'impressions' => 0,
            'clicks' => 0,
            'conversions' => 0,
            'spend' => 0.0,
            'revenue' => 0.0,
        ];

        foreach ($timeSeries as $point) {
            $totals['impressions'] += $point['impressions'] ?? 0;
            $totals['clicks'] += $point['clicks'] ?? 0;
            $totals['conversions'] += $point['conversions'] ?? 0;
            $totals['spend'] += $point['spend'] ?? 0.0;
            $totals['revenue'] += $point['revenue'] ?? 0.0;
        }

        return $totals;
    }

    private function aggregateByPlatform(
        int $campaignId,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $platform,
    ): array {
        return [];
    }
}
