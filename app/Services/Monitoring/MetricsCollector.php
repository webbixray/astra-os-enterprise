<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * MetricsCollector
 *
 * Lightweight in-process metrics collector that aggregates request
 * counters, error rates, and system resource snapshots.  Data is
 * stored in a namespaced cache entry so it survives across requests
 * and can be exported for observability pipelines.
 *
 * All counters are reset when the cache entry is flushed (e.g. cache
 * clear, deployment).
 */
final class MetricsCollector
{
    /**
     * Cache key under which all metrics are stored.
     */
    private const CACHE_KEY = 'astra-os:metrics';

    /**
     * TTL for the metrics cache entry (24 hours).  After this window
     * the entire snapshot is discarded, preventing unbounded growth.
     */
    private const CACHE_TTL = 86400;

    /**
     * Increment the request counter for a given endpoint and status code.
     */
    public function incrementRequestCount(string $method, string $path, int $statusCode): void
    {
        $this->update(function (array &$metrics) use ($method, $path, $statusCode): void {
            $statusGroup = (int) ($statusCode / 100); // 2xx, 3xx, 4xx, 5xx

            $metrics['requests']['total'] = ($metrics['requests']['total'] ?? 0) + 1;
            $metrics['requests']['by_endpoint']["{$method} {$path}"] = ($metrics['requests']['by_endpoint']["{$method} {$path}"] ?? 0) + 1;
            $metrics['requests']['by_status_group']["{$statusGroup}xx"] = ($metrics['requests']['by_status_group']["{$statusGroup}xx"] ?? 0) + 1;

            // Track error responses (4xx and 5xx)
            if ($statusCode >= 400) {
                $metrics['errors']['total'] = ($metrics['errors']['total'] ?? 0) + 1;
                $metrics['errors']['by_path']["{$method} {$path}"] = ($metrics['errors']['by_path']["{$method} {$path}"] ?? 0) + 1;
            }
        });
    }

    /**
     * Record a queue job lifecycle event.
     *
     * @param  string  $job     Fully-qualified class name of the job.
     * @param  string  $status  One of: 'queued', 'processing', 'processed', 'failed'.
     * @param  float   $duration  Duration in seconds (optional).
     */
    public function recordQueueEvent(string $job, string $status, float $duration = 0.0): void
    {
        $this->update(function (array &$metrics) use ($job, $status, $duration): void {
            $metrics['queue']['total'] = ($metrics['queue']['total'] ?? 0) + 1;
            $metrics['queue']['by_job'][$job]['count'] = ($metrics['queue']['by_job'][$job]['count'] ?? 0) + 1;
            $metrics['queue']['by_job'][$job]['last_status'] = $status;
            $metrics['queue']['by_status'][$status] = ($metrics['queue']['by_status'][$status] ?? 0) + 1;

            if ($duration > 0) {
                $metrics['queue']['by_job'][$job]['total_duration'] = ($metrics['queue']['by_job'][$job]['total_duration'] ?? 0.0) + $duration;
                $metrics['queue']['by_job'][$job]['count'] = ($metrics['queue']['by_job'][$job]['count'] ?? 0) + 1;
            }
        });
    }

