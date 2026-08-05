<?php

declare(strict_types=1);

namespace App\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Histogram;

/**
 * Queue Jobs Metrics
 * Tracks queue job execution counts and durations.
 */
class QueueJobsTotal
{
    public static function register(CollectorRegistry $registry = null): array
    {
        $registry = $registry ?? app(CollectorRegistry::class);

        $counter = $registry->registerCounter(
            config('prometheus.namespace', 'astra_os'),
            'queue_jobs_total',
            'Total number of queue jobs processed',
            ['job', 'status']
        );

        $duration = $registry->registerHistogram(
            config('prometheus.namespace', 'astra_os'),
            'queue_job_duration_seconds',
            'Queue job duration in seconds',
            ['job'],
            config('prometheus.buckets.queue', [0.01, 0.05, 0.1, 0.5, 1, 5, 10, 30, 60])
        );

        $failed = $registry->registerCounter(
            config('prometheus.namespace', 'astra_os'),
            'queue_job_failed_total',
            'Total number of failed queue jobs',
            ['job', 'exception']
        );

        return [$counter, $duration, $failed];
    }

    public static function increment(string $job, string $status = 'success'): void
    {
        $metrics = self::register();
        $metrics[0]->incBy(1, ['job' => $job, 'status' => $status]);
    }

    public static function observeDuration(string $job, float $duration): void
    {
        $metrics = self::register();
        $metrics[1]->observe($duration, ['job' => $job]);
    }

    public static function incrementFailed(string $job, string $exceptionClass): void
    {
        $metrics = self::register();
        $metrics[2]->incBy(1, ['job' => $job, 'exception' => $exceptionClass]);
    }
}