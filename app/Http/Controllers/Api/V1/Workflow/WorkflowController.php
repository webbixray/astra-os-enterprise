<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Workflow;

use App\Application\Workflow\DTOs\CreateWorkflowDTO;
use App\Application\Workflow\UseCases\CreateWorkflowUseCase;
use App\Application\Workflow\UseCases\ActivateWorkflowUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Workflow\StoreWorkflowRequest;
use App\Http\Resources\V1\WorkflowResource;
use App\Infrastructure\Persistence\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkflowController extends Controller
{
    public function __construct(
        private readonly CreateWorkflowUseCase $createWorkflowUseCase,
        private readonly ActivateWorkflowUseCase $activateWorkflowUseCase,
    ) {}

    /**
     * List workflows for an organization.
     */
    public function index(Request $request, int $organizationId): JsonResponse
    {
        $workflows = Workflow::where('organization_id', $organizationId)
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => WorkflowResource::collection($workflows),
            'meta' => [
                'current_page' => $workflows->currentPage(),
                'last_page' => $workflows->lastPage(),
                'total' => $workflows->total(),
            ],
        ]);
    }

    /**
     * Create a new workflow.
     */
    public function store(StoreWorkflowRequest $request, int $organizationId): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CreateWorkflowDTO(
            name: $validated['name'],
            description: $validated['description'] ?? '',
            nodes: $validated['nodes'],
            edges: $validated['edges'],
            organizationId: $organizationId,
            triggers: $validated['triggers'] ?? [],
            variables: $validated['variables'] ?? [],
        );

        $result = $this->createWorkflowUseCase->execute($dto);

        return response()->json([
            'message' => 'Workflow created successfully.',
            'data' => $result->toArray(),
        ], 201);
    }

    /**
     * Show a workflow.
     */
    public function show(int $organizationId, int $workflowId): JsonResponse
    {
        $workflow = Workflow::where('organization_id', $organizationId)
            ->findOrFail($workflowId);

        return response()->json([
            'data' => new WorkflowResource($workflow),
        ]);
    }

    /**
     * Update a workflow.
     */
    public function update(StoreWorkflowRequest $request, int $organizationId, int $workflowId): JsonResponse
    {
        $workflow = Workflow::where('organization_id', $organizationId)
            ->findOrFail($workflowId);

        $validated = $request->validated();

        $workflow->update([
            'name' => $validated['name'] ?? $workflow->name,
            'description' => $validated['description'] ?? $workflow->description,
            'nodes' => $validated['nodes'] ?? $workflow->nodes,
            'edges' => $validated['edges'] ?? $workflow->edges,
            'triggers' => $validated['triggers'] ?? $workflow->triggers,
            'variables' => $validated['variables'] ?? $workflow->variables,
        ]);

        return response()->json([
            'message' => 'Workflow updated successfully.',
            'data' => new WorkflowResource($workflow->fresh()),
        ]);
    }

    /**
     * Activate a workflow.
     */
    public function activate(int $organizationId, int $workflowId): JsonResponse
    {
        $this->activateWorkflowUseCase->execute($workflowId, true);

        return response()->json([
            'message' => 'Workflow activated successfully.',
        ]);
    }

    /**
     * Deactivate a workflow.
     */
    public function deactivate(int $organizationId, int $workflowId): JsonResponse
    {
        $this->activateWorkflowUseCase->execute($workflowId, false);

        return response()->json([
            'message' => 'Workflow deactivated successfully.',
        ]);
    }

    /**
     * Duplicate a workflow.
     */
    public function duplicate(int $organizationId, int $workflowId): JsonResponse
    {
        $workflow = Workflow::where('organization_id', $organizationId)
            ->findOrFail($workflowId);

        $duplicate = $workflow->replicate();
        $duplicate->name = $workflow->name . ' (Copy)';
        $duplicate->status = 'draft';
        $duplicate->save();

        return response()->json([
            'message' => 'Workflow duplicated successfully.',
            'data' => new WorkflowResource($duplicate->fresh()),
        ]);
    }

    /**
     * Delete a workflow.
     */
    public function destroy(int $organizationId, int $workflowId): JsonResponse
    {
        $workflow = Workflow::where('organization_id', $organizationId)
            ->findOrFail($workflowId);

        $workflow->delete();

        return response()->json([
            'message' => 'Workflow deleted successfully.',
        ]);
    }
}
