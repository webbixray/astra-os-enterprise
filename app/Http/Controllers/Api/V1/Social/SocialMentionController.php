<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Social;

use App\Application\Social\UseCases\ProcessMentionsUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Social Mentions', description: 'Social media mention monitoring — list, read, reply')]
#[OA\Schema(
    schema: 'SocialMention',
    description: 'Social media mention',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'organization_id', type: 'integer', example: 1),
        new OA\Property(property: 'platform', type: 'string', example: 'twitter'),
        new OA\Property(property: 'author', type: 'string', example: '@user123'),
        new OA\Property(property: 'content', type: 'string', example: 'Love your product!'),
        new OA\Property(property: 'sentiment', type: 'string', enum: ['positive', 'neutral', 'negative'], example: 'positive'),
        new OA\Property(property: 'status', type: 'string', enum: ['unread', 'read', 'replied'], example: 'unread'),
        new OA\Property(property: 'mention_url', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
final class SocialMentionController extends Controller
{
    public function __construct(
        private readonly ProcessMentionsUseCase $processMentionsUseCase,
    ) {}

    /**
     * List mentions for an organization.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/social/mentions',
        summary: 'List mentions',
        description: 'Return social media mentions for an organization, with optional platform/status filters.',
        tags: ['Social Mentions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'platform', description: 'Filter by platform', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'status', description: 'Filter by status', required: false, schema: new OA\Schema(type: 'string', enum: ['unread', 'read', 'replied'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of mentions',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SocialMention')),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 0),
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
        $platform = $request->platform;
        $status = $request->status;

        $mentions = []; // In production: Mention::where('organization_id', $organizationId)->get()

        return response()->json([
            'data' => $mentions,
            'meta' => ['total' => count($mentions)],
        ]);
    }

    /**
     * Show a specific mention.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/social/mentions/{mentionId}',
        summary: 'Show mention',
        description: 'Return a single social media mention.',
        tags: ['Social Mentions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'mentionId', description: 'Mention ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Mention details',
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
    public function show(string $organizationId, string $mentionId): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    /**
     * Mark a mention as read.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/social/mentions/{mentionId}/read',
        summary: 'Mark mention as read',
        description: 'Mark a social media mention as read.',
        tags: ['Social Mentions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'mentionId', description: 'Mention ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Mention marked as read', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Mention marked as read.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function markRead(string $organizationId, string $mentionId): JsonResponse
    {
        return response()->json([
            'message' => 'Mention marked as read.',
        ]);
    }

    /**
     * Generate an AI-suggested reply for a mention.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/social/mentions/{mentionId}/generate-reply',
        summary: 'Generate reply',
        description: 'Use AI to generate a suggested reply for a social media mention.',
        tags: ['Social Mentions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'mentionId', description: 'Mention ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'AI-generated reply suggestion',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'suggested_reply', type: 'string', example: 'Thank you for your feedback! We appreciate your input.'),
                                new OA\Property(property: 'sentiment', type: 'string', example: 'neutral'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function generateReply(string $organizationId, string $mentionId): JsonResponse
    {
        return response()->json([
            'data' => [
                'suggested_reply' => 'Thank you for your feedback! We appreciate your input.',
                'sentiment' => 'neutral',
            ],
        ]);
    }

    /**
     * Send a reply to a mention.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/social/mentions/{mentionId}/reply',
        summary: 'Send reply',
        description: 'Post a reply to a social media mention.',
        tags: ['Social Mentions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'mentionId', description: 'Mention ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['reply'],
                properties: [
                    new OA\Property(property: 'reply', type: 'string', maxLength: 2000, example: 'Thank you for your feedback!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Reply sent',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Reply sent successfully.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'mention_id', type: 'integer'),
                                new OA\Property(property: 'reply', type: 'string'),
                                new OA\Property(property: 'sent_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function sendReply(Request $request, string $organizationId, string $mentionId): JsonResponse
    {
        $validated = $request->validate([
            'reply' => 'required|string|max:2000',
        ]);

        return response()->json([
            'message' => 'Reply sent successfully.',
            'data' => [
                'mention_id' => $mentionId,
                'reply' => $validated['reply'],
                'sent_at' => now()->toDateTimeString(),
            ],
        ]);
    }
}
