<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class AuditLogger
{
    /**
     * Log API requests to the audit log.
     *
     * Records the method, URL, authenticated user (if any),
     * request parameters, and response status for every API request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log API routes
        if (!$this->isApiRequest($request)) {
            return $response;
        }

        $this->logRequest($request, $response);

        return $response;
    }

    /**
     * Determine if the request is an API request.
     */
    private function isApiRequest(Request $request): bool
    {
        return str_starts_with($request->path(), 'api/');
    }

    /**
     * Log the request details to the audit channel.
     */
    private function logRequest(Request $request, Response $response): void
    {
        $user = $request->user();

        $logData = [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $user?->id,
            'response_status' => $response->getStatusCode(),
            'duration_ms' => $this->getRequestDuration(),
        ];

        // Only log sensitive fields in non-production environments
        if (app()->environment('local', 'staging')) {
            $logData['parameters'] = $request->except(['password', 'password_confirmation', 'access_token', 'refresh_token']);
        }

        Log::channel('audit')->info('API Request', $logData);

        // In production, this would also insert into an audit_logs table
        // \App\Infrastructure\Persistence\Models\AuditLog::create($logData);
    }

    /**
     * Calculate the request duration in milliseconds.
     */
    private function getRequestDuration(): float
    {
        if (defined('LARAVEL_START')) {
            return (microtime(true) - LARAVEL_START) * 1000;
        }

        return 0.0;
    }
}
