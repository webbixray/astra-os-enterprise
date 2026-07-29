<?php

declare(strict_types=1);

namespace App\Domain\Agent\Entities;

use DateTimeImmutable;

final class AgentTask
{
    private ?int $id = null;
    private int $agentId;
    private string $type;
    private string $status;
    private array $input;
    private ?array $output = null;
    private ?string $error = null;
    private ?int $parentTaskId = null;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $completedAt = null;

    public function __construct(
        int $agentId,
        string $type,
        array $input,
        ?int $parentTaskId = null,
    ) {
        $this->agentId = $agentId;
        $this->type = $type;
        $this->input = $input;
        $this->parentTaskId = $parentTaskId;
        $this->status = 'pending';
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getAgentId(): int { return $this->agentId; }
    public function getType(): string { return $this->type; }
    public function getStatus(): string { return $this->status; }
    public function getInput(): array { return $this->input; }
    public function getOutput(): ?array { return $this->output; }
    public function getError(): ?string { return $this->error; }
    public function getParentTaskId(): ?int { return $this->parentTaskId; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }
    public function getCompletedAt(): ?DateTimeImmutable { return $this->completedAt; }

    public function markAsProcessing(): void
    {
        $this->status = 'processing';
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsCompleted(array $output): void
    {
        $this->status = 'completed';
        $this->output = $output;
        $this->completedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function markAsFailed(string $error): void
    {
        $this->status = 'failed';
        $this->error = $error;
        $this->completedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'agent_id' => $this->agentId,
            'type' => $this->type,
            'status' => $this->status,
            'input' => $this->input,
            'output' => $this->output,
            'error' => $this->error,
            'parent_task_id' => $this->parentTaskId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
            'completed_at' => $this->completedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
