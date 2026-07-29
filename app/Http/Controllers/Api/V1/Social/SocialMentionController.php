<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Social;

use App\Application\Social\UseCases\ProcessMentionsUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SocialMentionController extends Controller
{
    public function __construct(
        private readonly ProcessMentionsUseCase $processMentionsUseCase,
    ) {}

    /**
     * List mentions for an organization.
     *
     * In production, this would query the mentions table.
     */
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
    public function show(string $organizationId, string $mentionId): JsonResponse
    {
        // In production: Mention::where('organization_id', $organizationId)->findOrFail($mentionId)
        return response()->json(['data' => []]);
    }

    /**
     * Mark a mention as read.
     */
    public function markRead(string $organizationId, string $mentionId): JsonResponse
    {
        // In production: update mention status to 'read'
        return response()->json([
            'message' => 'Mention marked as read.',
        ]);
    }

    /**
     * Generate an AI-suggested reply for a mention.
     */
    public function generateReply(string $organizationId, string $mentionId): JsonResponse
    {
        // In production: use ProcessMentionsUseCase to generate a single reply
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
    public function sendReply(Request $request, string $organizationId, string $mentionId): JsonResponse
    {
        $validated = $request->validate([
            'reply' => 'required|string|max:2000',
        ]);

        // In production: post reply to the social platform via API
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
