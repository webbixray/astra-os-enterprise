<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use App\Models\User;
use App\Infrastructure\Persistence\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Log;

final class WebhookService
{
    /**
     * The HMAC signing algorithm.
     */
    private const SIGNING_ALGORITHM = 'sha256';

    public function __construct(
        private readonly WebhookDelivery $delivery,
    ) {}

    /**
     * Register a new webhook endpoint for a user.
     *
     * @return WebhookEndpoint
     */
    public function registerEndpoint(
        string $userId,
        string $url,
        ?string $secret = null,
        array $events = ['*'],
        ?string $organizationId = null,
    ): WebhookEndpoint {
        $secret = $secret ?? $this->generateSecret();

        return WebhookEndpoint::create([
            'user_id' => $userId,
            'organization_id' => $organizationId,
            'url' => $url,
            'secret' => $secret,
            'events' => $events,
            'is_active' => true,
        ]);
    }

    /**
     * Update a webhook endpoint.
     */
    public function updateEndpoint(
        string $endpointId,
        array $data,
    ): ?WebhookEndpoint {
        $endpoint = WebhookEndpoint::find($endpointId);

        if (! $endpoint) {
            return null;
        }

        $fillable = [];
        foreach (['url', 'secret', 'events', 'is_active'] as $field) {
            if (array_key_exists($field, $data)) {
                $fillable[$field] = $data[$field];
            }
        }

        if (! empty($fillable)) {
            $endpoint->update($fillable);
        }

        return $endpoint->fresh();
    }

    /**
     * Delete a webhook endpoint.
     */
    public function deleteEndpoint(string $endpointId): bool
    {
        $endpoint = WebhookEndpoint::find($endpointId);

        if (! $endpoint) {
            return false;
        }

        return (bool) $endpoint->delete();
    }

    /**
     * Dispatch an event to all matching webhook endpoints for a user.
     *
     * @return int Number of endpoints dispatched to.
     */
    public function dispatchToUser(string $userId, string $event, array $payload): int
    {
        $endpoints = WebhookEndpoint::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        return $this->dispatchToEndpoints($endpoints, $event, $payload);
    }

    /**
     * Dispatch an event to all matching webhook endpoints for an organization.
     *
     * @return int Number of endpoints dispatched to.
     */
    public function dispatchToOrganization(string $organizationId, string $event, array $payload): int
    {
        $endpoints = WebhookEndpoint::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->get();

        return $this->dispatchToEndpoints($endpoints, $event, $payload);
    }

    /**
     * Send a test event to a specific endpoint.
     */
    public function sendTestEvent(string $endpointId): bool
    {
        $endpoint = WebhookEndpoint::find($endpointId);

        if (! $endpoint || ! $endpoint->is_active) {
            return false;
        }

        $payload = [
            'event' => 'webhook.test',
            'data' => [
                'message' => 'This is a test webhook event from Astra OS.',
                'timestamp' => now()->toIso8601String(),
            ],
        ];

        $signature = $this->signPayload($payload, $endpoint->secret);
        $this->delivery->send($endpoint, $payload, $signature);

        return true;
    }

    /**
     * Verify an incoming webhook signature.
     */
    public function verifySignature(array $payload, string $signature, string $secret): bool
    {
        $expected = $this->signPayload($payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Generate a cryptographically secure HMAC secret.
     */
    public function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Sign a payload using HMAC-SHA256.
     */
    public function signPayload(array $payload, string $secret): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return hash_hmac(self::SIGNING_ALGORITHM, $json, $secret);
    }

    /**
     * Get all endpoints for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserEndpoints(string $userId)
    {
        return WebhookEndpoint::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all endpoints for an organization.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrganizationEndpoints(string $organizationId)
    {
        return WebhookEndpoint::where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Dispatch payload to a collection of endpoints.
     */
    private function dispatchToEndpoints($endpoints, string $event, array $payload): int
    {
        $count = 0;

        foreach ($endpoints as $endpoint) {
            if (! $this->eventMatches($event, $endpoint->events)) {
                continue;
            }

            $enrichedPayload = array_merge($payload, [
                'event' => $event,
                'timestamp' => now()->toIso8601String(),
                'endpoint_id' => $endpoint->id,
            ]);

            $signature = $this->signPayload($enrichedPayload, $endpoint->secret);

            try {
                $this->delivery->send($endpoint, $enrichedPayload, $signature);
                $count++;
            } catch (\Throwable $e) {
                Log::error('Webhook dispatch failed', [
                    'endpoint_id' => $endpoint->id,
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Check if an event matches a list of subscribed events (supports '*' wildcard).
     */
    private function eventMatches(string $event, array $subscribedEvents): bool
    {
        foreach ($subscribedEvents as $subscribed) {
            if ($subscribed === '*' || $subscribed === $event) {
                return true;
            }
        }

        return false;
    }
}
