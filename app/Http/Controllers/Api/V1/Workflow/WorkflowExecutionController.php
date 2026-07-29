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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Workflow Executions', description: 'Workflow execution management — execute, list, cancel')]
#[OA\Schema(
    schema: 'WorkflowExecution',
    description: 'Workflow execution record',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'workflow_id', type: 'integer', example: 1),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'running', 'completed', 'failed', 'cancelled'], example: 'completed'),
        new OA\Property(property: 'input_variables', type: 'object'),
        new OA\Property(property: 'output', type: 'object', nullable: true),
        new OA\Property(property: 'error', type: 'string', nullable: true),
        new OA\Property(property: 'started_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class WorkflowExecutionController extends Controller
{
    public function __construct(
        private readonly ExecuteWorkflowUseCase $executeWorkflowUseCase,
    ) {}

    /**
     * List executions for a workflow.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/workflows/{workflowId}/executions',
        summary: 'List executions',
        description: 'Paginated list of workflow execution records.',
        tags: ['Workflow Executions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'workflowId', description: 'Workflow ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of executions',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WorkflowExecution')),
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
    public function index(string $organizationId, string $workflowId): JsonResponse
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
    #[OA\Get(
        path: '/organizations/{organizationId}/workflows/{workflowId}/executions/{executionId}',
        summary: 'Show execution',
        description: 'Return details of a single workflow execution.',
        tags: ['Workflow Executions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'workflowId', description: 'Workflow ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'executionId', description: 'Execution ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Execution details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/WorkflowExecution'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(string $organizationId, string $workflowId, string $executionId): JsonResponse
    {
        $execution = WorkflowExecution::where('workflow_id', $workflowId)
            ->findOrFail($executionId);

        return response()->json(['data' => $execution]);
    }

    /**
     * Execute a workflow.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/workflows/{workflowId}/execute',
        summary: 'Execute workflow',
        description: 'Manually trigger a workflow execution with input variables.',
        tags: ['Workflow Executions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'workflowId', description: 'Workflow ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'variables', type: 'object', description: 'Input variables for the execution'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Workflow executed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Workflow executed successfully.'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function execute(ExecuteWorkflowRequest $request, string $organizationId, string $workflowId): JsonResponse
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
    #[OA\Post(
        path: '/organizations/{organizationId}/workflows/{workflowId}/executions/{executionId}/cancel',
        summary: 'Cancel execution',
        description: 'Cancel a pending or running workflow execution.',
        tags: ['Workflow Executions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'workflowId', description: 'Workflow ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'executionId', description: 'Execution ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Execution cancelled', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Execution cancelled.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function cancel(string $organizationId, string $workflowId, string $executionId): JsonResponse
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
