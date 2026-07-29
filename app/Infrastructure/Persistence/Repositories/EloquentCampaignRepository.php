<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Campaign\Entities\Campaign as CampaignEntity;
use App\Domain\Campaign\Repositories\CampaignRepositoryInterface;
use App\Domain\Common\ValueObjects\Money;
use App\Infrastructure\Persistence\Models\Campaign as CampaignModel;
use DateTimeImmutable;

final class EloquentCampaignRepository implements CampaignRepositoryInterface
{
    /**
     * Find a campaign by its ID.
     */
    public function findById(int $id): ?CampaignEntity
    {
        $model = CampaignModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * Find campaigns by organization ID.
     *
     * @return CampaignEntity[]
     */
    public function findByOrganizationId(int $organizationId, array $criteria = []): array
    {
        $query = CampaignModel::where('organization_id', $organizationId);

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['platform'])) {
            $query->whereJsonContains('platforms', $criteria['platform']);
        }

        return $query->get()
            ->map(fn (CampaignModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Find campaigns by status.
     *
     * @return CampaignEntity[]
     */
    public function findByStatus(string $status): array
    {
        return CampaignModel::where('status', $status)
            ->get()
            ->map(fn (CampaignModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Save a campaign entity.
     */
    public function save(CampaignEntity $campaign): CampaignEntity
    {
        $budget = $campaign->getBudget();

        $data = [
            'name' => $campaign->getName(),
            'objective' => $campaign->getObjective(),
            'budget_amount' => $budget->getAmount(),
            'budget_currency' => $budget->getCurrency(),
            'target_audience' => $campaign->getTargetAudience(),
            'platforms' => $campaign->getPlatforms(),
            'start_date' => $campaign->getStartDate()->format('Y-m-d H:i:s'),
            'end_date' => $campaign->getEndDate()->format('Y-m-d H:i:s'),
            'status' => $campaign->getStatus(),
            'metadata' => $campaign->getMetadata(),
            'organization_id' => $campaign->getOrganizationId(),
            'created_by' => $campaign->getCreatedBy(),
            'launched_at' => $campaign->getLaunchedAt()?->format('Y-m-d H:i:s'),
            'paused_at' => $campaign->getPausedAt()?->format('Y-m-d H:i:s'),
            'archived_at' => $campaign->getArchivedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($campaign->getId() !== null) {
            CampaignModel::where('id', $campaign->getId())->update($data);
            $model = CampaignModel::find($campaign->getId());
        } else {
            $model = CampaignModel::create($data);
        }

        if ($model === null) {
            throw new \RuntimeException('Failed to save campaign.');
        }

        $campaign->setId($model->id);

        return $campaign;
    }

    /**
     * Delete a campaign.
     */
    public function delete(int $id): bool
    {
        return (bool) CampaignModel::where('id', $id)->delete();
    }

    /**
     * Count campaigns by organization ID.
     */
    public function countByOrganizationId(int $organizationId): int
    {
        return CampaignModel::where('organization_id', $organizationId)->count();
    }

    /**
     * Convert an Eloquent model to a domain entity.
     */
    private function toEntity(CampaignModel $model): CampaignEntity
    {
        $entity = new CampaignEntity(
            name: $model->name,
            objective: $model->objective,
            budget: new Money((float) $model->budget_amount, $model->budget_currency),
            targetAudience: $model->target_audience ?? [],
            platforms: $model->platforms ?? [],
            startDate: new DateTimeImmutable($model->start_date),
            endDate: new DateTimeImmutable($model->end_date),
            organizationId: $model->organization_id,
            createdBy: $model->created_by,
            metadata: $model->metadata ?? [],
        );

        $entity->setId($model->id);

        // Restore stateful properties via reflection or setter methods
        // In a full implementation, we would use a proper hydrator
        $reflection = new \ReflectionClass($entity);

        $statusProp = $reflection->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($entity, $model->status);

        if ($model->launched_at !== null) {
            $prop = $reflection->getProperty('launchedAt');
            $prop->setAccessible(true);
            $prop->setValue($entity, new DateTimeImmutable($model->launched_at));
        }

        if ($model->paused_at !== null) {
            $prop = $reflection->getProperty('pausedAt');
            $prop->setAccessible(true);
            $prop->setValue($entity, new DateTimeImmutable($model->paused_at));
        }

        if ($model->archived_at !== null) {
            $prop = $reflection->getProperty('archivedAt');
            $prop->setAccessible(true);
            $prop->setValue($entity, new DateTimeImmutable($model->archived_at));
        }

        return $entity;
    }
}
