<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Workflow;

use App\Application\Workflow\UseCases\ExecuteWorkflowUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Workflow\ExecuteWorkflowRequest;
use App\Infrastructure\Persistence\Models\Workflow;
use App\Infrastructure\Persistence\Models\WorkflowExecution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkflowExecutionController extends Controller
{
    public function __construct(
        private readonly ExecuteWorkflowUseCase $executeWorkflowUseCase,
    ) {}

    /**
     * List executions for a workflow.
     */
    public function index(int $organizationId, int $workflowId): JsonResponse
    {
        Workflow::where('organization_id', $organizationId)->findOrFail($workflowId);

        $executions = WorkflowExecution::where('workflow_id', $workflowId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'data' => $executions,
            'meta' => [
                'current_page' => $executions->currentPage(),
                'last_page' => $executions->lastPage(),
                'total' => $executions->total(),
            ],
        ]);
    }

    /**
     * Show a specific execution.
     */
    public function show(int $organizationId, int $workflowId, int $executionId): JsonResponse
    {
        $execution = WorkflowExecution::where('workflow_id', $workflowId)
            ->findOrFail($executionId);

        return response()->json(['data' => $execution]);
    }

    /**
     * Execute a workflow.
     */
    public function execute(ExecuteWorkflowRequest $request, int $organizationId, int $workflowId): JsonResponse
    {
        Workflow::where('organization_id', $organizationId)->findOrFail($workflowId);

        $validated = $request->validated();

        $result = $this->executeWorkflowUseCase->execute(
            workflowId: $workflowId,
            inputVariables: $validated['variables'] ?? [],
        );

        return response()->json([
            'message' => 'Workflow executed successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Cancel a running execution.
     */
    public function cancel(int $organizationId, int $workflowId, int $executionId): JsonResponse
    {
        $execution = WorkflowExecution::where('workflow_id', $workflowId)
            ->where('id', $executionId)
            ->whereIn('status', ['pending', 'running'])
            ->firstOrFail();

        $execution->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Execution cancelled.',
        ]);
    }
}
