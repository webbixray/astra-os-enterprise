<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Agent;

use App\Domain\Agent\Entities\Agent;
use App\Domain\Agent\ValueObjects\AgentRole;
use App\Domain\Agent\ValueObjects\AutonomyLevel;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Agent domain entity.
 *
 * Covers agent creation with required and optional fields, role validation
 * via AgentRole value object, capabilities, status/autonomy level changes,
 * serialization round-trip, and edge cases such as invalid role values.
 *
 * Note: The Agent domain entity is a simple data entity without built-in
 * parent hierarchy tracking or domain event recording. Those concerns
 * are handled at the application/infrastructure layer.
 *
 * @package Tests\Unit\Domain\Agent
 */
final class AgentTest extends TestCase
{
    // ---- Helpers ----

    /**
     * Create a default agent for testing.
     *
     * @param array $overrides
     * @return Agent
     */
    private function createAgent(array $overrides = []): Agent
    {
        return new Agent(
            name: $overrides['name'] ?? 'Test Agent',
            role: $overrides['role'] ?? 'specialist',
            description: $overrides['description'] ?? 'A test agent',
            model: $overrides['model'] ?? 'gpt-4o',
            organizationId: $overrides['organizationId'] ?? 1,
            capabilities: $overrides['capabilities'] ?? [],
            configuration: $overrides['configuration'] ?? [],
        );
    }

    // ---- Happy Path: Creation ----

    #[Test]
    public function it_creates_an_agent_with_required_fields(): void
    {
        $agent = $this->createAgent([
            'name' => 'Marketing Analyst',
            'role' => 'analytics_director',
            'description' => 'Handles analytics',
            'model' => 'gpt-4o',
            'organizationId' => 10,
        ]);

        $this->assertInstanceOf(Agent::class, $agent);
        $this->assertSame('Marketing Analyst', $agent->getName());
        $this->assertSame('analytics_director', $agent->getRole());
        $this->assertSame('Handles analytics', $agent->getDescription());
        $this->assertSame('gpt-4o', $agent->getModel());
        $this->assertSame(10, $agent->getOrganizationId());
        $this->assertNull($agent->getId());
    }

    #[Test]
    public function it_creates_agent_with_all_optional_fields(): void
    {
        $agent = new Agent(
            name: 'Full Agent',
            role: 'ceo',
            description: 'CEO agent',
            model: 'gpt-4o',
            organizationId: 1,
            capabilities: ['strategy', 'planning', 'approval'],
            configuration: ['temperature' => 0.7, 'max_tokens' => 4096],
        );

        $this->assertSame(['strategy', 'planning', 'approval'], $agent->getCapabilities());
        $this->assertSame(['temperature' => 0.7, 'max_tokens' => 4096], $agent->getConfiguration());
    }

    #[Test]
    public function it_defaults_capabilities_and_configuration_to_empty(): void
    {
        $agent = $this->createAgent();

        $this->assertSame([], $agent->getCapabilities());
        $this->assertSame([], $agent->getConfiguration());
    }

    #[Test]
    public function it_starts_with_idle_status(): void
    {
        $agent = $this->createAgent();

        $this->assertSame('idle', $agent->getStatus());
    }

    // ---- ID ----

    #[Test]
    public function it_sets_and_gets_id(): void
    {
        $agent = $this->createAgent();
        $agent->setId(42);

        $this->assertSame(42, $agent->getId());
    }

    // ---- Status Changes ----

    #[Test]
    public function it_changes_status(): void
    {
        $agent = $this->createAgent();
        $agent->setStatus('busy');

        $this->assertSame('busy', $agent->getStatus());
    }

    #[Test]
    public function it_updates_timestamp_on_status_change(): void
    {
        $agent = $this->createAgent();
        $originalUpdatedAt = $agent->getUpdatedAt();

        usleep(2000);
        $agent->setStatus('processing');

        $this->assertGreaterThan($originalUpdatedAt, $agent->getUpdatedAt());
    }

    #[Test]
    public function it_accepts_any_status_string(): void
    {
        $agent = $this->createAgent();

        // The domain entity does not validate status values
        $agent->setStatus('custom_status_123');

        $this->assertSame('custom_status_123', $agent->getStatus());
    }

    // ---- AgentRole Value Object ----

    #[Test]
    public function it_creates_agent_role_from_string(): void
    {
        $role = AgentRole::fromString('ceo');

        $this->assertInstanceOf(AgentRole::class, $role);
        $this->assertSame('ceo', $role->getValue());
    }

    #[Test]
    public function it_creates_named_role_constructors(): void
    {
        $this->assertSame('ceo', AgentRole::ceo()->getValue());
        $this->assertSame('marketing_director', AgentRole::marketingDirector()->getValue());
        $this->assertSame('creative_director', AgentRole::creativeDirector()->getValue());
        $this->assertSame('ad_director', AgentRole::adDirector()->getValue());
        $this->assertSame('research_director', AgentRole::researchDirector()->getValue());
        $this->assertSame('analytics_director', AgentRole::analyticsDirector()->getValue());
        $this->assertSame('compliance_director', AgentRole::complianceDirector()->getValue());
        $this->assertSame('workflow_director', AgentRole::workflowDirector()->getValue());
        $this->assertSame('specialist', AgentRole::specialist()->getValue());
    }

