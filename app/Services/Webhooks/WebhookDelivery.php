<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use App\Infrastructure\Persistence\Models\WebhookEndpoint;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class WebhookDelivery
{
    /**
     * Default timeout for webhook HTTP requests in seconds.
     */
    private const DEFAULT_TIMEOUT = 10;

    /**
     * Maximum number of retry attempts.
     */
    private const MAX_RETRIES = 3;

    /**
     * Send a webhook payload to an endpoint with HMAC signature.
     *
     * @param WebhookEndpoint $endpoint The target endpoint.
     * @param array           $payload  The payload to send.
     * @param string          $signature HMAC-SHA256 signature of the payload.
     *
     * @throws \RuntimeException If delivery fails after all retries.
     */
    public function send(WebhookEndpoint $endpoint, array $payload, string $signature): void
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < self::MAX_RETRIES) {
            $attempts++;

            try {
                $response = Http::timeout(self::DEFAULT_TIMEOUT)
                    ->withHeaders($this->buildHeaders($signature, $endpoint))
                    ->retry(0) // We handle retry manually
                    ->post($endpoint->url, $payload);

                $this->logDeliveryAttempt($endpoint, $payload, $response, $attempts);

                if ($response->successful()) {
                    return;
                }

                // Non-2xx responses — do not retry on 4xx client errors
                if ($response->clientError()) {
                    Log::warning('Webhook delivery received client error (not retrying)', [
                        'endpoint_id' => $endpoint->id,
                        'url' => $endpoint->url,
                        'status' => $response->status(),
                        'body' => $this->truncateBody($response->body()),
                    ]);

                    return;
                }

                // Server errors — retry
                $lastException = new \RuntimeException(
                    "HTTP {$response->status()}: {$this->truncateBody($response->body())}"
                );
            } catch (\Throwable $e) {
                $lastException = $e;

                Log::warning("Webhook delivery attempt {$attempts} failed", [
                    'endpoint_id' => $endpoint->id,
                    'url' => $endpoint->url,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($attempts < self::MAX_RETRIES) {
                $this->backoff($attempts);
            }
        }

        // All retries exhausted
        Log::error('Webhook delivery failed after all retries', [
            'endpoint_id' => $endpoint->id,
            'url' => $endpoint->url,
            'attempts' => $attempts,
            'error' => $lastException?->getMessage(),
        ]);

        throw new \RuntimeException(
            "Webhook delivery failed after {$attempts} attempts: {$lastException?->getMessage()}",
            0,
            $lastException,
        );
    }

    /**
     * Build the HTTP headers for the webhook request.
     *
     * @return array<string, string>
     */
    private function buildHeaders(string $signature, WebhookEndpoint $endpoint): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-AstraOS-Signature' => $signature,
            'X-AstraOS-Timestamp' => (string) time(),
            'X-AstraOS-Webhook-ID' => $endpoint->id,
            'User-Agent' => 'AstraOS-Webhook/1.0',
        ];
    }

    /**
     * Log a delivery attempt for audit purposes.
     */
    private function logDeliveryAttempt(
        WebhookEndpoint $endpoint,
        array $payload,
        Response $response,
        int $attempt,
    ): void {
        Log::info('Webhook delivery attempt', [
            'endpoint_id' => $endpoint->id,
            'url' => $endpoint->url,
            'attempt' => $attempt,
            'status' => $response->status(),
            'event' => $payload['event'] ?? 'unknown',
        ]);
    }

    /**
     * Sleep with exponential backoff between retries.
     */
    private function backoff(int $attempt): void
    {
        $delay = min(2 ** $attempt * 2, 30); // 4s, 8s, 16s, capped at 30s
        usleep($delay * 1_000_000);
    }

    /**
     * Truncate a response body for logging.
     */
    private function truncateBody(string $body, int $maxLength = 500): string
    {
        if (mb_strlen($body) <= $maxLength) {
            return $body;
        }

        return mb_substr($body, 0, $maxLength) . '... (truncated)';
    }
}
