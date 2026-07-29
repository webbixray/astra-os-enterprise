<?php

declare(strict_types=1);

namespace App\Application\Analytics\Services;

use App\Application\Analytics\DTOs\AnalyticsQueryDTO;
use App\Domain\Campaign\Entities\Campaign;

final class ReportGeneratorService
{
    /**
     * Generate a campaign performance report.
     *
     * @return string The file path of the generated report.
     */
    public function generate(Campaign $campaign, AnalyticsQueryDTO $query, string $format = 'pdf'): string
    {
        $reportDir = storage_path('app/reports');
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }

        $filename = sprintf(
            'campaign_%d_report_%s.%s',
            $campaign->getId(),
            date('Ymd_His'),
            $format,
        );

        $filePath = $reportDir . DIRECTORY_SEPARATOR . $filename;

        $reportData = $this->buildReportData($campaign, $query);
        file_put_contents($filePath, json_encode($reportData, JSON_PRETTY_PRINT));

        return $filePath;
    }

    /**
     * Build the report data structure.
     */
    private function buildReportData(Campaign $campaign, AnalyticsQueryDTO $query): array
    {
        $budget = $campaign->getBudget();

        return [
            'report_metadata' => [
                'campaign_id' => $campaign->getId(),
                'campaign_name' => $campaign->getName(),
                'generated_at' => date('Y-m-d H:i:s'),
                'period' => [
                    'start' => $query->startDate->format('Y-m-d'),
                    'end' => $query->endDate->format('Y-m-d'),
                ],
                'format' => 'json',
            ],
            'campaign_info' => [
                'name' => $campaign->getName(),
                'objective' => $campaign->getObjective(),
                'status' => $campaign->getStatus(),
                'budget' => [
                    'amount' => $budget->getAmount(),
                    'currency' => $budget->getCurrency(),
                ],
                'platforms' => $campaign->getPlatforms(),
                'target_audience' => $campaign->getTargetAudience(),
                'start_date' => $campaign->getStartDate()->format('Y-m-d'),
                'end_date' => $campaign->getEndDate()->format('Y-m-d'),
            ],
            'analytics' => [
                'impressions' => 0,
                'clicks' => 0,
                'conversions' => 0,
                'spend' => 0.0,
                'revenue' => 0.0,
                'ctr' => 0.0,
                'cvr' => 0.0,
                'roas' => 0.0,
                'cpc' => 0.0,
            ],
        ];
    }
}
