<?php

declare(strict_types=1);

namespace App\Application\Analytics\DTOs;

use DateTimeImmutable;

final readonly class AnalyticsQueryDTO
{
    public function __construct(
        public int $campaignId,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public string $granularity = 'day',
        public ?string $platform = null,
        public array $metrics = [],
    ) {}
}
