<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Workflow;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WorkflowTemplateController extends Controller
{
    /**
     * List available workflow templates.
     *
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

    /**
     * List all available templates.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->getTemplates(),
        ]);
    }

    /**
     * Show a specific template.
     */
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
}
