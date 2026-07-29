<?php

declare(strict_types=1);

namespace App\Domain\Agent\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: AgentMemory
 *
 * Represents a discrete piece of knowledge stored by an AI agent.
 * Memories are categorized by type — episodic (experiences), semantic
 * (facts), or procedural (how-to knowledge) — and are assigned an
 * importance score that influences retention and recall priority.
 *
 * @package App\Domain\Agent\Entities
 */
class AgentMemory
{
    use HasTimestamps;

    /** @var string Memory of specific events or experiences. */
    public const string TYPE_EPISODIC = 'episodic';

    /** @var string Memory of general facts and knowledge. */
    public const string TYPE_SEMANTIC = 'semantic';

    /** @var string Memory of procedures and skills. */
    public const string TYPE_PROCEDURAL = 'procedural';

    /** @var array<int, string> Valid memory types. */
    public const array VALID_TYPES = [
        self::TYPE_EPISODIC,
        self::TYPE_SEMANTIC,
        self::TYPE_PROCEDURAL,
    ];

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $agentId;

    /**
     * @var string
     */
    private readonly string $type;

    /**
     * @var string
     */
    private readonly string $key;

    /**
     * @var array<string, mixed>
     */
    private array $content;

    /**
     * @var int
     */
    private int $importance;

    /**
     * @var int
     */
    private int $accessCount;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $lastAccessedAt;

    /**
     * @param UuidInterface             $id
     * @param UuidInterface             $agentId
     * @param string                    $type
     * @param string                    $key
     * @param array<string, mixed>     $content
     * @param int                       $importance
     * @param int                       $accessCount
     * @param DateTimeImmutable|null    $lastAccessedAt
     */
    private function __construct(
        UuidInterface $id,
        UuidInterface $agentId,
        string $type,
        string $key,
        array $content,
        int $importance,
        int $accessCount,
        ?DateTimeImmutable $lastAccessedAt
    ) {
        $this->id = $id;
        $this->agentId = $agentId;
        $this->type = $type;
        $this->key = $key;
        $this->content = $content;
        $this->importance = $importance;
        $this->accessCount = $accessCount;
        $this->lastAccessedAt = $lastAccessedAt;
    }

    /**
     * Create a new AgentMemory.
     *
     * @param UuidInterface            $agentId
     * @param string                   $type
     * @param string                   $key
     * @param array<string, mixed>    $content
     * @param int                      $importance 1-10 scale.
     * @return self
     *
     * @throws InvalidArgumentException
     */
    public static function create(
        UuidInterface $agentId,
        string $type,
        string $key,
        array $content,
        int $importance = 5
    ): self {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid memory type: "%s". Valid types: %s.', $type, implode(', ', self::VALID_TYPES))
            );
        }

        if ($importance < 1 || $importance > 10) {
            throw new InvalidArgumentException('Importance must be between 1 and 10.');
        }

        $key = trim($key);
        if ($key === '') {
            throw new InvalidArgumentException('Memory key cannot be empty.');
        }

        $memory = new self(
            Uuid::uuid4(),
            $agentId,
            $type,
            $key,
            $content,
            $importance,
            0,
            null
        );

        $memory->initializeTimestamps();

        return $memory;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface            $id
     * @param UuidInterface            $agentId
     * @param string                   $type
     * @param string                   $key
     * @param array<string, mixed>    $content
     * @param int                      $importance
     * @param int                      $accessCount
     * @param DateTimeImmutable|null   $lastAccessedAt
     * @param DateTimeImmutable        $createdAt
     * @param DateTimeImmutable        $updatedAt
     * @return self
     */
    public static function reconstitute(
        UuidInterface $id,
        UuidInterface $agentId,
        string $type,
        string $key,
        array $content,
        int $importance,
        int $accessCount,
        ?DateTimeImmutable $lastAccessedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        $memory = new self(
            $id, $agentId, $type, $key, $content,
            $importance, $accessCount, $lastAccessedAt
        );
        $memory->setCreatedAt($createdAt);
        $memory->setUpdatedAt($updatedAt);

        return $memory;
    }

    /**
     * Record access to this memory, incrementing the access count.
     *
     * @return void
     */
    public function recordAccess(): void
    {
        $this->accessCount++;
        $this->lastAccessedAt = new DateTimeImmutable();
        $this->markAsUpdated();
    }

    /**
     * Update the memory content.
     *
     * @param array<string, mixed> $content
     * @return void
     */
    public function updateContent(array $content): void
    {
        $this->content = $content;
        $this->markAsUpdated();
    }

    /**
     * Update the importance score.
     *
     * @param int $importance
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function setImportance(int $importance): void
    {
        if ($importance < 1 || $importance > 10) {
            throw new InvalidArgumentException('Importance must be between 1 and 10.');
        }
        $this->importance = $importance;
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
    public function getAgentId(): UuidInterface
    {
        return $this->agentId;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getImportance(): int
    {
        return $this->importance;
    }

    /**
     * @return int
     */
    public function getAccessCount(): int
    {
        return $this->accessCount;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getLastAccessedAt(): ?DateTimeImmutable
    {
        return $this->lastAccessedAt;
    }
}
