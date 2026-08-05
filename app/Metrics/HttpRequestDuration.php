<?php

declare(strict_types=1);

namespace App\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Histogram;

/**
 * HTTP Request Duration Histogram
 * Tracks HTTP request duration in seconds.
 */
class HttpRequestDuration
{
    public static function register(CollectorRegistry $registry = null): Histogram
    {
        $registry = $registry ?? app(CollectorRegistry::class);

        return $registry->registerHistogram(
            config('prometheus.namespace', 'astra_os'),
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'path'],
            config('prometheus.buckets.http', [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10])
        );
    }

    public static function observe(string $method, string $path, float $duration): void
    {
        $histogram = self::register();
        $histogram->observe($duration, [
            'method' => $method,
            'path' => self::normalizePath($path),
        ]);
    }

    protected static function normalizePath(string $path): string
    {
        // Normalize dynamic path segments
        return preg_replace('/\/\d+/', '/{id}', $path);
    }
}