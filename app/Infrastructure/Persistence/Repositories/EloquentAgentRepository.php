<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Agent\Entities\Agent as AgentEntity;
use App\Domain\Agent\Repositories\AgentRepositoryInterface;
use App\Infrastructure\Persistence\Models\Agent as AgentModel;

final class EloquentAgentRepository implements AgentRepositoryInterface
{
    /**
     * Find an agent by its ID.
     */
    public function findById(int $id): ?AgentEntity
    {
        $model = AgentModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * Find agents by organization ID.
     *
     * @return AgentEntity[]
     */
    public function findByOrganizationId(int $organizationId): array
    {
        return AgentModel::where('organization_id', $organizationId)
            ->get()
            ->map(fn (AgentModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Find agents by role.
     *
     * @return AgentEntity[]
     */
    public function findByRole(string $role): array
    {
        return AgentModel::where('role', $role)
            ->get()
            ->map(fn (AgentModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Save an agent entity.
     */
    public function save(AgentEntity $agent): AgentEntity
    {
        $data = [
            'name' => $agent->getName(),
            'role' => $agent->getRole(),
            'description' => $agent->getDescription(),
            'model' => $agent->getModel(),
            'capabilities' => $agent->getCapabilities(),
            'configuration' => $agent->getConfiguration(),
            'status' => $agent->getStatus(),
            'organization_id' => $agent->getOrganizationId(),
        ];

        if ($agent->getId() !== null) {
            AgentModel::where('id', $agent->getId())->update($data);
            $model = AgentModel::find($agent->getId());
        } else {
            $model = AgentModel::create($data);
        }

        if ($model === null) {
            throw new \RuntimeException('Failed to save agent.');
        }

        $agent->setId($model->id);

        return $agent;
    }

    /**
     * Delete an agent.
     */
    public function delete(int $id): bool
    {
        return (bool) AgentModel::where('id', $id)->delete();
    }

    /**
     * Count agents by organization ID.
     */
    public function countByOrganizationId(int $organizationId): int
    {
        return AgentModel::where('organization_id', $organizationId)->count();
    }

    /**
     * Convert an Eloquent model to a domain entity.
     */
    private function toEntity(AgentModel $model): AgentEntity
    {
        $entity = new AgentEntity(
            name: $model->name,
            role: $model->role,
            description: $model->description,
            model: $model->model,
            organizationId: $model->organization_id,
            capabilities: $model->capabilities ?? [],
            configuration: $model->configuration ?? [],
        );

        $entity->setId($model->id);
        $entity->setStatus($model->status);

        return $entity;
    }
}
