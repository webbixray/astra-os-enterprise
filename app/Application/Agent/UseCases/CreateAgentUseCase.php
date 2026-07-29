<?php

declare(strict_types=1);

namespace App\Application\Agent\UseCases;

use App\Application\Agent\DTOs\CreateAgentDTO;
use App\Application\Agent\DTOs\AgentResponseDTO;
use App\Domain\Agent\Entities\Agent;
use App\Domain\Agent\Events\AgentCreated;
use App\Domain\Agent\Repositories\AgentRepositoryInterface;

final readonly class CreateAgentUseCase
{
    public function __construct(
        private AgentRepositoryInterface $agentRepository,
    ) {}

    /**
     * Create a new AI agent.
     *
     * Persists the agent and dispatches the AgentCreated event.
     */
    public function execute(CreateAgentDTO $dto): AgentResponseDTO
    {
        if (empty(trim($dto->name))) {
            throw new \InvalidArgumentException('Agent name cannot be empty.');
        }

        if (empty(trim($dto->role))) {
            throw new \InvalidArgumentException('Agent role cannot be empty.');
        }

        $agent = new Agent(
            name: $dto->name,
            role: $dto->role,
            description: $dto->description,
            model: $dto->model,
            organizationId: $dto->organizationId,
            capabilities: $dto->capabilities,
            configuration: $dto->configuration,
        );

        $agent = $this->agentRepository->save($agent);

        AgentCreated::dispatch($agent);

        return AgentResponseDTO::fromArray($agent->toArray());
    }
}
