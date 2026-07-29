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
    public function index(Request $request, int $organizationId): JsonResponse
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
    public function store(StoreAgentRequest $request, int $organizationId): JsonResponse
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
    public function show(int $organizationId, int $agentId): JsonResponse
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
    public function update(StoreAgentRequest $request, int $organizationId, int $agentId): JsonResponse
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
     * Assign a task to an agent.
     */
    public function assignTask(AssignTaskRequest $request, int $organizationId, int $agentId): JsonResponse
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
    public function getMemory(int $organizationId, int $agentId): JsonResponse
    {
        $result = $this->getAgentMemoryUseCase->execute($agentId);

        return response()->json(['data' => $result]);
    }

    /**
     * Clear agent memory.
     */
    public function clearMemory(int $organizationId, int $agentId): JsonResponse
    {
        $agent = Agent::where('organization_id', $organizationId)->findOrFail($agentId);
        $agent->tasks()->delete();

        return response()->json([
            'message' => 'Agent memory cleared successfully.',
        ]);
    }
}
