<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CacheResponse — Middleware that caches API GET responses.
 *
 * Caches responses for a configurable TTL using a cache key derived
 * from the full URL (including query parameters) and the authenticated
 * user ID. Supports cache tags for grouped invalidation.
 *
 * Skips non-GET requests, authentication endpoints, and any route
 * containing dynamic content markers.
 */
class CacheResponse
{
    /**
     * Routes that should never be cached.
     */
    private const array EXCLUDED_PATTERNS = [
        '*auth*',
        '*login*',
        '*logout*',
        '*register*',
        '*password*',
        '*webhook*',
        '*callback*',
        '*export*',
        '*stream*',
        '*health*',
        '*metrics*',
        '*telescope*',
        '*horizon*',
        '*pulse*',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  int|null $ttl Override TTL in seconds
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?int $ttl = null): mixed
    {
        // Only cache GET requests
        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        // Skip excluded routes
        if ($this->isExcluded($request)) {
            return $next($request);
        }

        $cacheKey = $this->buildCacheKey($request);
        $cacheTtl = $ttl ?? (int) config('performance.cache.ttl.api_responses', 60);

        if ($cacheTtl <= 0) {
            return $next($request);
        }

        $tags = $this->getCacheTags($request);

        // Try to serve from cache
        $cached = $this->getFromCache($cacheKey, $tags);
        if ($cached !== null) {
            /** @var array{status: int, headers: array, content: string} $cached */
            return new JsonResponse(
                json_decode($cached['content'], true, 512, JSON_THROW_ON_ERROR),
                $cached['status'],
                $cached['headers'],
            );
        }

        /** @var Response|JsonResponse $response */
        $response = $next($request);

        // Only cache successful responses
        if ($this->shouldCache($response)) {
            $this->storeInCache($cacheKey, $tags, $cacheTtl, $response);
        }

        return $response;
    }

    /**
     * Determine if the request path is excluded from caching.
     */
    private function isExcluded(Request $request): bool
    {
        $path = $request->path();

        foreach (self::EXCLUDED_PATTERNS as $pattern) {
            if (str_contains($pattern, '*')) {
                $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i';
                if (preg_match($regex, $path)) {
                    return true;
                }
            } elseif (str_contains($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a unique cache key for the request.
     */
    private function buildCacheKey(Request $request): string
    {
        $queryParams = $request->query();
        ksort($queryParams);

        $parts = [
            'api_cache',
            $request->getMethod(),
            $request->path(),
            md5((string) json_encode($queryParams)),
        ];

        // Scope cache by authenticated user
        if ($request->user() !== null) {
            $parts[] = (string) $request->user()->getAuthIdentifier();
        }

        return implode(':', $parts);
    }

    /**
     * Get cache tags for the request.
     *
     * @return string[]
     */
    private function getCacheTags(Request $request): array
    {
        $tags = ['api'];

        // Add organization-scoped tag when available
        $orgId = $request->route('organization_id')
            ?? $request->header('X-Organization');

        if ($orgId !== null && is_string($orgId)) {
            $tags[] = 'org:' . $orgId;
        }

        // Add user-scoped tag
        if ($request->user() !== null) {
            $tags[] = 'user:' . $request->user()->getAuthIdentifier();
        }

        return $tags;
    }

    /**
     * Attempt to retrieve a cached response.
     *
     * @param  string        $cacheKey
     * @param  string[]|null $tags
     * @return array|null
     */
    private function getFromCache(string $cacheKey, array $tags): ?array
    {
        try {
            $store = Cache::tags($tags);

            $cached = $store->get($cacheKey);

            if ($cached !== null && is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable) {
            // Cache driver may not support tags; fall back to raw store
            $cached = Cache::get($cacheKey);

            if ($cached !== null && is_array($cached)) {
                return $cached;
            }
        }

        return null;
    }

    /**
     * Store the response in cache.
     *
     * @param  string            $cacheKey
     * @param  string[]|null     $tags
     * @param  int               $ttl
     * @param  Response|JsonResponse $response
     */
    private function storeInCache(string $cacheKey, array $tags, int $ttl, Response|JsonResponse $response): void
    {
        $content = $response->getContent();
        if ($content === false || $content === '') {
            return;
        }

        $data = [
            'status' => $response->getStatusCode(),
            'headers' => $this->filterHeaders($response->headers->all()),
            'content' => $content,
        ];

        try {
            Cache::tags($tags)->put($cacheKey, $data, $ttl);
        } catch (\Throwable) {
            // Fallback: store without tags
            Cache::put($cacheKey, $data, $ttl);
        }
    }

    /**
     * Determine if the response should be cached.
     */
    private function shouldCache(Response|JsonResponse $response): bool
    {
        // Only cache successful 2xx responses
        if (! $response->isSuccessful()) {
            return false;
        }

        // Don't cache streamed responses
        if ($response instanceof StreamedResponse) {
            return false;
        }

        // Don't cache responses with cache-control: no-store
        if ($response->headers->has('Cache-Control')) {
            $cacheControl = $response->headers->get('Cache-Control') ?? '';
            if (str_contains($cacheControl, 'no-store')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filter response headers to remove sensitive cookies and cache-busters.
     *
     * @param  array<string, array<string>> $headers
     * @return array<string, mixed>
     */
    private function filterHeaders(array $headers): array
    {
        $allowed = [
            'content-type',
            'content-encoding',
            'content-language',
            'x-content-type-options',
            'x-frame-options',
            'x-ratelimit-limit',
            'x-ratelimit-remaining',
        ];

        $filtered = [];
        foreach ($headers as $name => $values) {
            if (in_array(strtolower($name), $allowed, true)) {
                $filtered[$name] = $values[0] ?? '';
            }
        }

        return $filtered;
    }
}
