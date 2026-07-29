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

final class SocialPostController extends Controller
{
    public function __construct(
        private readonly CreatePostUseCase $createPostUseCase,
        private readonly SchedulePostUseCase $schedulePostUseCase,
    ) {}

    /**
     * List social posts for an organization.
     */
    public function index(Request $request, int $organizationId): JsonResponse
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
    public function store(StoreSocialPostRequest $request, int $organizationId): JsonResponse
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
    public function show(int $organizationId, int $postId): JsonResponse
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
    public function update(StoreSocialPostRequest $request, int $organizationId, int $postId): JsonResponse
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
    public function schedule(Request $request, int $organizationId, int $postId): JsonResponse
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
    public function publish(int $organizationId, int $postId): JsonResponse
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

        // In production: dispatch job to actually post to social platforms
        // PublishSocialPostJob::dispatch($post);

        return response()->json([
            'message' => 'Post published successfully.',
            'data' => new SocialPostResource($post->fresh()),
        ]);
    }

    /**
     * Delete a social post.
     */
    public function destroy(int $organizationId, int $postId): JsonResponse
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
