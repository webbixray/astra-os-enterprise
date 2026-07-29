<?php

declare(strict_types=1);

namespace App\Application\Analytics\UseCases;

use App\Application\Analytics\DTOs\AnalyticsQueryDTO;
use App\Application\Analytics\Services\ReportGeneratorService;
use App\Domain\Analytics\Events\ReportGenerated;
use App\Domain\Campaign\Repositories\CampaignRepositoryInterface;
use RuntimeException;

final readonly class GenerateReportUseCase
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private ReportGeneratorService $reportGenerator,
    ) {}

    /**
     * Generate a campaign performance report.
     *
     * @return array{ report_type: string, file_path: string, generated_at: string }
     *
     * @throws RuntimeException if campaign is not found.
     */
    public function execute(AnalyticsQueryDTO $dto, string $format = 'pdf'): array
    {
        $campaign = $this->campaignRepository->findById($dto->campaignId);

        if ($campaign === null) {
            throw new RuntimeException("Campaign with ID {$dto->campaignId} not found.");
        }

        $reportPath = $this->reportGenerator->generate(
            campaign: $campaign,
            query: $dto,
            format: $format,
        );

        ReportGenerated::dispatch(
            campaignId: $dto->campaignId,
            reportType: $format,
            filePath: $reportPath,
        );

        return [
            'report_type' => $format,
            'file_path' => $reportPath,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
