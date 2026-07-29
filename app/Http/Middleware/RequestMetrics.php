<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequestMetrics
{
    /**
     * Handle an incoming request.
     *
     * Records request duration, peak memory usage, and attaches
     * observability headers to every response.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = hrtime(true);

        /** @var Response $response */
        $response = $next($request);

        $duration = (hrtime(true) - $start) / 1e6; // milliseconds
        $memory = memory_get_peak_usage(true) / 1024; // kilobytes

        $response->headers->set('X-Request-Duration-Ms', (string) round($duration, 1));
        $response->headers->set('X-Memory-Peak-Kb', (string) round($memory, 0));
        $response->headers->set('Server-Timing', sprintf('app;dur=%.1f', $duration));

        // Log a warning when a request exceeds the slow threshold (1 second).
        if ($duration > 1000) {
            logger()->warning('Slow request detected', [
                'method' => $request->method(),
                'path' => $request->path(),
                'duration_ms' => round($duration, 1),
                'memory_kb' => round($memory, 0),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $response;
    }
}
