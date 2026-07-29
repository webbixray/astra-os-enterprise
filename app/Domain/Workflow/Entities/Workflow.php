<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Entities;

use App\Domain\Common\Contracts\AggregateRoot;
use App\Domain\Common\Traits\HasDomainEvents;
use App\Domain\Common\Traits\HasTimestamps;
use App\Domain\Workflow\Events\WorkflowActivated;
use App\Domain\Workflow\Events\WorkflowCreated;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Aggregate Root: Workflow
 *
 * Represents an automated workflow or pipeline within the Astra OS domain.
 * Workflows consist of interconnected nodes (triggers, actions, conditions,
 * delays, approvals, notifications) that form a directed graph defining
 * an automated business process.
 *
 * @package App\Domain\Workflow\Entities
 */
class Workflow implements AggregateRoot
{
    use HasDomainEvents;
    use HasTimestamps;

    /** @var string Workflow is in draft state. */
    public const string STATUS_DRAFT = 'draft';

    /** @var string Workflow is active and running. */
    public const string STATUS_ACTIVE = 'active';

    /** @var string Workflow has been paused. */
    public const string STATUS_PAUSED = 'paused';

    /** @var string Workflow has been archived. */
    public const string STATUS_ARCHIVED = 'archived';

    /** @var array<int, string> Valid statuses. */
    public const array VALID_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_PAUSED,
        self::STATUS_ARCHIVED,
    ];

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $organizationId;

    /**
     * @var UuidInterface|null
     */
    private readonly ?UuidInterface $campaignId;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string|null
     */
    private ?string $description;

    /**
     * @var array<int, WorkflowNode>
     */
    private array $nodes;

    /**
     * @var array<int, WorkflowEdge>
     */
    private array $edges;

    /**
     * @var string
     */
    private string $status;

    /**
     * @var int
     */
    private int $version;

    /**
     * @var array<string, mixed>
     */
    private array $metadata;

    /**
     * @param UuidInterface              $id
     * @param UuidInterface              $organizationId
     * @param UuidInterface|null         $campaignId
     * @param string                     $name
     * @param string|null                $description
     * @param array<int, WorkflowNode>   $nodes
     * @param array<int, WorkflowEdge>   $edges
     * @param string                     $status
     * @param int                        $version
     * @param array<string, mixed>      $metadata
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $organizationId,
        ?UuidInterface $campaignId,
        string $name,
        ?string $description,
        array $nodes,
        array $edges,
        string $status,
        int $version,
        array $metadata
    ) {
        $this->id = $id;
        $this->organizationId = $organizationId;
        $this->campaignId = $campaignId;
        $this->name = $name;
        $this->description = $description;
        $this->nodes = $nodes;
        $this->edges = $edges;
        $this->status = $status;
        $this->version = $version;
        $this->metadata = $metadata;
    }

    /**
     * Create a new Workflow aggregate root.
     *
     * @param UuidInterface              $organizationId
     * @param string                     $name
     * @param string|null                $description
     * @param UuidInterface|null         $campaignId
     * @param array<int, WorkflowNode>   $nodes
     * @param array<int, WorkflowEdge>   $edges
     * @return self
     */
    public static function create(
        UuidInterface $organizationId,
        string $name,
        ?string $description = null,
        ?UuidInterface $campaignId = null,
        array $nodes = [],
        array $edges = []
    ): self {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Workflow name cannot be empty.');
        }

        $workflow = new self(
            Uuid::uuid4(),
            $organizationId,
            $campaignId,
            $name,
            $description,
            $nodes,
            $edges,
            self::STATUS_DRAFT,
            1,
            []
        );

        $workflow->initializeTimestamps();
        $workflow->recordDomainEvent(
            new WorkflowCreated(
                workflowId: $workflow->getIdString(),
                organizationId: $organizationId->toString(),
                name: $name
            )
        );

        return $workflow;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface              $id
     * @param UuidInterface              $organizationId
     * @param UuidInterface|null         $campaignId
     * @param string                     $name
     * @param string|null                $description
     * @param array<int, WorkflowNode>   $nodes
     * @param array<int, WorkflowEdge>   $edges
     * @param string                     $status
     * @param int                        $version
     * @param array<string, mixed>      $metadata
     * @param DateTimeImmutable          $createdAt
     * @param DateTimeImmutable          $updatedAt
     * @return self
     */
    public static function reconstitute(
        UuidInterface $id,
        UuidInterface $organizationId,
        ?UuidInterface $campaignId,
        string $name,
        ?string $description,
        array $nodes,
        array $edges,
        string $status,
        int $version,
        array $metadata,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        $workflow = new self(
            $id, $organizationId, $campaignId, $name, $description,
            $nodes, $edges, $status, $version, $metadata
        );
        $workflow->setCreatedAt($createdAt);
        $workflow->setUpdatedAt($updatedAt);

        return $workflow;
    }

    // ---- Commands ----

    /**
     * Activate the workflow.
     *
     * @return void
     */
    public function activate(): void
    {
        if ($this->status === self::STATUS_ACTIVE) {
            return;
        }

        $this->status = self::STATUS_ACTIVE;
        $this->markAsUpdated();

        $this->recordDomainEvent(
            new WorkflowActivated(
                workflowId: $this->getIdString()
            )
        );
    }

    /**
     * Pause the workflow.
     *
     * @return void
     */
    public function pause(): void
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            throw new InvalidArgumentException(
                sprintf('Cannot pause workflow in "%s" status.', $this->status)
            );
        }
        $this->status = self::STATUS_PAUSED;
        $this->markAsUpdated();
    }

    /**
     * Archive the workflow.
     *
     * @return void
     */
    public function archive(): void
    {
        $this->status = self::STATUS_ARCHIVED;
        $this->markAsUpdated();
    }

    /**
     * Add a node to the workflow.
     *
     * @param WorkflowNode $node
     * @return void
     */
    public function addNode(WorkflowNode $node): void
    {
        $this->nodes[] = $node;
        $this->markAsUpdated();
    }

    /**
     * Add an edge to the workflow.
     *
     * @param WorkflowEdge $edge
     * @return void
     */
    public function addEdge(WorkflowEdge $edge): void
    {
        $this->edges[] = $edge;
        $this->markAsUpdated();
    }

    /**
     * Increment the workflow version.
     *
     * @return void
     */
    public function incrementVersion(): void
    {
        $this->version++;
        $this->markAsUpdated();
    }

    /**
     * Update the workflow name.
     *
     * @param string $name
     * @return void
     */
    public function rename(string $name): void
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('Workflow name cannot be empty.');
        }
        $this->name = $name;
        $this->markAsUpdated();
    }

    /**
     * Update the workflow description.
     *
     * @param string|null $description
     * @return void
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
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
     * @return string
     */
    public function getIdString(): string
    {
        return $this->id->toString();
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * @return UuidInterface|null
     */
    public function getCampaignId(): ?UuidInterface
    {
        return $this->campaignId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array<int, WorkflowNode>
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return array<int, WorkflowEdge>
     */
    public function getEdges(): array
    {
        return $this->edges;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return bool
     */
    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }
}
