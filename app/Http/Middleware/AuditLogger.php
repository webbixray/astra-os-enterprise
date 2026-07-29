<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AuditLog;
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

        // Persist audit log to database
        try {
            AuditLog::create([
                'user_id' => $user?->id,
                'action' => $request->method() . ' ' . $request->path(),
                'entity_type' => $this->resolveEntityType($request),
                'entity_id' => $request->route('id') ?? $request->route('campaign') ?? $request->route('agent') ?? $request->route('workflow'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'new_values' => $request->except(['password', 'password_confirmation', 'access_token', 'refresh_token']),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to persist audit log entry: ' . $e->getMessage());
        }
    }

    /**
     * Resolve the entity type from the request path.
     */
    private function resolveEntityType(Request $request): string
    {
        $path = $request->path();

        if (str_contains($path, '/campaigns/')) {
            return 'campaign';
        }
        if (str_contains($path, '/agents/')) {
            return 'agent';
        }
        if (str_contains($path, '/workflows/')) {
            return 'workflow';
        }
        if (str_contains($path, '/organizations/')) {
            return 'organization';
        }
        if (str_contains($path, '/auth/')) {
            return 'auth';
        }

        return 'api';
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
