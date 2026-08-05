<?php

declare(strict_types=1);

namespace App\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;

/**
 * HTTP Requests Total Counter
 * Tracks total HTTP requests by method, path, and status code.
 */
class HttpRequestsTotal
{
    public static function register(CollectorRegistry $registry = null): Counter
    {
        $registry = $registry ?? app(CollectorRegistry::class);

        return $registry->registerCounter(
            config('prometheus.namespace', 'astra_os'),
            'http_requests_total',
            'Total number of HTTP requests',
            ['method', 'path', 'status']
        );
    }

    public static function increment(string $method, string $path, int $status): void
    {
        $counter = self::register();
        $counter->incBy(1, [
            'method' => $method,
            'path' => self::normalizePath($path),
            'status' => (string) $status,
        ]);
    }

    protected static function normalizePath(string $path): string
    {
        // Normalize dynamic path segments
        return preg_replace('/\/\d+/', '/{id}', $path);
    }
}