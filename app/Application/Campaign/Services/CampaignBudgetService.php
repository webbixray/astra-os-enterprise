<?php

declare(strict_types=1);

namespace App\Application\Campaign\Services;

use App\Domain\Common\ValueObjects\Money;
use InvalidArgumentException;
use RuntimeException;

final class CampaignBudgetService
{
    private const float MIN_BUDGET_PER_PLATFORM = 10.0;
    private const float MAX_BUDGET = 1_000_000.0;

    /** @var array<int, array{budget: Money, allocated: array<string, Money>}> */
    private array $allocations = [];

    public function validateBudget(Money $budget, array $platforms): void
    {
        if ($budget->getAmount() <= 0) {
            throw new InvalidArgumentException('Budget must be greater than zero.');
        }

        if ($budget->getAmount() > self::MAX_BUDGET) {
            throw new InvalidArgumentException(
                sprintf('Budget exceeds maximum of %s %s.', self::MAX_BUDGET, $budget->getCurrency())
            );
        }

        $minRequired = self::MIN_BUDGET_PER_PLATFORM * max(count($platforms), 1);
        if ($budget->getAmount() < $minRequired) {
            throw new InvalidArgumentException(
                sprintf(
                    'Budget of %s %s is insufficient. Minimum required: %s %s.',
                    $budget->getAmount(),
                    $budget->getCurrency(),
                    $minRequired,
                    $budget->getCurrency()
                )
            );
        }
    }

    /**
     * @return array<string, float>
     */
    public function allocateBudget(int $campaignId, Money $totalBudget): array
    {
        $this->validateBudget($totalBudget, []);
        $allocations = [];

        $this->allocations[$campaignId] = [
            'budget' => $totalBudget,
            'allocated' => $allocations,
        ];

        return $allocations;
    }

    public function calculateDailyPacing(Money $budget, int $daysDuration): float
    {
        if ($daysDuration <= 0) {
            throw new InvalidArgumentException('Campaign duration must be at least 1 day.');
        }

        return round($budget->getAmount() / $daysDuration, 2);
    }

    public function checkOverspendRisk(int $campaignId, float $currentSpend, float $remainingBudget): void
    {
        $spendRatio = $remainingBudget > 0
            ? $currentSpend / ($currentSpend + $remainingBudget)
            : 1.0;

        if ($spendRatio > 0.95) {
            throw new RuntimeException(
                sprintf(
                    'Overspend risk detected for campaign %d: %.1f%% of budget consumed.',
                    $campaignId,
                    $spendRatio * 100
                )
            );
        }
    }
}
