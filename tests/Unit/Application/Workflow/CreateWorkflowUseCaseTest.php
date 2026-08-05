<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Workflow;

use Tests\TestCase;
use App\Application\Workflow\DTOs\CreateWorkflowDTO;
use App\Domain\Workflow\ValueObjects\WorkflowStatus;
use Ramsey\Uuid\Uuid;

#[Group('unit')]
class CreateWorkflowUseCaseTest extends TestCase
{
    public function test_create_workflow_dto(): void
    {
        $dto = new CreateWorkflowDTO(
            name: 'Campaign Launch Pipeline',
            description: 'End-to-end campaign launch workflow',
            nodes: [
                [
                    'id' => 'node-1',
                    'type' => 'trigger',
                    'config' => ['event' => 'campaign.created'],
                ],
                [
                    'id' => 'node-2',
                    'type' => 'action',
                    'config' => ['action' => 'create_creatives'],
                ],
                [
                    'id' => 'node-3',
                    'type' => 'approval',
                    'config' => ['approvers' => ['manager@example.com']],
                ],
            ],
            edges: [
                ['from' => 'node-1', 'to' => 'node-2'],
                ['from' => 'node-2', 'to' => 'node-3'],
            ],
            organizationId: Uuid::uuid4(),
            createdBy: Uuid::uuid4(),
        );
        
        $this->assertEquals('Campaign Launch Pipeline', $dto->name);
        $this->assertCount(3, $dto->nodes);
        $this->assertCount(2, $dto->edges);
    }

    public function test_workflow_node_types(): void
    {
        $nodeTypes = ['trigger', 'action', 'condition', 'approval', 'delay', 'notification', 'webhook'];
        
        foreach ($nodeTypes as $type) {
            $dto = new CreateWorkflowDTO(
                name: 'Test',
                description: 'Test',
                nodes: [
                    ['id' => 'node-1', 'type' => $type, 'config' => []],
                ],
                edges: [],
                organizationId: Uuid::uuid4(),
                createdBy: Uuid::uuid4(),
            );
            
            $this->assertEquals($type, $dto->nodes[0]['type']);
        }
    }

    public function test_workflow_edges_connectivity(): void
    {
        $dto = new CreateWorkflowDTO(
            name: 'Test',
            description: 'Test',
            nodes: [
                ['id' => 'start', 'type' => 'trigger', 'config' => []],
                ['id' => 'middle', 'type' => 'action', 'config' => []],
                ['id' => 'end', 'type' => 'action', 'config' => []],
            ],
            edges: [
                ['from' => 'start', 'to' => 'middle'],
                ['from' => 'middle', 'to' => 'end'],
            ],
            organizationId: Uuid::uuid4(),
            createdBy: Uuid::uuid4(),
        );
        
        $this->assertCount(2, $dto->edges);
        $this->assertEquals('start', $dto->edges[0]['from']);
        $this->assertEquals('middle', $dto->edges[0]['to']);
        $this->assertEquals('middle', $dto->edges[1]['from']);
        $this->assertEquals('end', $dto->edges[1]['to']);
    }
}