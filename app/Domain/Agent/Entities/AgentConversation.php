<?php

declare(strict_types=1);

namespace App\Domain\Agent\Entities;

use App\Domain\Common\Traits\HasTimestamps;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Entity: AgentConversation
 *
 * Represents a complete conversation session between a user and an AI
 * agent, or between multiple agents. Captures the full message history,
 * context, and token usage for audit, replay, and billing purposes.
 *
 * @package App\Domain\Agent\Entities
 */
class AgentConversation
{
    use HasTimestamps;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $id;

    /**
     * @var string
     */
    private readonly string $sessionId;

    /**
     * @var UuidInterface
     */
    private readonly UuidInterface $agentId;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $messages;

    /**
     * @var array<string, mixed>
     */
    private array $context;

    /**
     * @var int
     */
    private int $tokensUsed;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $startedAt;

    /**
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $completedAt;

    /**
     * @param UuidInterface                $id
     * @param string                       $sessionId
     * @param UuidInterface                $agentId
     * @param array<int, array<string, mixed>> $messages
     * @param array<string, mixed>        $context
     * @param int                          $tokensUsed
     * @param DateTimeImmutable|null       $startedAt
     * @param DateTimeImmutable|null       $completedAt
     */
    private function __construct(
        UuidInterface $id,
        string $sessionId,
        UuidInterface $agentId,
        array $messages,
        array $context,
        int $tokensUsed,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $completedAt
    ) {
        $this->id = $id;
        $this->sessionId = $sessionId;
        $this->agentId = $agentId;
        $this->messages = $messages;
        $this->context = $context;
        $this->tokensUsed = $tokensUsed;
        $this->startedAt = $startedAt;
        $this->completedAt = $completedAt;
    }

    /**
     * Create a new AgentConversation.
     *
     * @param UuidInterface                $agentId
     * @param string                       $sessionId
     * @param array<string, mixed>         $context
     * @return self
     */
    public static function create(
        UuidInterface $agentId,
        string $sessionId,
        array $context = []
    ): self {
        $sessionId = trim($sessionId);
        if ($sessionId === '') {
            throw new InvalidArgumentException('Session ID cannot be empty.');
        }

        $conversation = new self(
            Uuid::uuid4(),
            $sessionId,
            $agentId,
            [],
            $context,
            0,
            new DateTimeImmutable(),
            null
        );

        $conversation->initializeTimestamps();

        return $conversation;
    }

    /**
     * Reconstitute from persistence.
     *
     * @param UuidInterface                $id
     * @param string                       $sessionId
     * @param UuidInterface                $agentId
     * @param array<int, array<string, mixed>> $messages
     * @param array<string, mixed>        $context
     * @param int                          $tokensUsed
     * @param DateTimeImmutable|null       $startedAt
     * @param DateTimeImmutable|null       $completedAt
     * @param DateTimeImmutable            $createdAt
     * @param DateTimeImmutable            $updatedAt
     * @return self
     */
    public static function reconstitute(
        UuidInterface $id,
        string $sessionId,
        UuidInterface $agentId,
        array $messages,
        array $context,
        int $tokensUsed,
        ?DateTimeImmutable $startedAt,
        ?DateTimeImmutable $completedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        $conversation = new self(
            $id, $sessionId, $agentId, $messages, $context,
            $tokensUsed, $startedAt, $completedAt
        );
        $conversation->setCreatedAt($createdAt);
        $conversation->setUpdatedAt($updatedAt);

        return $conversation;
    }

    /**
     * Add a message to the conversation.
     *
     * @param array<string, mixed> $message
     * @return void
     */
    public function addMessage(array $message): void
    {
        $this->messages[] = $message;
        $this->markAsUpdated();
    }

    /**
     * Update the tokens used count.
     *
     * @param int $tokens
     * @return void
     */
    public function addTokens(int $tokens): void
    {
        $this->tokensUsed += $tokens;
        $this->markAsUpdated();
    }

    /**
     * Update the conversation context.
     *
     * @param array<string, mixed> $context
     * @return void
     */
    public function updateContext(array $context): void
    {
        $this->context = $context;
        $this->markAsUpdated();
    }

    /**
     * End the conversation.
     *
     * @return void
     */
    public function end(): void
    {
        $this->completedAt = new DateTimeImmutable();
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
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @return UuidInterface
     */
    public function getAgentId(): UuidInterface
    {
        return $this->agentId;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return int
     */
    public function getTokensUsed(): int
    {
        return $this->tokensUsed;
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
    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }
}
