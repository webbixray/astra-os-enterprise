<?php

declare(strict_types=1);

namespace App\Domain\Campaign\Events;

/**
 * Domain Event: BudgetUpdated
 *
 * Fired when a campaign's budget is modified, carrying the old
 * and new values for audit trail and notification purposes.
 *
 * @package App\Domain\Campaign\Events
 */
final class BudgetUpdated
{
    /**
     * @param string     $campaignId
     * @param int|null   $oldBudget  Previous budget in minor units, or null if not set.
     * @param int        $newBudget  New budget in minor units.
     * @param string     $currency   ISO 4217 currency code.
     */
    public function __construct(
        public readonly string $campaignId,
        public readonly ?int $oldBudget,
        public readonly int $newBudget,
        public readonly string $currency
    ) {
    }
}
