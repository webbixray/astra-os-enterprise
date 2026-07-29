<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Agent\Entities\AgentTask as AgentTaskEntity;
use App\Domain\Agent\Repositories\AgentTaskRepositoryInterface;
use App\Infrastructure\Persistence\Models\AgentTask as AgentTaskModel;

final class EloquentAgentTaskRepository implements AgentTaskRepositoryInterface
{
    /**
     * Find a task by its ID.
     */
    public function findById(int $id): ?AgentTaskEntity
    {
        $model = AgentTaskModel::find($id);

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    /**
     * Find tasks by agent ID.
     *
     * @return AgentTaskEntity[]
     */
    public function findByAgentId(int $agentId): array
    {
        return AgentTaskModel::where('agent_id', $agentId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (AgentTaskModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Find tasks by status.
     *
     * @return AgentTaskEntity[]
     */
    public function findByStatus(string $status): array
    {
        return AgentTaskModel::where('status', $status)
            ->get()
            ->map(fn (AgentTaskModel $model) => $this->toEntity($model))
            ->all();
    }

    /**
     * Save a task entity.
     */
    public function save(AgentTaskEntity $task): AgentTaskEntity
    {
        $data = [
            'agent_id' => $task->getAgentId(),
            'type' => $task->getType(),
            'status' => $task->getStatus(),
            'input' => $task->getInput(),
            'output' => $task->getOutput(),
            'error' => $task->getError(),
            'parent_task_id' => $task->getParentTaskId(),
            'completed_at' => $task->getCompletedAt()?->format('Y-m-d H:i:s'),
        ];

        if ($task->getId() !== null) {
            AgentTaskModel::where('id', $task->getId())->update($data);
            $model = AgentTaskModel::find($task->getId());
        } else {
            $model = AgentTaskModel::create($data);
        }

        if ($model === null) {
            throw new \RuntimeException('Failed to save task.');
        }

        $task->setId($model->id);

        return $task;
    }

    /**
     * Delete a task.
     */
    public function delete(int $id): bool
    {
        return (bool) AgentTaskModel::where('id', $id)->delete();
    }

    /**
     * Convert an Eloquent model to a domain entity.
     */
    private function toEntity(AgentTaskModel $model): AgentTaskEntity
    {
        $entity = new AgentTaskEntity(
            agentId: $model->agent_id,
            type: $model->type,
            input: $model->input ?? [],
            parentTaskId: $model->parent_task_id,
        );

        $entity->setId($model->id);

        $reflection = new \ReflectionClass($entity);

        $statusProp = $reflection->getProperty('status');
        $statusProp->setAccessible(true);
        $statusProp->setValue($entity, $model->status);

        if ($model->output !== null) {
            $prop = $reflection->getProperty('output');
            $prop->setAccessible(true);
            $prop->setValue($entity, $model->output);
        }

        if ($model->error !== null) {
            $prop = $reflection->getProperty('error');
            $prop->setAccessible(true);
            $prop->setValue($entity, $model->error);
        }

        if ($model->completed_at !== null) {
            $prop = $reflection->getProperty('completedAt');
            $prop->setAccessible(true);
            $prop->setValue($entity, new \DateTimeImmutable($model->completed_at));
        }

        return $entity;
    }
}
