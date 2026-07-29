<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Entities;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: WorkflowEdge
 *
 * Represents a directed connection between two workflow nodes. Edges
 * define the flow of execution through the workflow graph, optionally
 * with a condition label for conditional branching.
 *
 * @package App\Domain\Workflow\Entities
 */
class WorkflowEdge
{
    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $workflowId;

    /**
     * @var string
     */
    private readonly string $sourceNodeId;

    /**
     * @var string
     */
    private readonly string $targetNodeId;

    /**
     * @var string|null
     */
    private ?string $label;

    /**
     * @param UuidInterface $id
     * @param UuidInterface $workflowId
     * @param string        $sourceNodeId
     * @param string        $targetNodeId
     * @param string|null   $label
     */
    public function __construct(
        UuidInterface $id,
        UuidInterface $workflowId,
        string $sourceNodeId,
        string $targetNodeId,
        ?string $label = null
    ) {
        $this->id = $id;
        $this->workflowId = $workflowId;
        $this->sourceNodeId = $sourceNodeId;
        $this->targetNodeId = $targetNodeId;
        $this->label = $label;
    }

    /**
     * Create a new WorkflowEdge.
     *
     * @param UuidInterface $workflowId
     * @param string        $sourceNodeId
     * @param string        $targetNodeId
     * @param string|null   $label
     * @return self
     */
    public static function create(
        UuidInterface $workflowId,
        string $sourceNodeId,
        string $targetNodeId,
        ?string $label = null
    ): self {
        return new self(
            Uuid::uuid4(),
            $workflowId,
            $sourceNodeId,
            $targetNodeId,
            $label
        );
    }

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return UuidInterface
     */
    public function getWorkflowId(): UuidInterface
    {
        return $this->workflowId;
    }

    /**
     * @return string
     */
    public function getSourceNodeId(): string
    {
        return $this->sourceNodeId;
    }

    /**
     * @return string
     */
    public function getTargetNodeId(): string
    {
        return $this->targetNodeId;
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string|null $label
     * @return void
     */
    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }
}
