<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ForceJsonResponse
{
    /**
     * Force all API responses to be JSON.
     *
     * Sets the Accept header to application/json and ensures
     * that validation errors and exceptions return JSON.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force JSON acceptance for API routes
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        // Ensure response content type is JSON
        if ($response instanceof Response && !$response->headers->has('Content-Type')) {
            $response->headers->set('Content-Type', 'application/json');
        }

        // Add API version header
        if ($response instanceof Response) {
            $response->headers->set('X-Api-Version', 'v1');
        }

        return $response;
    }
}
