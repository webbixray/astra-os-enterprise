<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Models\User;
use App\Services\Notifications\NotificationService;
use App\Services\Webhooks\WebhookService;
use Illuminate\Support\Facades\Log;

final class WebhookChannel
{
    public function __construct(
        private readonly WebhookService $webhookService,
    ) {}

    /**
     * Send a notification via registered webhook endpoints for the user.
     *
     * The notification is dispatched to every active webhook endpoint
     * the user (or their organization) has registered.
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = [],
        string $priority = NotificationService::PRIORITY_NORMAL,
    ): void {
        $payload = [
            'event' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'priority' => $priority,
            'timestamp' => now()->toIso8601String(),
        ];

        $organizationId = $user->organization_id;

        // Dispatch to user's own webhooks and organization webhooks
        $this->webhookService->dispatchToUser($user->id, 'notification', $payload);

        if ($organizationId) {
            $this->webhookService->dispatchToOrganization($organizationId, 'notification', $payload);
        }
    }
}
