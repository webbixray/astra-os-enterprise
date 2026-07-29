<?php

declare(strict_types=1);

namespace App\Domain\Analytics\Events;

use DateTimeImmutable;

/**
 * Domain Event: ReportGenerated
 *
 * Fired when an analytics report has been generated and is ready
 * for download or distribution.
 *
 * @package App\Domain\Analytics\Events
 */
final class ReportGenerated
{
    /**
     * @param string            $reportId
     * @param string            $organizationId
     * @param string            $name
     * @param string            $format
     * @param DateTimeImmutable $generatedAt
     */
    public function __construct(
        public readonly string $reportId,
        public readonly string $organizationId,
        public readonly string $name,
        public readonly string $format,
        public readonly DateTimeImmutable $generatedAt
    ) {
    }
}
