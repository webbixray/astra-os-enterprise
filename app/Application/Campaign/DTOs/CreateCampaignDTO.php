<?php

declare(strict_types=1);

namespace App\Application\Campaign\DTOs;

use DateTimeImmutable;

final readonly class CreateCampaignDTO
{
    public function __construct(
        public string $name,
        public string $objective,
        public float $budgetAmount,
        public string $budgetCurrency,
        public array $targetAudience,
        public array $platforms,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public int $organizationId,
        public ?int $createdBy = null,
        public array $metadata = [],
    ) {}
}
