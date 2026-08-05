<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PrometheusMetrics
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('prometheus.enabled', true)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $method = $request->method();
        $path = $request->path();

        try {
            $response = $next($request);
            $statusCode = $response->getStatusCode();
        } catch (\Throwable $e) {
            $statusCode = 500;
            throw $e;
        } finally {
            $duration = microtime(true) - $startTime;

            // Record HTTP metrics
            $this->recordMetrics($method, $path, $statusCode, $duration);
        }

        return $response;
    }

    /**
     * Record HTTP metrics.
     */
    protected function recordMetrics(string $method, string $path, int $statusCode, float $duration): void
    {
        try {
            // HTTP requests total
            App::make(\App\Metrics\HttpRequestsTotal::class)->increment($method, $path, $statusCode);

            // HTTP request duration
            App::make(\App\Metrics\HttpRequestDuration::class)->observe($method, $path, $duration);
        } catch (\Throwable $e) {
            // Silently ignore metrics errors to avoid impacting request
            \Illuminate\Support\Facades\Log::debug('Prometheus metrics error', ['error' => $e->getMessage()]);
        }
    }
}