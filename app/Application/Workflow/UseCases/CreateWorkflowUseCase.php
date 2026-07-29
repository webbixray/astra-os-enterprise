<?php

declare(strict_types=1);

namespace App\Application\Workflow\UseCases;

use App\Application\Workflow\DTOs\CreateWorkflowDTO;
use App\Application\Workflow\DTOs\WorkflowResponseDTO;
use App\Domain\Workflow\Entities\Workflow;
use App\Domain\Workflow\Events\WorkflowCreated;
use App\Domain\Workflow\Repositories\WorkflowRepositoryInterface;

final readonly class CreateWorkflowUseCase
{
    public function __construct(
        private WorkflowRepositoryInterface $workflowRepository,
    ) {}

    /**
     * Create a new workflow.
     *
     * Validates the workflow structure, persists it, and dispatches
     * the WorkflowCreated event.
     */
    public function execute(CreateWorkflowDTO $dto): WorkflowResponseDTO
    {
        if (empty(trim($dto->name))) {
            throw new \InvalidArgumentException('Workflow name cannot be empty.');
        }

        $this->validateNodes($dto->nodes);
        $this->validateEdges($dto->nodes, $dto->edges);

        $workflow = new Workflow(
            name: $dto->name,
            description: $dto->description,
            nodes: $dto->nodes,
            edges: $dto->edges,
            organizationId: $dto->organizationId,
            triggers: $dto->triggers,
            variables: $dto->variables,
        );

        $workflow = $this->workflowRepository->save($workflow);

        WorkflowCreated::dispatch($workflow);

        return WorkflowResponseDTO::fromArray($workflow->toArray());
    }

    /**
     * Validate that all nodes have required fields.
     */
    private function validateNodes(array $nodes): void
    {
        foreach ($nodes as $index => $node) {
            if (!isset($node['id']) || !isset($node['type'])) {
                throw new \InvalidArgumentException(
                    "Node at index {$index} is missing required fields 'id' and 'type'."
                );
            }

            if (!in_array($node['type'], ['action', 'condition', 'trigger', 'output'], true)) {
                throw new \InvalidArgumentException(
                    "Node '{$node['id']}' has invalid type '{$node['type']}'. " .
                    'Must be one of: action, condition, trigger, output.'
                );
            }
        }
    }

    /**
     * Validate that all edges reference valid nodes.
     */
    private function validateEdges(array $nodes, array $edges): void
    {
        $nodeIds = array_column($nodes, 'id');

        foreach ($edges as $index => $edge) {
            if (!isset($edge['from']) || !isset($edge['to'])) {
                throw new \InvalidArgumentException(
                    "Edge at index {$index} is missing required fields 'from' and 'to'."
                );
            }

            if (!in_array($edge['from'], $nodeIds, true)) {
                throw new \InvalidArgumentException(
                    "Edge at index {$index} references non-existent source node '{$edge['from']}'."
                );
            }

            if (!in_array($edge['to'], $nodeIds, true)) {
                throw new \InvalidArgumentException(
                    "Edge at index {$index} references non-existent target node '{$edge['to']}'."
                );
            }
        }
    }
}
