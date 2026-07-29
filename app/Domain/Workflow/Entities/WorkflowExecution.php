<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: WorkflowExecution
 *
 * Represents a single execution run of a workflow. Tracks the trigger
 * that started it, the current status, which node is actively being
 * processed, and the contextual data flowing through the workflow.
 *
 * @package App\Domain\Workflow\Entities
 */
class WorkflowExecution
{
    use HasTimestamps;

    /** @var string Execution is currently running. */
    public const string STATUS_RUNNING = 'running';

    /** @var string Execution completed successfully. */
    public const string STATUS_COMPLETED = 'completed';

    /** @var string Execution failed during processing. */
    public const string STATUS_FAILED = 'failed';

    /** @var string Execution is blocked awaiting external input. */
    public const string STATUS_BLOCKED = 'blocked';

    /** @var array<int, string> Valid execution statuses. */
    public const array VALID_STATUSES = [
        self::STATUS_RUNNING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
        self::STATUS_BLOCKED,
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
    private readonly string $trigger;

    /**
     * @var string
     */
    private string $status;

    /**
     * @var string|null
     */
    private ?string $currentNodeId;

    /**
     * @var array<string, mixed>
     */
    private array $context;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $startedAt;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $completedAt;

    /**
     * @param UuidInterface             $id
     * @param UuidInterface             $workflowId
     * @param string                    $trigger
     * @param string                    $status
     * @param string|null               $currentNodeId
     * @param array<string, mixed>     $context
     * @param DateTimeImmutable|null    $startedAt
     * @param DateTimeImmutable|null    $completedAt
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $workflowId,
        string $trigger,
        string $status,
        ?string $currentNodeId,
        array $context,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $completedAt
    ) {
        $this->id = $id;
        $this->workflowId = $workflowId;
        $this->trigger = $trigger;
        $this->status = $status;
        $this->currentNodeId = $currentNodeId;
        $this->context = $context;
        $this->startedAt = $startedAt;
        $this->completedAt = $completedAt;
    }

    /**
     * Create a new WorkflowExecution.
     *
     * @param UuidInterface         $workflowId
     * @param string                $trigger
     * @param array<string, mixed> $context
     * @return self
     */
    public static function create(
        UuidInterface $workflowId,
        string $trigger,
        array $context = []
    ): self {
        $execution = new self(
            Uuid::uuid4(),
            $workflowId,
            $trigger,
            self::STATUS_RUNNING,
            null,
            $context,
            new DateTimeImmutable(),
            null
        );

        $execution->initializeTimestamps();

        return $execution;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface             $id
     * @param UuidInterface             $workflowId
     * @param string                    $trigger
     * @param string                    $status
     * @param string|null               $currentNodeId
     * @param array<string, mixed>     $context
     * @param DateTimeImmutable|null    $startedAt
     * @param DateTimeImmutable|null    $completedAt
     * @param DateTimeImmutable         $createdAt
     * @param DateTimeImmutable         $updatedAt
     * @return self
     */
    public static function reconstitute(
        UuidInterface $id,
        UuidInterface $workflowId,
        string $trigger,
        string $status,
        ?string $currentNodeId,
        array $context,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $completedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        $execution = new self(
            $id, $workflowId, $trigger, $status,
            $currentNodeId, $context, $startedAt, $completedAt
        );
        $execution->setCreatedAt($createdAt);
        $execution->setUpdatedAt($updatedAt);

        return $execution;
    }

    /**
     * Advance the execution to the next node.
     *
     * @param string $nodeId
     * @return void
     */
    public function advanceToNode(string $nodeId): void
    {
        $this->currentNodeId = $nodeId;
        $this->markAsUpdated();
    }

    /**
     * Mark the execution as completed.
     *
     * @return void
     */
    public function complete(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new DateTimeImmutable();
        $this->markAsUpdated();
    }

    /**
     * Mark the execution as failed.
     *
     * @return void
     */
    public function fail(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->completedAt = new DateTimeImmutable();
        $this->markAsUpdated();
    }

    /**
     * Mark the execution as blocked.
     *
     * @return void
     */
    public function block(): void
    {
        $this->status = self::STATUS_BLOCKED;
        $this->markAsUpdated();
    }

    /**
     * Update the execution context.
     *
     * @param array<string, mixed> $context
     * @return void
     */
    public function updateContext(array $context): void
    {
        $this->context = $context;
        $this->markAsUpdated();
    }

    // ---- Getters ----

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
    public function getTrigger(): string
    {
        return $this->trigger;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getCurrentNodeId(): ?string
    {
        return $this->currentNodeId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    /**
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }
}
