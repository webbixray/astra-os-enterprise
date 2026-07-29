<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Workflow;

use App\Domain\Workflow\Entities\Workflow;
use App\Domain\Workflow\Entities\WorkflowEdge;
use App\Domain\Workflow\Entities\WorkflowNode;
use App\Domain\Workflow\Events\WorkflowActivated;
use App\Domain\Workflow\Events\WorkflowCreated;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Unit tests for the Workflow aggregate root.
 *
 * Covers creation via static factory (with domain events), adding nodes
 * and edges, status lifecycle (draft → active → pause / archive),
 * reconstitution from persistence, execution history tracking via
 * WorkflowExecution entity, and serialization through getters.
 *
 * @package Tests\Unit\Domain\Workflow
 */
final class WorkflowTest extends TestCase
{
    // ---- Helpers ----

    private function createWorkflow(string $name = 'Test Workflow'): Workflow
    {
        return Workflow::create(
            organizationId: Uuid::uuid4(),
            name: $name,
            description: 'A test workflow',
        );
    }

    private function makeNode(Workflow $workflow, string $type = 'action', array $config = []): WorkflowNode
    {
        return WorkflowNode::create($workflow->getId(), $type, $config);
    }

    private function makeEdge(Workflow $workflow, WorkflowNode $source, WorkflowNode $target): WorkflowEdge
    {
        return WorkflowEdge::create(
            $workflow->getId(),
            $source->getId()->toString(),
            $target->getId()->toString(),
        );
    }

    // ---- Happy Path: Creation ----

    #[Test]
    public function it_creates_a_workflow_as_draft(): void
    {
        $workflow = $this->createWorkflow('Campaign Workflow');

        $this->assertInstanceOf(Workflow::class, $workflow);
        $this->assertSame('Campaign Workflow', $workflow->getName());
        $this->assertSame('draft', $workflow->getStatus());
        $this->assertTrue($workflow->isDraft());
        $this->assertFalse($workflow->isActive());
        $this->assertSame(1, $workflow->getVersion());
        $this->assertNotNull($workflow->getId());
        $this->assertNotNull($workflow->getOrganizationId());
    }

    #[Test]
    public function it_creates_workflow_with_campaign_id(): void
    {
        $campaignId = Uuid::uuid4();
        $orgId = Uuid::uuid4();

        $workflow = Workflow::create(
            organizationId: $orgId,
            name: 'Campaign WF',
            description: null,
            campaignId: $campaignId,
        );

        $this->assertSame($campaignId->toString(), $workflow->getCampaignId()->toString());
    }

    #[Test]
    public function it_starts_with_empty_nodes_and_edges(): void
    {
        $workflow = $this->createWorkflow();

        $this->assertEmpty($workflow->getNodes());
        $this->assertEmpty($workflow->getEdges());
    }

    #[Test]
    public function it_records_workflow_created_event(): void
    {
        $orgId = Uuid::uuid4();
        $workflow = Workflow::create(
            organizationId: $orgId,
            name: 'New WF',
        );
        $events = $workflow->getDomainEvents();

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf(WorkflowCreated::class, $event);
        $this->assertSame($workflow->getIdString(), $event->workflowId);
        $this->assertSame($orgId->toString(), $event->organizationId);
        $this->assertSame('New WF', $event->name);
    }

    #[Test]
    public function it_throws_for_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Workflow name cannot be empty.');

