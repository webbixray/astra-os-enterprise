<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Workflow\Entities\Workflow as WorkflowEntity;
use App\Domain\Workflow\Repositories\WorkflowRepositoryInterface;
use App\Infrastructure\Persistence\Models\Workflow as WorkflowModel;

final class EloquentWorkflowRepository implements WorkflowRepositoryInterface
{
    /**
     * Find a workflow by its ID.
     */
    public function findById(int $id): ?WorkflowEntity
    {
        $model = WorkflowModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * Find workflows by organization ID.
     *
     * @return WorkflowEntity[]
     */
    public function findByOrganizationId(int $organizationId): array
    {
        return WorkflowModel::where('organization_id', $organizationId)
            ->get()
            ->map(fn (WorkflowModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Find workflows by status.
     *
     * @return WorkflowEntity[]
     */
    public function findByStatus(string $status): array
    {
        return WorkflowModel::where('status', $status)
            ->get()
            ->map(fn (WorkflowModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Save a workflow entity.
     */
    public function save(WorkflowEntity $workflow): WorkflowEntity
    {
        $data = [
            'name' => $workflow->getName(),
            'description' => $workflow->getDescription(),
            'nodes' => $workflow->getNodes(),
            'edges' => $workflow->getEdges(),
            'triggers' => $workflow->getTriggers(),
            'variables' => $workflow->getVariables(),
            'status' => $workflow->getStatus(),
            'organization_id' => $workflow->getOrganizationId(),
            'version' => $workflow->getVersion(),
        ];

        if ($workflow->getId() !== null) {
            WorkflowModel::where('id', $workflow->getId())->update($data);
            $model = WorkflowModel::find($workflow->getId());
        } else {
            $model = WorkflowModel::create($data);
        }

        if ($model === null) {
            throw new \RuntimeException('Failed to save workflow.');
        }

        $workflow->setId($model->id);

        return $workflow;
    }

    /**
     * Delete a workflow.
     */
    public function delete(int $id): bool
    {
        return (bool) WorkflowModel::where('id', $id)->delete();
    }

    /**
     * Convert an Eloquent model to a domain entity.
     */
    private function toEntity(WorkflowModel $model): WorkflowEntity
    {
        $entity = new WorkflowEntity(
            name: $model->name,
            description: $model->description,
            nodes: $model->nodes ?? [],
            edges: $model->edges ?? [],
            organizationId: $model->organization_id,
            triggers: $model->triggers ?? [],
            variables: $model->variables ?? [],
        );

        $entity->setId($model->id);

        $reflection = new \ReflectionClass($entity);

        $statusProp = $reflection->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($entity, $model->status);

        $versionProp = $reflection->getProperty('version');
        $versionProp->setAccessible(true);
        $versionProp->setValue($entity, $model->version);

        return $entity;
    }
}
