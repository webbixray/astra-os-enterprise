<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Workflow;

use App\Application\Workflow\DTOs\CreateWorkflowDTO;
use App\Application\Workflow\DTOs\WorkflowResponseDTO;
use App\Application\Workflow\UseCases\CreateWorkflowUseCase;
use App\Domain\Workflow\Entities\Workflow;
use App\Domain\Workflow\Events\WorkflowCreated;
use App\Domain\Workflow\Repositories\WorkflowRepositoryInterface;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateWorkflowUseCase.
 *
 * Tests the workflow creation logic with a mocked repository,
 * including validation of node types and edges.
 */
final class CreateWorkflowUseCaseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Creates a workflow with nodes and edges and returns a WorkflowResponseDTO.
     */
    public function test_creates_workflow_with_nodes_and_edges(): void
    {
        $mockWorkflowRepo = Mockery::mock(WorkflowRepositoryInterface::class);
        $eventMock = Mockery::mock('overload:' . WorkflowCreated::class);

        $dto = new CreateWorkflowDTO(
            name: 'Onboarding Flow',
            description: 'New user onboarding workflow',
            nodes: [
                ['id' => 'start', 'type' => 'trigger', 'config' => ['event' => 'user.signup']],
                ['id' => 'send_email', 'type' => 'action', 'config' => ['template' => 'welcome']],
                ['id' => 'check_status', 'type' => 'condition', 'config' => ['field' => 'email_verified']],
                ['id' => 'complete', 'type' => 'output', 'config' => ['message' => 'Done']],
            ],
            edges: [
                ['from' => 'start', 'to' => 'send_email'],
                ['from' => 'send_email', 'to' => 'check_status'],
                ['from' => 'check_status', 'to' => 'complete'],
            ],
            organizationId: 1,
            triggers: ['user.signup'],
        );

        $mockWorkflowRepo
            ->expects()
            ->save(Mockery::type(Workflow::class))
            ->andReturnUsing(function (Workflow $workflow) {
                return $workflow;
            });

        $eventMock->expects()->dispatch(Mockery::type(Workflow::class));

        $useCase = new CreateWorkflowUseCase($mockWorkflowRepo);

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(WorkflowResponseDTO::class, $result);
        $this->assertSame('Onboarding Flow', $result->name);
    }

    /**
     * Throws an InvalidArgumentException when a node has an invalid type.
     */
    public function test_validates_node_types(): void
    {
        $mockWorkflowRepo = Mockery::mock(WorkflowRepositoryInterface::class);

        $dto = new CreateWorkflowDTO(
            name: 'Bad Flow',
            description: 'Workflow with invalid node type',
            nodes: [
                ['id' => 'n1', 'type' => 'invalid_type'],
                ['id' => 'n2', 'type' => 'action'],
            ],
            edges: [
                ['from' => 'n1', 'to' => 'n2'],
            ],
            organizationId: 1,
        );

        $useCase = new CreateWorkflowUseCase($mockWorkflowRepo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Node 'n1' has invalid type 'invalid_type'.");

        $useCase->execute($dto);
    }

    /**
     * Throws an InvalidArgumentException when the workflow name is empty.
     */
    public function test_throws_when_name_is_empty(): void
    {
        $mockWorkflowRepo = Mockery::mock(WorkflowRepositoryInterface::class);

        $dto = new CreateWorkflowDTO(
            name: '',
            description: 'No name',
            nodes: [],
            edges: [],
            organizationId: 1,
        );

        $useCase = new CreateWorkflowUseCase($mockWorkflowRepo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Workflow name cannot be empty.');

        $useCase->execute($dto);
    }

    /**
     * Throws an InvalidArgumentException when nodes are missing required fields.
     */
    public function test_validates_nodes_have_id_and_type(): void
    {
        $mockWorkflowRepo = Mockery::mock(WorkflowRepositoryInterface::class);

        $dto = new CreateWorkflowDTO(
            name: 'Incomplete Nodes',
            description: 'Nodes missing required fields',
            nodes: [
                ['type' => 'action'], // missing 'id'
            ],
            edges: [],
            organizationId: 1,
        );

        $useCase = new CreateWorkflowUseCase($mockWorkflowRepo);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Node at index 0 is missing required fields 'id' and 'type'.");

        $useCase->execute($dto);
    }

    /**
     * Creates a workflow and verifies the response structure.
     */
    public function test_returns_workflow_response_dto(): void
    {
        $mockWorkflowRepo = Mockery::mock(WorkflowRepositoryInterface::class);
        $eventMock = Mockery::mock('overload:' . WorkflowCreated::class);

        $dto = new CreateWorkflowDTO(
            name: 'Approval Workflow',
            description: 'Multi-step approval process',
            nodes: [
                ['id' => 'step_1', 'type' => 'action', 'config' => []],
                ['id' => 'step_2', 'type' => 'condition', 'config' => []],
                ['id' => 'step_3', 'type' => 'output', 'config' => []],
            ],
            edges: [
                ['from' => 'step_1', 'to' => 'step_2'],
                ['from' => 'step_2', 'to' => 'step_3'],
            ],
            organizationId: 2,
            variables: ['approval_threshold' => 1000],
        );

        $mockWorkflowRepo
            ->expects()
            ->save(Mockery::type(Workflow::class))
            ->andReturnUsing(function (Workflow $workflow) {
                return $workflow;
            });

        $eventMock->expects()->dispatch(Mockery::type(Workflow::class));

        $useCase = new CreateWorkflowUseCase($mockWorkflowRepo);

        $result = $useCase->execute($dto);

        $this->assertInstanceOf(WorkflowResponseDTO::class, $result);
        $this->assertSame('Approval Workflow', $result->name);
        $this->assertNotEmpty($result->nodes);
        $this->assertNotEmpty($result->edges);
        $this->assertSame(['approval_threshold' => 1000], $result->variables);
    }
}
