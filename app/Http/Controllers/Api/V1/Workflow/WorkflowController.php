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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Workflows', description: 'Workflow automation — CRUD, activate, deactivate, duplicate')]
#[OA\Schema(
    schema: 'Workflow',
    description: 'Workflow automation model',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Content Approval'),
        new OA\Property(property: 'description', type: 'string', example: 'Multi-step content review workflow'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'active', 'inactive'], example: 'draft'),
        new OA\Property(property: 'nodes', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'edges', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'triggers', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'variables', type: 'object'),
        new OA\Property(property: 'organization_id', type: 'integer', example: 1),
        new OA\Property(property: 'version', type: 'integer', example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class WorkflowController extends Controller
{
    public function __construct(
        private readonly CreateWorkflowUseCase $createWorkflowUseCase,
        private readonly ActivateWorkflowUseCase $activateWorkflowUseCase,
    ) {}

    /**
     * List workflows for an organization.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/workflows',
        summary: 'List workflows',
        description: 'Paginated list of automation workflows.',
        tags: ['Workflows'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'status', description: 'Filter by status', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'active', 'inactive'])),
            new OA\QueryParameter(name: 'per_page', description: 'Items per page', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\QueryParameter(name: 'page', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of workflows',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Workflow')),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request, string $organizationId): JsonResponse
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
    #[OA\Post(
        path: '/organizations/{organizationId}/workflows',
        summary: 'Create workflow',
        description: 'Create a new automation workflow with nodes, edges, and triggers.',
        tags: ['Workflows'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'nodes', 'edges'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Content Approval'),
                    new OA\Property(property: 'description', type: 'string', example: 'Multi-step content review workflow'),
                    new OA\Property(property: 'nodes', type: 'array', items: new OA\Items(type: 'object'), description: 'Workflow node definitions'),
                    new OA\Property(property: 'edges', type: 'array', items: new OA\Items(type: 'object'), description: 'Workflow edge connections'),
                    new OA\Property(property: 'triggers', type: 'array', items: new OA\Items(type: 'object'), description: 'Event triggers'),
                    new OA\Property(property: 'variables', type: 'object', description: 'Workflow variable definitions'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Workflow created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Workflow created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Workflow'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreWorkflowRequest $request, string $organizationId): JsonResponse
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
    #[OA\Get(
        path: '/organizations/{organizationId}/workflows/{workflowId}',
        summary: 'Show workflow',
        description: 'Return a single workflow with its full node/edge definition.',
        tags: ['Workflows'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'workflowId', description: 'Workflow ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Workflow details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Workflow'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(string $organizationId, string $workflowId): JsonResponse
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
    #[OA\Put(
        path: '/organizations/{organizationId}/workflows/{workflowId}',
        summary: 'Update workflow',
        description: "Update an existing workflow's nodes, edges, triggers, or variables.",
        tags: ['Workflows'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'workflowId', description: 'Workflow ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'nodes', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'edges', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'triggers', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'variables', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Workflow updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Workflow updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Workflow'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(StoreWorkflowRequest $request, string $organizationId, string $workflowId): JsonResponse
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
    #[OA\Post(
        path: '/organizations/{organizationId}/workflows/{workflowId}/activate',
        summary: 'Activate workflow',
        description: 'Activate a workflow so it begins listening for triggers.',
        tags: ['Workflows'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'workflowId', description: 'Workflow ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Workflow activated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Workflow activated successfully.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function activate(string $organizationId, string $workflowId): JsonResponse
    {
        $this->activateWorkflowUseCase->execute($workflowId, true);

        return response()->json([
            'message' => 'Workflow activated successfully.',
        ]);
    }

    /**
     * Deactivate a workflow.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/workflows/{workflowId}/deactivate',
        summary: 'Deactivate workflow',
        description: 'Deactivate a running workflow so it stops listening for triggers.',
        tags: ['Workflows'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'workflowId', description: 'Workflow ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Workflow deactivated', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Workflow deactivated successfully.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function deactivate(string $organizationId, string $workflowId): JsonResponse
    {
        $this->activateWorkflowUseCase->execute($workflowId, false);

        return response()->json([
            'message' => 'Workflow deactivated successfully.',
        ]);
    }

    /**
     * Duplicate a workflow.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/workflows/{workflowId}/duplicate',
        summary: 'Duplicate workflow',
        description: 'Create a copy of an existing workflow as a new draft.',
        tags: ['Workflows'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'workflowId', description: 'Workflow ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Workflow duplicated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Workflow duplicated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Workflow'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function duplicate(string $organizationId, string $workflowId): JsonResponse
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
    #[OA\Delete(
        path: '/organizations/{organizationId}/workflows/{workflowId}',
        summary: 'Delete workflow',
        description: 'Permanently delete a workflow.',
        tags: ['Workflows'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'workflowId', description: 'Workflow ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Workflow deleted', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Workflow deleted successfully.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(string $organizationId, string $workflowId): JsonResponse
    {
        $workflow = Workflow::where('organization_id', $organizationId)
            ->findOrFail($workflowId);

        $workflow->delete();

        return response()->json([
            'message' => 'Workflow deleted successfully.',
        ]);
    }
}