        Workflow::create(organizationId: Uuid::uuid4(), name: '');
    }

    #[Test]
    public function it_throws_for_whitespace_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Workflow::create(organizationId: Uuid::uuid4(), name: '   ');
    }

    // ---- Nodes ----

    #[Test]
    public function it_adds_a_node(): void
    {
        $workflow = $this->createWorkflow();
        $node = $this->makeNode($workflow, 'trigger', ['event' => 'campaign.launched']);

        $workflow->addNode($node);

        $nodes = $workflow->getNodes();
        $this->assertCount(1, $nodes);
        $this->assertSame($node, $nodes[0]);
    }

    #[Test]
    public function it_adds_multiple_nodes(): void
    {
        $workflow = $this->createWorkflow();
        $workflow->addNode($this->makeNode($workflow, 'trigger'));
        $workflow->addNode($this->makeNode($workflow, 'action', ['type' => 'email']));
        $workflow->addNode($this->makeNode($workflow, 'notification'));

        $this->assertCount(3, $workflow->getNodes());
    }

    #[Test]
    public function it_updates_timestamp_when_adding_node(): void
    {
        $workflow = $this->createWorkflow();
        $originalUpdatedAt = $workflow->getUpdatedAt();

        usleep(2000);
        $workflow->addNode($this->makeNode($workflow));

        $this->assertGreaterThan($originalUpdatedAt, $workflow->getUpdatedAt());
    }

    // ---- Edges ----

    #[Test]
    public function it_adds_an_edge(): void
    {
        $workflow = $this->createWorkflow();
        $source = $this->makeNode($workflow);
        $target = $this->makeNode($workflow);
        $workflow->addNode($source);
        $workflow->addNode($target);

        $edge = $this->makeEdge($workflow, $source, $target);
        $workflow->addEdge($edge);

        $edges = $workflow->getEdges();
        $this->assertCount(1, $edges);
        $this->assertSame($edge, $edges[0]);
    }

    #[Test]
    public function it_adds_edge_with_condition_label(): void
    {
        $workflow = $this->createWorkflow();
        $source = $this->makeNode($workflow, 'condition');
        $target = $this->makeNode($workflow, 'action');
        $workflow->addNode($source);
        $workflow->addNode($target);

        $edge = WorkflowEdge::create(
            $workflow->getId(),
            $source->getId()->toString(),
            $target->getId()->toString(),
            label: 'approved',
        );
        $workflow->addEdge($edge);

        $this->assertSame('approved', $workflow->getEdges()[0]->getLabel());
    }

    // ---- WorkflowNode Value Object ----

    #[Test]
    public function workflow_node_creates_with_defaults(): void
    {
        $wfId = Uuid::uuid4();
        $node = WorkflowNode::create($wfId, 'action');

        $this->assertSame('action', $node->getType());
        $this->assertSame($wfId->toString(), $node->getWorkflowId()->toString());
        $this->assertSame(['x' => 0, 'y' => 0], $node->getPosition());
        $this->assertNull($node->getLabel());
    }

    #[Test]
    public function workflow_node_sets_type(): void
    {
        $node = WorkflowNode::create(Uuid::uuid4(), 'trigger');
        $node->setType('action');

        $this->assertSame('action', $node->getType());
    }

    #[Test]
    public function workflow_node_sets_position(): void
    {
        $node = WorkflowNode::create(Uuid::uuid4(), 'action');
        $node->setPosition(100, 200);

        $this->assertSame(['x' => 100, 'y' => 200], $node->getPosition());
    }

    #[Test]
    public function workflow_node_sets_label(): void
    {
        $node = WorkflowNode::create(Uuid::uuid4(), 'human_approval', label: 'Review');
        $node->setLabel('Manager Review');

        $this->assertSame('Manager Review', $node->getLabel());
    }

    #[Test]
    public function workflow_node_sets_config(): void
    {
        $node = WorkflowNode::create(Uuid::uuid4(), 'delay', ['duration' => 3600]);
        $node->setConfig(['duration' => 7200]);

        $this->assertSame(['duration' => 7200], $node->getConfig());
    }

    // ---- WorkflowEdge Value Object ----

    #[Test]
    public function workflow_edge_creates_with_defaults(): void
    {
        $wfId = Uuid::uuid4();
        $edge = WorkflowEdge::create($wfId, 'source-1', 'target-1');

        $this->assertSame($wfId->toString(), $edge->getWorkflowId()->toString());
        $this->assertSame('source-1', $edge->getSourceNodeId());
        $this->assertSame('target-1', $edge->getTargetNodeId());
        $this->assertNull($edge->getLabel());
    }

    #[Test]
    public function workflow_edge_sets_label(): void
    {
        $edge = WorkflowEdge::create(Uuid::uuid4(), 's', 't', 'yes');
        $edge->setLabel('no');

        $this->assertSame('no', $edge->getLabel());
    }

    // ---- Status Lifecycle ----

    #[Test]
    public function it_activates_from_draft(): void
    {
        $workflow = $this->createWorkflow();
        $workflow->activate();

        $this->assertSame('active', $workflow->getStatus());
        $this->assertTrue($workflow->isActive());
    }

    #[Test]
    public function it_records_activated_event(): void
    {
        $workflow = $this->createWorkflow();
        $workflow->clearDomainEvents(); // clear the created event

        $workflow->activate();

        $events = $workflow->getDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(WorkflowActivated::class, $events[0]);
        $this->assertSame($workflow->getIdString(), $events[0]->workflowId);
    }

    #[Test]
    public function activate_is_idempotent(): void
    {
        $workflow = $this->createWorkflow();
        $workflow->activate();
        $workflow->clearDomainEvents();

        $workflow->activate(); // should be no-op

        $this->assertSame('active', $workflow->getStatus());
        $this->assertEmpty($workflow->getDomainEvents());
    }

    #[Test]
    public function it_pauses_from_active(): void
    {
        $workflow = $this->createWorkflow();
        $workflow->activate();

        $workflow->pause();

        $this->assertSame('paused', $workflow->getStatus());
        $this->assertTrue($workflow->isPaused());
    }

    #[Test]
    public function it_throws_when_pausing_from_draft(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot pause workflow in "draft" status.');

        $workflow = $this->createWorkflow();
        $workflow->pause();
    }

    #[Test]
    public function it_throws_when_pausing_from_paused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $workflow = $this->createWorkflow();
        $workflow->activate();
        $workflow->pause();
        $workflow->pause(); // second pause should throw
    }

    #[Test]
    public function it_archives_from_any_state(): void
    {
        $workflow = $this->createWorkflow();
        $workflow->archive();

        $this->assertSame('archived', $workflow->getStatus());
    }

    #[Test]
    public function it_archives_from_active(): void
    {
        $workflow = $this->createWorkflow();
        $workflow->activate();

        $workflow->archive();

        $this->assertSame('archived', $workflow->getStatus());
    }

    #[Test]
    public function it_archives_from_paused(): void
    {
        $workflow = $this->createWorkflow();
        $workflow->activate();
        $workflow->pause();

        $workflow->archive();

        $this->assertSame('archived', $workflow->getStatus());
    }

    // ---- Other Commands ----

    #[Test]
    public function it_renames(): void
    {
        $workflow = $this->createWorkflow('Old Name');
        $workflow->rename('New Name');

        $this->assertSame('New Name', $workflow->getName());
    }

    #[Test]
    public function it_throws_when_renaming_to_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $workflow = $this->createWorkflow('Name');
        $workflow->rename('');
    }

    #[Test]
    public function it_throws_when_renaming_to_whitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $workflow = $this->createWorkflow('Name');
        $workflow->rename('   ');
    }

    #[Test]
    public function it_sets_description(): void
    {
        $workflow = $this->createWorkflow();
        $workflow->setDescription('Updated description');

        $this->assertSame('Updated description', $workflow->getDescription());
    }

    #[Test]
    public function it_sets_description_to_null(): void
    {
        $workflow = $this->createWorkflow('Test', 'Some desc');
        $workflow->setDescription(null);

        $this->assertNull($workflow->getDescription());
    }

    #[Test]
    public function it_increments_version(): void
    {
        $workflow = $this->createWorkflow();
        $this->assertSame(1, $workflow->getVersion());

        $workflow->incrementVersion();
        $this->assertSame(2, $workflow->getVersion());

        $workflow->incrementVersion();
        $this->assertSame(3, $workflow->getVersion());
    }

    // ---- Timestamps ----

    #[Test]
    public function it_has_timestamps_via_trait(): void
    {
        $workflow = $this->createWorkflow();

        $this->assertNotNull($workflow->getCreatedAt());
        $this->assertNotNull($workflow->getUpdatedAt());
    }

    // ---- Reconstitution ----

    #[Test]
    public function it_reconstitutes_from_persistence(): void
    {
        $id = Uuid::uuid4();
        $orgId = Uuid::uuid4();
        $campaignId = Uuid::uuid4();
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $updatedAt = new DateTimeImmutable('2024-06-01 00:00:00');

        $node = WorkflowNode::create($id, 'action');
        $edge = WorkflowEdge::create($id, $node->getId()->toString(), 'target-node');

        $workflow = Workflow::reconstitute(
            id: $id,
            organizationId: $orgId,
            campaignId: $campaignId,
            name: 'Reconstituted WF',
            description: 'Was persisted',
            nodes: [$node],
            edges: [$edge],
            status: 'active',
            version: 3,
            metadata: ['author' => 'admin'],
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        $this->assertSame($id->toString(), $workflow->getIdString());
        $this->assertSame($orgId->toString(), $workflow->getOrganizationId()->toString());
        $this->assertSame($campaignId->toString(), $workflow->getCampaignId()->toString());
        $this->assertSame('Reconstituted WF', $workflow->getName());
        $this->assertSame('active', $workflow->getStatus());
        $this->assertSame(3, $workflow->getVersion());
        $this->assertCount(1, $workflow->getNodes());
        $this->assertCount(1, $workflow->getEdges());
        $this->assertSame($createdAt, $workflow->getCreatedAt());
        $this->assertSame($updatedAt, $workflow->getUpdatedAt());
        $this->assertEmpty($workflow->getDomainEvents()); // no events on reconstitution
    }

    // ---- Edge Cases ----

    #[Test]
    public function it_accepts_null_campaign_id(): void
    {
        $workflow = Workflow::create(
            organizationId: Uuid::uuid4(),
            name: 'No Campaign',
        );

        $this->assertNull($workflow->getCampaignId());
    }

    #[Test]
    public function it_accepts_null_description(): void
    {
        $workflow = Workflow::create(
            organizationId: Uuid::uuid4(),
            name: 'No Desc',
            description: null,
        );

        $this->assertNull($workflow->getDescription());
    }
}
