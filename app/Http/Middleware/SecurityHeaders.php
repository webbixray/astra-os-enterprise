<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware SecurityHeaders
 *
 * Applies a comprehensive set of HTTP security headers to every response.
 * Headers are loaded from config/security.php so they can be tuned without
 * touching code.  The Content-Security-Policy header is built dynamically
 * from the structured CSP configuration array.
 */
final class SecurityHeaders
{
    /**
     * List of hop-by-hop headers that must never be forwarded.
     *
     * @var list<string>
     */
    private const HOP_BY_HOP = [
        'Connection',
        'Keep-Alive',
        'Proxy-Authenticate',
        'Proxy-Authorization',
        'TE',
        'Trailer',
        'Transfer-Encoding',
        'Upgrade',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $this->applySecurityHeaders($response);
        } catch (\Throwable $e) {
            Log::warning('Failed to apply security headers: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Apply all configured security headers to the response.
     */
    private function applySecurityHeaders(Response $response): void
    {
        $headers = config('security.headers', []);
        $csp = config('security.csp', []);

        // Set each configured security header, avoiding duplicates.
        foreach ($headers as $name => $value) {
            if ($response->headers->has($name)) {
                continue;
            }
            $response->headers->set($name, $value);
        }

        // Build and set the Content-Security-Policy header.
        if ($csp !== [] && ! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', $this->buildCspString($csp));
        }

        // Strip hop-by-hop headers from the response.
        foreach (self::HOP_BY_HOP as $header) {
            $response->headers->remove($header);
        }
    }

    /**
     * Convert the structured CSP configuration array into a policy string.
     *
     * Example output:
     *   default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; ...
     *
     * @param  array<string, list<string>>  $directives
     */
    private function buildCspString(array $directives): string
    {
        $parts = [];

        foreach ($directives as $directive => $sources) {
            $value = implode(' ', $sources);
            $parts[] = $value !== '' ? $directive . ' ' . $value : $directive;
        }

        return implode('; ', $parts);
    }
}
