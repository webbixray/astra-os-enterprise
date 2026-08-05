<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Agent;

use Tests\TestCase;
use App\Application\Agent\DTOs\CreateAgentDTO;
use App\Application\Agent\UseCases\CreateAgentUseCase;
use App\Domain\Agent\ValueObjects\AutonomyLevel;
use App\Domain\Agent\ValueObjects\AgentRole;
use Ramsey\Uuid\Uuid;

#[Group('unit')]
class CreateAgentUseCaseTest extends TestCase
{
    public function test_create_agent_dto_validation(): void
    {
        $dto = new CreateAgentDTO(
            name: 'Test Agent',
            role: AgentRole::SPECIALIST,
            autonomyLevel: AutonomyLevel::SUPERVISED,
            modelConfig: [
                'provider' => 'openai',
                'model' => 'gpt-4o-mini',
                'temperature' => 0.7,
            ],
            parentAgentId: null,
            organizationId: Uuid::uuid4(),
            configuration: [
                'max_tokens' => 4096,
                'tools' => ['web_search', 'code_execution'],
            ]
        );
        
        $this->assertEquals('Test Agent', $dto->name);
        $this->assertEquals(AgentRole::SPECIALIST, $dto->role);
        $this->assertEquals(AutonomyLevel::SUPERVISED, $dto->autonomyLevel);
        $this->assertEquals('openai', $dto->modelConfig['provider']);
    }

    public function test_create_agent_with_parent(): void
    {
        $parentId = Uuid::uuid4();
        
        $dto = new CreateAgentDTO(
            name: 'Child Agent',
            role: AgentRole::SPECIALIST,
            autonomyLevel: AutonomyLevel::SUPERVISED,
            modelConfig: ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
            parentAgentId: $parentId,
            organizationId: Uuid::uuid4(),
            configuration: []
        );
        
        $this->assertEquals($parentId, $dto->parentAgentId);
    }

    public function test_autonomy_level_values(): void
    {
        $levels = [
            AutonomyLevel::FULL_AUTONOMY,
            AutonomyLevel::SUPERVISED,
            AutonomyLevel::APPROVAL_REQUIRED,
            AutonomyLevel::READ_ONLY,
        ];
        
        foreach ($levels as $level) {
            $dto = new CreateAgentDTO(
                name: 'Test',
                role: AgentRole::SPECIALIST,
                autonomyLevel: $level,
                modelConfig: ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
                parentAgentId: null,
                organizationId: Uuid::uuid4(),
                configuration: []
            );
            
            $this->assertEquals($level, $dto->autonomyLevel);
        }
    }

    public function test_agent_role_values(): void
    {
        $roles = [
            AgentRole::CEO,
            AgentRole::DIRECTOR,
            AgentRole::SPECIALIST,
            AgentRole::COORDINATOR,
        ];
        
        foreach ($roles as $role) {
            $dto = new CreateAgentDTO(
                name: 'Test',
                role: $role,
                autonomyLevel: AutonomyLevel::SUPERVISED,
                modelConfig: ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
                parentAgentId: null,
                organizationId: Uuid::uuid4(),
                configuration: []
            );
            
            $this->assertEquals($role, $dto->role);
        }
    }
}