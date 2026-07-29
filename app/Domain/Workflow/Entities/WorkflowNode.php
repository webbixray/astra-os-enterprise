<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Entities;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: WorkflowNode
 *
 * Represents a single node within a workflow graph. Each node has a type
 * (trigger, action, condition, etc.), configuration, and position for
 * visual rendering in the workflow builder.
 *
 * @package App\Domain\Workflow\Entities
 */
class WorkflowNode
{
    /** @var string Node that triggers workflow execution. */
    public const string TYPE_TRIGGER = 'trigger';

    /** @var string Node that performs an action. */
    public const string TYPE_ACTION = 'action';

    /** @var string Node that evaluates a condition for branching. */
    public const string TYPE_CONDITION = 'condition';

    /** @var string Node that introduces a time delay. */
    public const string TYPE_DELAY = 'delay';

    /** @var string Node that requires human approval. */
    public const string TYPE_HUMAN_APPROVAL = 'human_approval';

    /** @var string Node that sends a notification. */
    public const string TYPE_NOTIFICATION = 'notification';

    /** @var array<int, string> Valid node types. */
    public const array VALID_TYPES = [
        self::TYPE_TRIGGER,
        self::TYPE_ACTION,
        self::TYPE_CONDITION,
        self::TYPE_DELAY,
        self::TYPE_HUMAN_APPROVAL,
        self::TYPE_NOTIFICATION,
    ];

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
    private string $type;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @var array{x: int, y: int}
     */
    private array $position;

    /**
     * @var string|null
     */
    private ?string $label;

    /**
     * @param UuidInterface          $id
     * @param UuidInterface          $workflowId
     * @param string                 $type
     * @param array<string, mixed>  $config
     * @param array{x: int, y: int} $position
     * @param string|null            $label
     */
    public function __construct(
        UuidInterface $id,
        UuidInterface $workflowId,
        string $type,
        array $config = [],
        array $position = ['x' => 0, 'y' => 0],
        ?string $label = null
    ) {
        $this->id = $id;
        $this->workflowId = $workflowId;
        $this->type = $type;
        $this->config = $config;
        $this->position = $position;
        $this->label = $label;
    }

    /**
     * Create a new WorkflowNode.
     *
     * @param UuidInterface          $workflowId
     * @param string                 $type
     * @param array<string, mixed>  $config
     * @param array{x: int, y: int} $position
     * @param string|null            $label
     * @return self
     */
    public static function create(
        UuidInterface $workflowId,
        string $type,
        array $config = [],
        array $position = ['x' => 0, 'y' => 0],
        ?string $label = null
    ): self {
        return new self(
            Uuid::uuid4(),
            $workflowId,
            $type,
            $config,
            $position,
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
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @return array{x: int, y: int}
     */
    public function getPosition(): array
    {
        return $this->position;
    }

    /**
     * @param int $x
     * @param int $y
     * @return void
     */
    public function setPosition(int $x, int $y): void
    {
        $this->position = ['x' => $x, 'y' => $y];
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
