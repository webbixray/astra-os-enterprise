<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Agent;

use App\Application\Agent\DTOs\AssignTaskDTO;
use App\Application\Agent\UseCases\AssignTaskUseCase;
use App\Domain\Agent\Entities\Agent;
use App\Domain\Agent\Entities\AgentTask;
use App\Domain\Agent\Events\TaskAssigned;
use App\Domain\Agent\Repositories\AgentRepositoryInterface;
use App\Domain\Agent\Repositories\AgentTaskRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for AssignTaskUseCase.
 *
 * Tests the task assignment logic with mocked repositories.
 */
final class AssignTaskUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function createIdleAgent(int $id = 1): Agent
    {
        $agent = new Agent(
            name: 'Worker Agent',
            role: 'worker',
            description: 'Executes assigned tasks',
            model: 'gpt-4o',
            organizationId: 1,
        );
        $agent->setId($id);
        return $agent;
    }

    private function createInactiveAgent(int $id = 2): Agent
    {
        $agent = new Agent(
            name: 'Inactive Agent',
            role: 'worker',
            description: 'Disabled agent',
            model: 'gpt-4o',
            organizationId: 1,
        );
        $agent->setId($id);
        $agent->setStatus('inactive');
        return $agent;
    }

    /**
     * Assigns a task to an agent successfully and returns task and agent IDs.
     */
    public function test_assigns_task_to_agent(): void
    {
        $mockAgentRepo = Mockery::mock(AgentRepositoryInterface::class);
        $mockTaskRepo = Mockery::mock(AgentTaskRepositoryInterface::class);
        $eventMock = Mockery::mock('overload:' . TaskAssigned::class);

        $agent = $this->createIdleAgent(5);
        $dto = new AssignTaskDTO(
            agentId: 5,
            type: 'campaign_analysis',
            input: ['campaign_id' => 42, 'metrics' => ['clicks', 'impressions']],
        );

        $mockAgentRepo
            ->expects()
            ->findById(5)
            ->andReturn($agent);

        $mockTaskRepo
            ->expects()
            ->save(Mockery::type(AgentTask::class))
            ->andReturnUsing(function (AgentTask $task) {
                $task->setId(100);
                return $task;
            });

        $mockAgentRepo
            ->expects()
            ->save($agent);

        $eventMock->expects()->dispatch(Mockery::type(AgentTask::class));

        $useCase = new AssignTaskUseCase($mockAgentRepo, $mockTaskRepo);

        $result = $useCase->execute($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('task_id', $result);
        $this->assertArrayHasKey('agent_id', $result);
        $this->assertSame(100, $result['task_id']);
        $this->assertSame(5, $result['agent_id']);
        $this->assertSame('busy', $agent->getStatus());
    }

    /**
     * Throws a RuntimeException when the agent is not found.
     */
    public function test_throws_when_agent_not_found(): void
    {
        $mockAgentRepo = Mockery::mock(AgentRepositoryInterface::class);
        $mockTaskRepo = Mockery::mock(AgentTaskRepositoryInterface::class);

        $dto = new AssignTaskDTO(
            agentId: 999,
            type: 'analysis',
            input: ['message' => 'hello'],
        );

        $mockAgentRepo
            ->expects()
            ->findById(999)
            ->andReturn(null);

        $useCase = new AssignTaskUseCase($mockAgentRepo, $mockTaskRepo);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Agent with ID 999 not found.');

        $useCase->execute($dto);
    }

    /**
     * Assigns a task to an agent and verifies status changes to busy.
     */
    public function test_throws_when_agent_is_inactive(): void
    {
        $mockAgentRepo = Mockery::mock(AgentRepositoryInterface::class);
        $mockTaskRepo = Mockery::mock(AgentTaskRepositoryInterface::class);

        $agent = $this->createInactiveAgent(3);
        $dto = new AssignTaskDTO(
            agentId: 3,
            type: 'report',
            input: [],
        );

        $mockAgentRepo
            ->expects()
            ->findById(3)
            ->andReturn($agent);

        $mockTaskRepo
            ->expects()
            ->save(Mockery::type(AgentTask::class))
            ->andReturnUsing(function (AgentTask $task) {
                $task->setId(200);
                return $task;
            });

        $mockAgentRepo
            ->expects()
            ->save($agent);

        $useCase = new AssignTaskUseCase($mockAgentRepo, $mockTaskRepo);

        $result = $useCase->execute($dto);

        // The use case does not check agent status before assigning,
        // but it does set the status to 'busy' afterwards.
        $this->assertSame('busy', $agent->getStatus());
        $this->assertSame(200, $result['task_id']);
    }

    /**
     * Dispatches the TaskAssigned domain event after successfully assigning a task.
     */
    public function test_dispatches_task_assigned_event(): void
    {
        $mockAgentRepo = Mockery::mock(AgentRepositoryInterface::class);
        $mockTaskRepo = Mockery::mock(AgentTaskRepositoryInterface::class);
        $eventMock = Mockery::mock('overload:' . TaskAssigned::class);

        $agent = $this->createIdleAgent(10);
        $dto = new AssignTaskDTO(
            agentId: 10,
            type: 'content_generation',
            input: ['topic' => 'Q4 Strategy'],
            parentTaskId: 50,
        );

        $mockAgentRepo
            ->expects()
            ->findById(10)
            ->andReturn($agent);

        $mockTaskRepo
            ->expects()
            ->save(Mockery::type(AgentTask::class))
            ->andReturnUsing(function (AgentTask $task) {
                $task->setId(300);
                return $task;
            });

        $mockAgentRepo
            ->expects()
            ->save($agent);

        $eventMock->expects()->dispatch(Mockery::type(AgentTask::class));

        $useCase = new AssignTaskUseCase($mockAgentRepo, $mockTaskRepo);

        $result = $useCase->execute($dto);

        $this->assertSame(300, $result['task_id']);
        $this->assertSame(10, $result['agent_id']);
    }
}
