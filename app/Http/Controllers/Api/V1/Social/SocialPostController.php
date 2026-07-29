<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Social;

use App\Application\Social\DTOs\CreatePostDTO;
use App\Application\Social\UseCases\CreatePostUseCase;
use App\Application\Social\UseCases\SchedulePostUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Social\StoreSocialPostRequest;
use App\Http\Resources\V1\SocialPostResource;
use App\Infrastructure\Persistence\Models\SocialPost;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Social Posts', description: 'Social media post management — CRUD, schedule, publish')]
#[OA\Schema(
    schema: 'SocialPost',
    description: 'Social media post',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'organization_id', type: 'integer', example: 1),
        new OA\Property(property: 'social_account_id', type: 'integer', example: 1),
        new OA\Property(property: 'content', type: 'string', example: 'Check out our new product!'),
        new OA\Property(property: 'media_url', type: 'string', nullable: true, example: 'https://cdn.example.com/post.jpg'),
        new OA\Property(property: 'platforms', type: 'array', items: new OA\Items(type: 'string'), example: ['twitter', 'linkedin']),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'scheduled', 'published', 'failed'], example: 'draft'),
        new OA\Property(property: 'scheduled_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
final class SocialPostController extends Controller
{
    public function __construct(
        private readonly CreatePostUseCase $createPostUseCase,
        private readonly SchedulePostUseCase $schedulePostUseCase,
    ) {}

    /**
     * List social posts for an organization.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/social/posts',
        summary: 'List social posts',
        description: 'Paginated list of social media posts with optional status/platform filters.',
        tags: ['Social Posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\QueryParameter(name: 'status', description: 'Filter by status', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'scheduled', 'published', 'failed'])),
            new OA\QueryParameter(name: 'platform', description: 'Filter by platform', required: false, schema: new OA\Schema(type: 'string')),
            new OA\QueryParameter(name: 'per_page', description: 'Items per page', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\QueryParameter(name: 'page', description: 'Page number', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of posts',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/SocialPost')),
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
        $posts = SocialPost::where('organization_id', $organizationId)
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->platform, fn ($q, $p) => $q->whereJsonContains('platforms', $p))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => SocialPostResource::collection($posts),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    /**
     * Create a new social post.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/social/posts',
        summary: 'Create social post',
        description: 'Create a new social media post for one or more platforms.',
        tags: ['Social Posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['social_account_id', 'content', 'platforms'],
                properties: [
                    new OA\Property(property: 'social_account_id', type: 'integer', example: 1),
                    new OA\Property(property: 'content', type: 'string', maxLength: 5000, example: 'Check out our new product!'),
                    new OA\Property(property: 'platforms', type: 'array', items: new OA\Items(type: 'string'), example: ['twitter', 'linkedin']),
                    new OA\Property(property: 'media_url', type: 'string', format: 'uri', example: 'https://cdn.example.com/post.jpg'),
                    new OA\Property(property: 'scheduled_at', type: 'string', format: 'date-time', nullable: true, example: '2026-08-01T09:00:00Z'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Post created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Post created successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SocialPost'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreSocialPostRequest $request, string $organizationId): JsonResponse
    {
        $validated = $request->validated();

        $dto = new CreatePostDTO(
            organizationId: $organizationId,
            socialAccountId: $validated['social_account_id'],
            content: $validated['content'],
            platforms: $validated['platforms'],
            mediaUrl: $validated['media_url'] ?? null,
            scheduledAt: isset($validated['scheduled_at'])
                ? new DateTimeImmutable($validated['scheduled_at'])
                : null,
        );

        $result = $this->createPostUseCase->execute($dto);

        return response()->json([
            'message' => 'Post created successfully.',
            'data' => $result->toArray(),
        ], 201);
    }

    /**
     * Show a social post.
     */
    #[OA\Get(
        path: '/organizations/{organizationId}/social/posts/{postId}',
        summary: 'Show social post',
        description: 'Return a single social media post with account details.',
        tags: ['Social Posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'postId', description: 'Post ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Post details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/SocialPost'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(string $organizationId, string $postId): JsonResponse
    {
        $post = SocialPost::where('organization_id', $organizationId)
            ->with('socialAccount')
            ->findOrFail($postId);

        return response()->json([
            'data' => new SocialPostResource($post),
        ]);
    }

    /**
     * Update a social post.
     */
    #[OA\Put(
        path: '/organizations/{organizationId}/social/posts/{postId}',
        summary: 'Update social post',
        description: 'Update a draft or scheduled post. Published posts cannot be updated.',
        tags: ['Social Posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'postId', description: 'Post ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 5000),
                    new OA\Property(property: 'media_url', type: 'string', format: 'uri'),
                    new OA\Property(property: 'platforms', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Post updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Post updated successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SocialPost'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Cannot update a published post'),
        ]
    )]
    public function update(StoreSocialPostRequest $request, string $organizationId, string $postId): JsonResponse
    {
        $post = SocialPost::where('organization_id', $organizationId)
            ->findOrFail($postId);

        if ($post->status === 'published') {
            return response()->json([
                'message' => 'Cannot update a published post.',
            ], 422);
        }

        $validated = $request->validated();

        $post->update([
            'content' => $validated['content'] ?? $post->content,
            'media_url' => $validated['media_url'] ?? $post->media_url,
            'platforms' => $validated['platforms'] ?? $post->platforms,
        ]);

        return response()->json([
            'message' => 'Post updated successfully.',
            'data' => new SocialPostResource($post->fresh()),
        ]);
    }

    /**
     * Schedule a post for future publication.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/social/posts/{postId}/schedule',
        summary: 'Schedule post',
        description: 'Set a future publication time for a post.',
        tags: ['Social Posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'postId', description: 'Post ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['scheduled_at'],
                properties: [
                    new OA\Property(property: 'scheduled_at', type: 'string', format: 'date-time', example: '2026-08-01T09:00:00Z', description: 'Must be in the future'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Post scheduled',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Post scheduled successfully.'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function schedule(Request $request, string $organizationId, string $postId): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $result = $this->schedulePostUseCase->execute(
            postId: $postId,
            scheduledAt: new DateTimeImmutable($validated['scheduled_at']),
        );

        return response()->json([
            'message' => 'Post scheduled successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Publish a post immediately.
     */
    #[OA\Post(
        path: '/organizations/{organizationId}/social/posts/{postId}/publish',
        summary: 'Publish post',
        description: 'Publish a post immediately to its configured platforms.',
        tags: ['Social Posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'postId', description: 'Post ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Post published',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Post published successfully.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/SocialPost'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Post is already published'),
        ]
    )]
    public function publish(string $organizationId, string $postId): JsonResponse
    {
        $post = SocialPost::where('organization_id', $organizationId)
            ->findOrFail($postId);

        if ($post->status === 'published') {
            return response()->json([
                'message' => 'Post is already published.',
            ], 422);
        }

        $post->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return response()->json([
            'message' => 'Post published successfully.',
            'data' => new SocialPostResource($post->fresh()),
        ]);
    }

    /**
     * Delete a social post.
     */
    #[OA\Delete(
        path: '/organizations/{organizationId}/social/posts/{postId}',
        summary: 'Delete social post',
        description: 'Delete a draft or scheduled post. Published posts cannot be deleted.',
        tags: ['Social Posts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\PathParameter(name: 'organizationId', description: 'Organization ID', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\PathParameter(name: 'postId', description: 'Post ID', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Post deleted', content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string', example: 'Post deleted successfully.')])),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Cannot delete a published post'),
        ]
    )]
    public function destroy(string $organizationId, string $postId): JsonResponse
    {
        $post = SocialPost::where('organization_id', $organizationId)
            ->findOrFail($postId);

        if ($post->status === 'published') {
            return response()->json([
                'message' => 'Cannot delete a published post.',
            ], 422);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully.',
        ]);
    }
}
