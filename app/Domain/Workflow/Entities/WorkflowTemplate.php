<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: WorkflowTemplate
 *
 * Represents a reusable workflow template that can be instantiated
 * as a complete Workflow. Templates provide pre-built workflow
 * definitions for common marketing automation patterns.
 *
 * @package App\Domain\Workflow\Entities
 */
class WorkflowTemplate
{
    use HasTimestamps;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string|null
     */
    private ?string $description;

    /**
     * @var string
     */
    private string $category;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $nodes;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $edges;

    /**
     * @var bool
     */
    private bool $isPublished;

    /**
     * @var int
     */
    private int $version;

    /**
     * @param UuidInterface $id
     * @param string        $name
     * @param string|null   $description
     * @param string        $category
     * @param array<int, array<string, mixed>> $nodes
     * @param array<int, array<string, mixed>> $edges
     * @param bool          $isPublished
     * @param int           $version
     */
    private function __construct(
        UuidInterface $id,
        string $name,
        ?string $description,
        string $category,
        array $nodes,
        array $edges,
        bool $isPublished,
        int $version
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->category = $category;
        $this->nodes = $nodes;
        $this->edges = $edges;
        $this->isPublished = $isPublished;
        $this->version = $version;
    }

    /**
     * Create a new WorkflowTemplate.
     *
     * @param string $name
     * @param string $category
     * @param string|null $description
     * @return self
     */
    public static function create(
        string $name,
        string $category = 'general',
        ?string $description = null
    ): self {
        $template = new self(
            Uuid::uuid4(),
            $name,
            $description,
            $category,
            [],
            [],
            false,
            1
        );

        $template->initializeTimestamps();

        return $template;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface $id
     * @param string        $name
     * @param string|null   $description
     * @param string        $category
     * @param array<int, array<string, mixed>> $nodes
     * @param array<int, array<string, mixed>> $edges
     * @param bool          $isPublished
     * @param int           $version
     * @param DateTimeImmutable $createdAt
     * @param DateTimeImmutable $updatedAt
     * @return self
     */
    public static function reconstitute(
        UuidInterface $id,
        string $name,
        ?string $description,
        string $category,
        array $nodes,
        array $edges,
        bool $isPublished,
        int $version,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        $template = new self($id, $name, $description, $category, $nodes, $edges, $isPublished, $version);
        $template->setCreatedAt($createdAt);
        $template->setUpdatedAt($updatedAt);

        return $template;
    }

    /**
     * Publish the template.
     *
     * @return void
     */
    public function publish(): void
    {
        $this->isPublished = true;
        $this->markAsUpdated();
    }

    /**
     * Unpublish the template.
     *
     * @return void
     */
    public function unpublish(): void
    {
        $this->isPublished = false;
        $this->markAsUpdated();
    }

    /**
     * Increment the template version.
     *
     * @return void
     */
    public function incrementVersion(): void
    {
        $this->version++;
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
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEdges(): array
    {
        return $this->edges;
    }

    /**
     * @return bool
     */
    public function getIsPublished(): bool
    {
        return $this->isPublished;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }
}
