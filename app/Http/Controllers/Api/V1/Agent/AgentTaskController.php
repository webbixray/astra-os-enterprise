<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Agent;

use App\Application\Agent\UseCases\ProcessTaskUseCase;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AgentTaskResource;
use App\Infrastructure\Persistence\Models\Agent;
use App\Infrastructure\Persistence\Models\AgentTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AgentTaskController extends Controller
{
    public function __construct(
        private readonly ProcessTaskUseCase $processTaskUseCase,
    ) {}

    /**
     * List tasks for an agent.
     */
    public function index(Request $request, string $organizationId, string $agentId): JsonResponse
    {
        Agent::where('organization_id', $organizationId)->findOrFail($agentId);

        $tasks = AgentTask::where('agent_id', $agentId)
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => AgentTaskResource::collection($tasks),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'total' => $tasks->total(),
            ],
        ]);
    }

    /**
     * Show a task.
     */
    public function show(string $organizationId, string $agentId, string $taskId): JsonResponse
    {
        $task = AgentTask::where('agent_id', $agentId)->findOrFail($taskId);

        return response()->json([
            'data' => new AgentTaskResource($task),
        ]);
    }

    /**
     * Retry a failed task.
     */
    public function retry(string $organizationId, string $agentId, string $taskId): JsonResponse
    {
        $task = AgentTask::where('agent_id', $agentId)->findOrFail($taskId);

        if ($task->status !== 'failed') {
            return response()->json([
                'message' => 'Only failed tasks can be retried.',
            ], 422);
        }

        $task->update(['status' => 'pending', 'error' => null]);

        return response()->json([
            'message' => 'Task queued for retry.',
            'data' => new AgentTaskResource($task->fresh()),
        ]);
    }

    /**
     * Cancel a pending task.
     */
    public function cancel(string $organizationId, string $agentId, string $taskId): JsonResponse
    {
        $task = AgentTask::where('agent_id', $agentId)->findOrFail($taskId);

        if (!in_array($task->status, ['pending', 'processing'], true)) {
            return response()->json([
                'message' => 'Only pending or processing tasks can be cancelled.',
            ], 422);
        }

        $task->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Task cancelled.',
            'data' => new AgentTaskResource($task->fresh()),
        ]);
    }
}
