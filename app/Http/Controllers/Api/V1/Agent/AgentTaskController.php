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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Agent Tasks', description: 'Agent task management — list, show, retry, cancel')]
#[OA\Schema(
    schema: 'AgentTask',
    description: 'Agent task model',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'agent_id', type: 'integer', example: 1),
        new OA\Property(property: 'type', type: 'string', example: 'text-generation'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'processing', 'completed', 'failed', 'cancelled'], example: 'pending'),
        new OA\Property(property: 'input', type: 'object'),
        new OA\Property(property: 'output', type: 'object', nullable: true),
        new OA\Property(property: 'error', type: 'string', nullable: true),
        new OA\Property(property: 'parent_task_id', type: 'integer', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class AgentTaskController extends Controller
{
    public function __construct(
        private readonly ProcessTaskUseCase $processTaskUseCase,
    ) {}

    /**
     * List tasks for an agent.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/agents/{agentId}/tasks',
        summary: 'List agent tasks',
        description: 'Paginated list of tasks for a specific agent.',
        tags: ['Agent Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'agentId', description: 'Agent ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'status', description: 'Filter by status', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'processing', 'completed', 'failed', 'cancelled'])),
            new OA\QueryParameter(name: 'per_page', description: 'Items per page', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\QueryParameter(name: 'page', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of tasks',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AgentTask')),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
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
    #[OA\Get(
        path: '/organizations/{organizationId}/agents/{agentId}/tasks/{taskId}',
        summary: 'Show agent task',
        description: 'Return a single task with its full input/output details.',
        tags: ['Agent Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'agentId', description: 'Agent ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'taskId', description: 'Task ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AgentTask'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
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
    #[OA\Post(
        path: '/organizations/{organizationId}/agents/{agentId}/tasks/{taskId}/retry',
        summary: 'Retry failed task',
        description: 'Reset a failed task to pending so it can be retried.',
        tags: ['Agent Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'agentId', description: 'Agent ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'taskId', description: 'Task ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task queued for retry',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Task queued for retry.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AgentTask'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Only failed tasks can be retried'),
        ]
    )]
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
    #[OA\Delete(
        path: '/organizations/{organizationId}/agents/{agentId}/tasks/{taskId}',
        summary: 'Cancel task',
        description: 'Cancel a pending or processing task.',
        tags: ['Agent Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'agentId', description: 'Agent ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'taskId', description: 'Task ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Task cancelled',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Task cancelled.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AgentTask'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Only pending or processing tasks can be cancelled'),
        ]
    )]
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
