<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Agent;

use App\Application\Agent\DTOs\AgentResponseDTO;
use App\Application\Agent\DTOs\CreateAgentDTO;
use App\Application\Agent\UseCases\CreateAgentUseCase;
use App\Domain\Agent\Entities\Agent;
use App\Domain\Agent\Events\AgentCreated;
use App\Domain\Agent\Repositories\AgentRepositoryInterface;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateAgentUseCase.
 *
 * Tests the agent creation logic with a mocked repository.
 */
final class CreateAgentUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Creates an agent with a valid role and returns an AgentResponseDTO.
     */
    public function test_creates_agent_with_role(): void
    {
        $mockAgentRepo = Mockery::mock(AgentRepositoryInterface::class);
        $eventMock = Mockery::mock('overload:' . AgentCreated::class);

        $dto = new CreateAgentDTO(
            name: 'Marketing Assistant',
            role: 'assistant',
            description: 'Helps with campaign management',
            model: 'gpt-4o',
            organizationId: 1,
            capabilities: ['campaign_analysis', 'report_generation'],
            configuration: ['temperature' => 0.7],
        );

        $mockAgentRepo
            ->expects()
            ->save(Mockery::type(Agent::class))
            ->andReturnUsing(function (Agent $agent) {
                $agent->setId(1);
                return $agent;
            });

        $eventMock->expects()->dispatch(Mockery::type(Agent::class));

        $useCase = new CreateAgentUseCase($mockAgentRepo);

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(AgentResponseDTO::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertSame('Marketing Assistant', $result->name);
        $this->assertSame('assistant', $result->role);
    }

    /**
     * Throws an InvalidArgumentException when the role is empty.
     */
    public function test_validates_role_is_not_empty(): void
    {
        $mockAgentRepo = Mockery::mock(AgentRepositoryInterface::class);

        $dto = new CreateAgentDTO(
            name: 'Bad Agent',
            role: '',
            description: 'No role specified',
            model: 'gpt-4o',
            organizationId: 1,
        );

        $useCase = new CreateAgentUseCase($mockAgentRepo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent role cannot be empty.');

        $useCase->execute($dto);
    }

    /**
     * Throws an InvalidArgumentException when the name is empty.
     */
    public function test_validates_name_is_not_empty(): void
    {
        $mockAgentRepo = Mockery::mock(AgentRepositoryInterface::class);

        $dto = new CreateAgentDTO(
            name: '',
            role: 'assistant',
            description: 'No name',
            model: 'gpt-4o',
            organizationId: 1,
        );

        $useCase = new CreateAgentUseCase($mockAgentRepo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Agent name cannot be empty.');

        $useCase->execute($dto);
    }

    /**
     * Creates an agent and validates it returns the correct AgentResponseDTO.
     */
    public function test_returns_agent_response_dto(): void
    {
        $mockAgentRepo = Mockery::mock(AgentRepositoryInterface::class);
        $eventMock = Mockery::mock('overload:' . AgentCreated::class);

        $dto = new CreateAgentDTO(
            name: 'Data Analyst Agent',
            role: 'analyst',
            description: 'Processes and analyzes marketing data',
            model: 'gpt-4o-mini',
            organizationId: 1,
            capabilities: ['data_analysis', 'visualization'],
        );

        $mockAgentRepo
            ->expects()
            ->save(Mockery::type(Agent::class))
            ->andReturnUsing(function (Agent $agent) {
                $agent->setId(3);
                return $agent;
            });

        $eventMock->expects()->dispatch(Mockery::type(Agent::class));

        $useCase = new CreateAgentUseCase($mockAgentRepo);

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(AgentResponseDTO::class, $result);
        $this->assertSame(3, $result->id);
        $this->assertSame('analyst', $result->role);
        $this->assertSame('idle', $result->status);
        $this->assertSame(['data_analysis', 'visualization'], $result->capabilities);
    }
}
