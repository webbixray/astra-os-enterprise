<?php

declare(strict_types=1);

namespace App\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Histogram;

/**
 * Database Queries Metrics
 * Tracks database query counts and durations.
 */
class DatabaseQueriesTotal
{
    public static function register(CollectorRegistry $registry = null): array
    {
        $registry = $registry ?? app(CollectorRegistry::class);

        $counter = $registry->registerCounter(
            config('prometheus.namespace', 'astra_os'),
            'database_queries_total',
            'Total number of database queries executed',
            ['connection', 'type']
        );

        $duration = $registry->registerHistogram(
            config('prometheus.namespace', 'astra_os'),
            'database_query_duration_seconds',
            'Database query duration in seconds',
            ['connection', 'type'],
            config('prometheus.buckets.database', [0.001, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1])
        );

        return [$counter, $duration];
    }

    public static function increment(string $connection, string $type = 'select'): void
    {
        $metrics = self::register();
        $metrics[0]->incBy(1, ['connection' => $connection, 'type' => $type]);
    }

    public static function observeDuration(string $connection, string $type, float $duration): void
    {
        $metrics = self::register();
        $metrics[1]->observe($duration, ['connection' => $connection, 'type' => $type]);
    }
}