    /**
     * Take a snapshot of current system resources (memory, CPU load).
     *
     * This is intentionally a no-op if the required OS facilities are
     * unavailable (e.g. restricted containers, Windows).
     */
    public function snapshotSystemResources(): void
    {
        $snapshot = [
            'memory' => [
                'peak_usage_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
                'current_usage_mb' => round(memory_get_usage(true) / 1048576, 2),
            ],
            'time' => now()->toIso8601String(),
        ];

        // Attempt to read CPU load averages (Linux/macOS only).
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load)) {
                $snapshot['cpu'] = [
                    'load_1m' => round($load[0], 2),
                    'load_5m' => round($load[1], 2),
                    'load_15m' => round($load[2], 2),
                ];
            }
        }

        $this->update(function (array &$metrics) use ($snapshot): void {
            $metrics['system_resources'][] = $snapshot;

            // Keep only the last 60 snapshots to prevent unbounded growth.
            if (count($metrics['system_resources']) > 60) {
                array_shift($metrics['system_resources']);
            }
        });
    }

    /**
     * Record a custom gauge value.
     *
     * @param  string  $name   Gauge name (e.g. 'active_agents', 'db_connections').
     * @param  float|int  $value  Current value.
     */
    public function gauge(string $name, float|int $value): void
    {
        $this->update(function (array &$metrics) use ($name, $value): void {
            $metrics['gauges'][$name] = $value;
        });
    }

    /**
     * Retrieve the full metrics snapshot as an associative array.
     *
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return Cache::get(self::CACHE_KEY, $this->emptyMetrics());
    }

    /**
     * Export metrics in a Prometheus-compatible text format (informal).
     *
     * This is a best-effort conversion.  For production Prometheus
     * integration, prefer an exporter daemon or the prometheus_client
     * library instead.
     *
     * @return string
     */
    public function exportPrometheus(): string
    {
        $metrics = $this->export();
        $lines = [];
        $timestamp = now()->timestamp;

        // Request counters
        $lines[] = '# HELP astra_os_requests_total Total number of HTTP requests.';
        $lines[] = '# TYPE astra_os_requests_total counter';
        $total = $metrics['requests']['total'] ?? 0;
        $lines[] = "astra_os_requests_total {$total} {$timestamp}";

        if (isset($metrics['requests']['by_status_group'])) {
            $lines[] = '# HELP astra_os_request_status_group Request count by HTTP status group.';
            $lines[] = '# TYPE astra_os_request_status_group counter';
            foreach ($metrics['requests']['by_status_group'] as $group => $count) {
                $lines[] = "astra_os_request_status_group{status_group=\"{$group}\"} {$count} {$timestamp}";
            }
        }

        // Error counters
        $lines[] = '# HELP astra_os_errors_total Total number of error responses.';
        $lines[] = '# TYPE astra_os_errors_total counter';
        $errors = $metrics['errors']['total'] ?? 0;
        $lines[] = "astra_os_errors_total {$errors} {$timestamp}";

        // Queue metrics
        if (isset($metrics['queue'])) {
            $lines[] = '# HELP astra_os_queue_jobs_total Total number of queue jobs processed.';
            $lines[] = '# TYPE astra_os_queue_jobs_total counter';
            $queueTotal = $metrics['queue']['total'] ?? 0;
            $lines[] = "astra_os_queue_jobs_total {$queueTotal} {$timestamp}";

            if (isset($metrics['queue']['by_status'])) {
                foreach ($metrics['queue']['by_status'] as $status => $count) {
                    $lines[] = "astra_os_queue_jobs_by_status{status=\"{$status}\"} {$count} {$timestamp}";
                }
            }
        }

        // Memory gauge
        if (isset($metrics['system_resources']) && count($metrics['system_resources']) > 0) {
            $lines[] = '# HELP astra_os_memory_usage_bytes Current memory usage in bytes.';
            $lines[] = '# TYPE astra_os_memory_usage_bytes gauge';
            $latest = end($metrics['system_resources']);
            if (isset($latest['memory']['current_usage_mb'])) {
                $bytes = (int) round($latest['memory']['current_usage_mb'] * 1048576);
                $lines[] = "astra_os_memory_usage_bytes {$bytes} {$timestamp}";
            }
        }

        // Custom gauges
        if (isset($metrics['gauges'])) {
            $lines[] = '# HELP astra_os_custom_gauges Custom application gauges.';
            $lines[] = '# TYPE astra_os_custom_gauges gauge';
            foreach ($metrics['gauges'] as $name => $value) {
                $safeName = str_replace([' ', '.', '-'], '_', $name);
                $lines[] = "astra_os_{$safeName} {$value} {$timestamp}";
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Reset all collected metrics.
     */
    public function reset(): void
    {
        Cache::forget(self::CACHE_KEY);
        Log::debug('Metrics collector reset');
    }

    // -----------------------------------------------------------------------
    //  Internal helpers
    // -----------------------------------------------------------------------

    /**
     * Execute a mutating operation on the metrics store.
     *
     * Uses cache locking to prevent race conditions when multiple
     * requests attempt to update metrics simultaneously.
     *
     * @param  callable(array): void  $callback
     */
    private function update(callable $callback): void
    {
        $lock = Cache::lock(self::CACHE_KEY . ':lock', 5);

        try {
            $lock->block(3);

            $metrics = Cache::get(self::CACHE_KEY, $this->emptyMetrics());
            $callback($metrics);
            Cache::put(self::CACHE_KEY, $metrics, self::CACHE_TTL);
        } catch (\Throwable $e) {
            Log::warning('MetricsCollector update failed', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            $lock?->release();
        }
    }

    /**
     * Return the empty metrics structure used when no data exists yet.
     *
     * @return array<string, mixed>
     */
    private function emptyMetrics(): array
    {
        return [
            'requests' => [
                'total' => 0,
                'by_endpoint' => [],
                'by_status_group' => [],
            ],
            'errors' => [
                'total' => 0,
                'by_path' => [],
            ],
            'queue' => [
                'total' => 0,
                'by_job' => [],
                'by_status' => [],
            ],
            'system_resources' => [],
            'gauges' => [],
            'started_at' => now()->toIso8601String(),
        ];
    }
}
