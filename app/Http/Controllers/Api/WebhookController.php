<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Webhooks\WebhookService;
use App\Infrastructure\Persistence\Models\WebhookEndpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class WebhookController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly WebhookService $webhookService,
    ) {}

    /**
     * List all webhook endpoints for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $endpoints = $this->webhookService->getUserEndpoints($request->user()->id);

        return $this->success($endpoints);
    }

    /**
     * Store a new webhook endpoint.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['nullable', 'array'],
            'events.*' => ['string', 'max:100'],
            'secret' => ['nullable', 'string', 'min:16', 'max:128'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $validated = $validator->validated();

        $endpoint = $this->webhookService->registerEndpoint(
            userId: $request->user()->id,
            url: $validated['url'],
            secret: $validated['secret'] ?? null,
            events: $validated['events'] ?? ['*'],
        );

        return $this->created($endpoint, 'Webhook endpoint created.');
    }

    /**
     * Show a specific webhook endpoint.
     */
    public function show(string $id): JsonResponse
    {
        $endpoint = WebhookEndpoint::find($id);

        if (! $endpoint) {
            return $this->notFound('Webhook endpoint not found.');
        }

        return $this->success($endpoint);
    }

    /**
     * Update a webhook endpoint.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $endpoint = WebhookEndpoint::find($id);

        if (! $endpoint) {
            return $this->notFound('Webhook endpoint not found.');
        }

        $validator = Validator::make($request->all(), [
            'url' => ['sometimes', 'url', 'max:2048'],
            'events' => ['sometimes', 'array'],
            'events.*' => ['string', 'max:100'],
            'secret' => ['sometimes', 'string', 'min:16', 'max:128'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $updated = $this->webhookService->updateEndpoint($id, $validator->validated());

        return $this->success($updated, 'Webhook endpoint updated.');
    }

    /**
     * Delete a webhook endpoint.
     */
    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->webhookService->deleteEndpoint($id);

        if (! $deleted) {
            return $this->notFound('Webhook endpoint not found.');
        }

        return $this->success(null, 'Webhook endpoint deleted.');
    }

    /**
     * Send a test event to a webhook endpoint.
     */
    public function test(string $id): JsonResponse
    {
        $sent = $this->webhookService->sendTestEvent($id);

        if (! $sent) {
            return $this->notFound('Webhook endpoint not found or inactive.');
        }

        return $this->success(null, 'Test event sent successfully.');
    }
}
