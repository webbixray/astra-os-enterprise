<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Workflow;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Workflow Templates', description: 'Pre-built workflow templates — list, view, apply')]
final class WorkflowTemplateController extends Controller
{
    /**
     * List all available templates.
     */
    #[OA\Get(
        path: '/workflow-templates',
        summary: 'List templates',
        description: 'Return all available workflow templates that can be applied to create workflows.',
        tags: ['Workflow Templates'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of templates',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', example: 'content_approval'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Content Approval'),
                                    new OA\Property(property: 'description', type: 'string', example: 'A multi-step content review and approval workflow.'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->getTemplates(),
        ]);
    }

    /**
     * Show a specific template.
     */
    #[OA\Get(
        path: '/workflow-templates/{templateId}',
        summary: 'Show template',
        description: 'Return a single workflow template with full node/edge definitions.',
        tags: ['Workflow Templates'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'templateId', description: 'Template ID (slug)', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Template details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: 'content_approval'),
                                new OA\Property(property: 'name', type: 'string', example: 'Content Approval'),
                                new OA\Property(property: 'description', type: 'string'),
                                new OA\Property(property: 'nodes', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'edges', type: 'array', items: new OA\Items(type: 'object')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Template not found'),
        ]
    )]
    public function show(string $templateId): JsonResponse
    {
        $templates = $this->getTemplates();
        $template = collect($templates)->firstWhere('id', $templateId);

        if ($template === null) {
            return response()->json([
                'message' => "Template '{$templateId}' not found.",
            ], 404);
        }

        return response()->json(['data' => $template]);
    }

    /**
     * Apply a template to create a new workflow.
     */
    #[OA\Post(
        path: '/workflow-templates/{templateId}/apply',
        summary: 'Apply template',
        description: 'Create a new workflow from a template.',
        tags: ['Workflow Templates'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'templateId', description: 'Template ID (slug)', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['organization_id'],
                properties: [
                    new OA\Property(property: 'organization_id', type: 'integer', example: 1, description: 'Target organization ID'),
                    new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'My Content Workflow', description: 'Custom workflow name (defaults to template name)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Workflow created from template',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Workflow created from template.'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Template not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function apply(Request $request, string $templateId): JsonResponse
    {
        $templates = $this->getTemplates();
        $template = collect($templates)->firstWhere('id', $templateId);

        if ($template === null) {
            return response()->json([
                'message' => "Template '{$templateId}' not found.",
            ], 404);
        }

        $validated = $request->validate([
            'organization_id' => 'required|integer|exists:organizations,id',
            'name' => 'nullable|string|max:255',
        ]);

        $workflow = Workflow::create([
            'name' => $validated['name'] ?? $template['name'],
            'description' => $template['description'],
            'nodes' => $template['nodes'],
            'edges' => $template['edges'],
            'organization_id' => $validated['organization_id'],
            'status' => 'draft',
            'version' => 1,
        ]);

        return response()->json([
            'message' => 'Workflow created from template.',
            'data' => $workflow,
        ], 201);
    }

    /**
     * @return array<int, array{id: string, name: string, description: string, nodes: array, edges: array}>
     */
    private function getTemplates(): array
    {
        return [
            [
                'id' => 'content_approval',
                'name' => 'Content Approval',
                'description' => 'A multi-step content review and approval workflow.',
                'nodes' => [
                    ['id' => 'trigger', 'type' => 'trigger', 'config' => ['event' => 'content.created']],
                    ['id' => 'review', 'type' => 'action', 'config' => ['action_type' => 'notify.reviewers']],
                    ['id' => 'approve', 'type' => 'condition', 'config' => ['expression' => 'approved']],
                    ['id' => 'publish', 'type' => 'action', 'config' => ['action_type' => 'content.publish']],
                    ['id' => 'reject', 'type' => 'output', 'config' => ['output' => 'Content rejected']],
                ],
                'edges' => [
                    ['from' => 'trigger', 'to' => 'review'],
                    ['from' => 'review', 'to' => 'approve'],
                    ['from' => 'approve', 'to' => 'publish'],
                    ['from' => 'approve', 'to' => 'reject'],
                ],
            ],
            [
                'id' => 'campaign_launch',
                'name' => 'Campaign Launch',
                'description' => 'Automated campaign launch with approval gate.',
                'nodes' => [
                    ['id' => 'start', 'type' => 'trigger', 'config' => ['event' => 'campaign.ready']],
                    ['id' => 'validate', 'type' => 'action', 'config' => ['action_type' => 'campaign.validate']],
                    ['id' => 'approve', 'type' => 'condition', 'config' => ['expression' => 'approved']],
                    ['id' => 'launch', 'type' => 'action', 'config' => ['action_type' => 'campaign.launch']],
                ],
                'edges' => [
                    ['from' => 'start', 'to' => 'validate'],
                    ['from' => 'validate', 'to' => 'approve'],
                    ['from' => 'approve', 'to' => 'launch'],
                ],
            ],
        ];
    }
}
