<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Agent;

use App\Application\Agent\DTOs\CreateAgentDTO;
use App\Application\Agent\UseCases\AssignTaskUseCase;
use App\Application\Agent\UseCases\CreateAgentUseCase;
use App\Application\Agent\UseCases\GetAgentMemoryUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Agent\AssignTaskRequest;
use App\Http\Requests\V1\Agent\StoreAgentRequest;
use App\Http\Resources\V1\AgentResource;
use App\Infrastructure\Persistence\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Agents', description: 'AI agent management — CRUD, task assignment, memory')]
#[OA\Schema(
    schema: 'Agent',
    description: 'AI agent model',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Content Writer'),
        new OA\Property(property: 'role', type: 'string', example: 'writer'),
        new OA\Property(property: 'description', type: 'string', example: 'Generates marketing copy'),
        new OA\Property(property: 'model', type: 'string', example: 'gpt-4o'),
        new OA\Property(property: 'status', type: 'string', enum: ['idle', 'busy', 'error'], example: 'idle'),
        new OA\Property(property: 'organization_id', type: 'integer', example: 1),
        new OA\Property(property: 'capabilities', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'configuration', type: 'object'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class AgentController extends Controller
{
    public function __construct(
        private readonly CreateAgentUseCase $createAgentUseCase,
        private readonly AssignTaskUseCase $assignTaskUseCase,
        private readonly GetAgentMemoryUseCase $getAgentMemoryUseCase,
    ) {}

    /**
     * List agents for an organization.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/agents',
        summary: 'List agents',
        description: 'Return all AI agents for an organization, optionally filtered by role/status.',
        tags: ['Agents'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'role', description: 'Filter by role', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'status', description: 'Filter by status', required: false, schema: new OA\Schema(type: 'string', enum: ['idle', 'busy', 'error'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of agents',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Agent')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request, string $organizationId): JsonResponse
    {
        $agents = Agent::where('organization_id', $organizationId)
            ->when($request->role, fn ($q, $role) => $q->where('role', $role))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => AgentResource::collection($agents),
        ]);
    }

    /**
     * Create a new agent.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/agents',
        summary: 'Create agent',
        description: 'Create a new AI agent with a specific role and capabilities.',
        tags: ['Agents'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'role', 'model'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Content Writer'),
                    new OA\Property(property: 'role', type: 'string', example: 'writer'),
                    new OA\Property(property: 'description', type: 'string', example: 'Generates marketing copy'),
                    new OA\Property(property: 'model', type: 'string', example: 'gpt-4o'),
                    new OA\Property(property: 'capabilities', type: 'array', items: new OA\Items(type: 'string'), example: ['text-generation', 'summarization']),
                    new OA\Property(property: 'configuration', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Agent created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Agent created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Agent'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreAgentRequest $request, string $organizationId): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CreateAgentDTO(
            name: $validated['name'],
            role: $validated['role'],
            description: $validated['description'] ?? '',
            model: $validated['model'],
            organizationId: $organizationId,
            capabilities: $validated['capabilities'] ?? [],
            configuration: $validated['configuration'] ?? [],
        );

        $result = $this->createAgentUseCase->execute($dto);

        return response()->json([
            'message' => 'Agent created successfully.',
            'data' => $result->toArray(),
        ], 201);
    }

    /**
     * Show an agent.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/agents/{agentId}',
        summary: 'Show agent',
        description: 'Return a single agent with its tasks.',
        tags: ['Agents'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'agentId', description: 'Agent ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Agent details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Agent'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(string $organizationId, string $agentId): JsonResponse
    {
        $agent = Agent::with('tasks')
            ->where('organization_id', $organizationId)
            ->findOrFail($agentId);

        return response()->json([
            'data' => new AgentResource($agent),
        ]);
    }

    /**
     * Update an agent.
     */
    #[OA\Put(
        path: '/organizations/{organizationId}/agents/{agentId}',
        summary: 'Update agent',
        description: 'Update an agent's name, description, model, capabilities, or configuration.',
        tags: ['Agents'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'agentId', description: 'Agent ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'model', type: 'string'),
                    new OA\Property(property: 'capabilities', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'configuration', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Agent updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Agent updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Agent'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(StoreAgentRequest $request, string $organizationId, string $agentId): JsonResponse
    {
        $agent = Agent::where('organization_id', $organizationId)
            ->findOrFail($agentId);

        $validated = $request->validated();

        $agent->update([
            'name' => $validated['name'] ?? $agent->name,
            'description' => $validated['description'] ?? $agent->description,
            'model' => $validated['model'] ?? $agent->model,
            'capabilities' => $validated['capabilities'] ?? $agent->capabilities,
            'configuration' => $validated['configuration'] ?? $agent->configuration,
        ]);

        return response()->json([
            'message' => 'Agent updated successfully.',
            'data' => new AgentResource($agent->fresh()),
        ]);
    }

    /**
     * Delete an agent.
     */
    #[OA\Delete(
        path: '/organizations/{organizationId}/agents/{agentId}',
        summary: 'Delete agent',
        description: 'Permanently delete an agent.',
        tags: ['Agents'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'agentId', description: 'Agent ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Agent deleted', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Agent deleted successfully.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(string $organizationId, string $agentId): JsonResponse
    {
        $agent = Agent::where('organization_id', $organizationId)
            ->findOrFail($agentId);

        $agent->delete();

        return response()->json([
            'message' => 'Agent deleted successfully.',
        ]);
    }

    /**
     * Assign a task to an agent.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/agents/{agentId}/tasks',
        summary: 'Assign task',
        description: 'Assign a new task to an AI agent for processing.',
        tags: ['Agents'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'agentId', description: 'Agent ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['type', 'input'],
                properties: [
                    new OA\Property(property: 'type', type: 'string', example: 'text-generation', description: 'Task type'),
                    new OA\Property(property: 'input', type: 'object', description: 'Task input data'),
                    new OA\Property(property: 'parent_task_id', type: 'integer', nullable: true, description: 'Parent task ID for subtasks'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task assigned',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Task assigned successfully.'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function assignTask(AssignTaskRequest $request, string $organizationId, string $agentId): JsonResponse
    {
        $validated = $request->validated();

        $dto = new \App\Application\Agent\DTOs\AssignTaskDTO(
            agentId: $agentId,
            type: $validated['type'],
            input: $validated['input'],
            parentTaskId: $validated['parent_task_id'] ?? null,
        );

        $result = $this->assignTaskUseCase->execute($dto);

        return response()->json([
            'message' => 'Task assigned successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Get agent memory.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/agents/{agentId}/memory',
        summary: 'Get agent memory',
        description: 'Return the agent's stored memory/context data.',
        tags: ['Agents'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'agentId', description: 'Agent ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Agent memory',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function getMemory(string $organizationId, string $agentId): JsonResponse
    {
        $result = $this->getAgentMemoryUseCase->execute($agentId);

        return response()->json(['data' => $result]);
    }

    /**
     * Clear agent memory.
     */
    #[OA\Delete(
        path: '/organizations/{organizationId}/agents/{agentId}/memory',
        summary: 'Clear agent memory',
        description: 'Clear all stored memory and task history for an agent.',
        tags: ['Agents'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'agentId', description: 'Agent ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Memory cleared', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Agent memory cleared successfully.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function clearMemory(string $organizationId, string $agentId): JsonResponse
    {
        $agent = Agent::where('organization_id', $organizationId)->findOrFail($agentId);
        $agent->tasks()->delete();

        return response()->json([
            'message' => 'Agent memory cleared successfully.',
        ]);
    }
}