    #[Test]
    public function agent_role_returns_label(): void
    {
        $role = AgentRole::marketingDirector();

        $this->assertSame('Marketing Director', $role->getLabel());
        $this->assertSame('CEO', AgentRole::ceo()->getLabel());
        $this->assertSame('Specialist', AgentRole::specialist()->getLabel());
    }

    #[Test]
    public function agent_role_detects_equality(): void
    {
        $a = AgentRole::fromString('ceo');
        $b = AgentRole::ceo();

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function agent_role_detects_inequality(): void
    {
        $a = AgentRole::ceo();
        $b = AgentRole::specialist();

        $this->assertFalse($a->equals($b));
    }

    #[Test]
    public function agent_role_converts_to_string(): void
    {
        $role = AgentRole::complianceDirector();

        $this->assertSame('compliance_director', (string) $role);
    }

    #[Test]
    public function it_throws_for_invalid_agent_role(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid agent role');

        AgentRole::fromString('invalid_role_xyz');
    }

    #[Test]
    public function it_throws_for_empty_agent_role(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AgentRole::fromString('');
    }

    #[Test]
    public function it_normalizes_agent_role_to_lowercase(): void
    {
        $role = AgentRole::fromString('CEO');

        $this->assertSame('ceo', $role->getValue());
    }

    // ---- AutonomyLevel Value Object ----

    #[Test]
    public function it_creates_autonomy_level(): void
    {
        $level = new AutonomyLevel('full_auto');

        $this->assertSame('full_auto', $level->getValue());
        $this->assertSame('Fully Autonomous', $level->getLabel());
    }

    #[Test]
    public function it_defaults_autonomy_level_to_advisory(): void
    {
        $level = new AutonomyLevel();

        $this->assertSame('advisory', $level->getValue());
    }

    #[Test]
    public function autonomy_level_has_named_checks(): void
    {
        $advisory = new AutonomyLevel('advisory');
        $semi = new AutonomyLevel('semi_auto');
        $full = new AutonomyLevel('full_auto');

        $this->assertTrue($advisory->isAdvisory());
        $this->assertFalse($advisory->isSemiAuto());
        $this->assertFalse($advisory->isFullAuto());

        $this->assertTrue($semi->isSemiAuto());
        $this->assertFalse($semi->isFullAuto());

        $this->assertTrue($full->isFullAuto());
    }

    #[Test]
    public function autonomy_level_detects_equality(): void
    {
        $a = new AutonomyLevel('full_auto');
        $b = new AutonomyLevel('full_auto');

        $this->assertTrue($a->equals($b));
    }

    #[Test]
    public function autonomy_level_converts_to_string(): void
    {
        $level = new AutonomyLevel('semi_auto');

        $this->assertSame('semi_auto', (string) $level);
    }

    #[Test]
    public function it_throws_for_invalid_autonomy_level(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid autonomy level');

        new AutonomyLevel('ultra_auto');
    }

    #[Test]
    public function it_normalizes_autonomy_level_to_lowercase(): void
    {
        $level = new AutonomyLevel('FULL_AUTO');

        $this->assertSame('full_auto', $level->getValue());
    }

    // ---- Timestamps ----

    #[Test]
    public function it_has_timestamps_on_creation(): void
    {
        $agent = $this->createAgent();

        $this->assertNotNull($agent->getCreatedAt());
        $this->assertNotNull($agent->getUpdatedAt());
    }

    // ---- Serialization ----

    #[Test]
    public function it_serializes_to_array(): void
    {
        $agent = new Agent(
            name: 'Serializer',
            role: 'ad_director',
            description: 'Serializes well',
            model: 'gpt-4o-mini',
            organizationId: 3,
            capabilities: ['ad_management'],
            configuration: ['budget_cap' => 1000],
        );
        $agent->setId(15);
        $agent->setStatus('busy');

        $array = $agent->toArray();

        $this->assertSame(15, $array['id']);
        $this->assertSame('Serializer', $array['name']);
        $this->assertSame('ad_director', $array['role']);
        $this->assertSame('busy', $array['status']);
        $this->assertSame(3, $array['organization_id']);
        $this->assertIsString($array['created_at']);
        $this->assertIsString($array['updated_at']);
    }

    #[Test]
    public function it_serializes_with_null_id(): void
    {
        $agent = $this->createAgent();
        $array = $agent->toArray();

        $this->assertNull($array['id']);
    }

    // ---- Edge Cases ----

    #[Test]
    public function it_accepts_empty_name(): void
    {
        $agent = $this->createAgent(['name' => '']);

        $this->assertSame('', $agent->getName());
    }

    #[Test]
    public function it_accepts_empty_description(): void
    {
        $agent = $this->createAgent(['description' => '']);

        $this->assertSame('', $agent->getDescription());
    }

    #[Test]
    public function it_accepts_very_long_name(): void
    {
        $longName = str_repeat('A', 500);
        $agent = $this->createAgent(['name' => $longName]);

        $this->assertSame($longName, $agent->getName());
    }

    #[Test]
    public function it_accepts_zero_organization_id(): void
    {
        $agent = $this->createAgent(['organizationId' => 0]);

        $this->assertSame(0, $agent->getOrganizationId());
    }

    #[Test]
    public function it_preserves_created_at_after_status_change(): void
    {
        $agent = $this->createAgent();
        $createdAt = $agent->getCreatedAt();

        $agent->setStatus('offline');

        $this->assertSame($createdAt, $agent->getCreatedAt());
    }
}
