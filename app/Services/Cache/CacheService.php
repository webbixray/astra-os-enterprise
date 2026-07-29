<?php

declare(strict_types=1);

namespace App\Services\Cache;

use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CacheService — Advanced caching layer for Astra OS Enterprise.
 *
 * Implements cache-aside pattern with tag-based invalidation,
 * model cache warming, and cache hit/miss statistics.
 */
class CacheService
{
    private const string STATS_KEY = 'cache_service:stats';

    private CacheRepository $store;

    private bool $tagsEnabled;

    private array $prefixes;

    private array $ttls;

    /**
     * @param CacheFactory $factory Laravel cache factory
     */
    public function __construct(private readonly CacheFactory $factory)
    {
        $this->store = $factory->store();
        $this->tagsEnabled = (bool) config('performance.cache.tags_enabled', true);
        $this->prefixes = (array) config('performance.cache.prefixes', [
            'models' => 'model',
            'queries' => 'query',
            'api' => 'api',
        ]);
        $this->ttls = (array) config('performance.cache.ttl', [
            'models' => 3600,
            'queries' => 300,
            'api_responses' => 60,
            'analytics' => 900,
        ]);
    }

    /**
     * Retrieve an item from cache using cache-aside pattern.
     * Returns the cached value or calls $callback to generate and store it.
     *
     * @template T
     * @param  string        $key      Cache key
     * @param  int           $ttl      Time-to-live in seconds
     * @param  callable(): T $callback Generator callback
     * @param  string[]|null $tags     Optional cache tags for grouped invalidation
     * @return T
     */
    public function remember(string $key, int $ttl, callable $callback, ?array $tags = null): mixed
    {
        $store = $this->getStore($tags);

        $value = $store->get($key);

        if ($value !== null) {
            $this->recordHit($key);

            return $value;
        }

        $this->recordMiss($key);

        /** @var T $value */
        $value = $callback();

        $store->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Remember a value forever (until explicitly forgotten or tags invalidated).
     *
     * @template T
     * @param  string        $key      Cache key
     * @param  callable(): T $callback Generator callback
     * @param  string[]|null $tags     Optional cache tags
     * @return T
     */
    public function rememberForever(string $key, callable $callback, ?array $tags = null): mixed
    {
        $store = $this->getStore($tags);

        $value = $store->get($key);

        if ($value !== null) {
            $this->recordHit($key);

            return $value;
        }

        $this->recordMiss($key);

        /** @var T $value */
        $value = $callback();

        $store->forever($key, $value);

        return $value;
    }

    /**
     * Retrieve a cached model entity by key.
     *
     * @template TEntity of object
     * @param  string             $entityClass Entity class name (used in cache key prefix)
     * @param  string             $id          Entity identifier
     * @param  callable(): TEntity $callback   Generator callback
     * @param  int|null           $ttl         Override TTL (default model TTL)
     * @return TEntity
     */
    public function rememberModel(string $entityClass, string $id, callable $callback, ?int $ttl = null): object
    {
        $key = $this->modelKey($entityClass, $id);
        $ttl ??= (int) ($this->ttls['models'] ?? 3600);

        $tags = $this->tagsEnabled ? [$this->prefixes['models'], $entityClass] : null;

        /** @var TEntity */
        return $this->remember($key, $ttl, $callback, $tags);
    }

    /**
     * Invalidate all cached entries for a given entity class.
     */
    public function flushModel(string $entityClass): void
    {
        if ($this->tagsEnabled) {
            Cache::tags([$this->prefixes['models'], $entityClass])->flush();
        }
    }

    /**
     * Invalidate a specific model cache entry.
     */
    public function forgetModel(string $entityClass, string $id): void
    {
        $this->forget($this->modelKey($entityClass, $id));
    }

    /**
     * Cache a query result.
     *
     * @template T
     * @param  string        $signature Unique query signature (e.g. hashed SQL + params)
     * @param  callable(): T $callback  Query executor
     * @param  int|null      $ttl       Override TTL (default query TTL)
     * @return T
     */
    public function rememberQuery(string $signature, callable $callback, ?int $ttl = null): mixed
    {
        $key = $this->queryKey($signature);
        $ttl ??= (int) ($this->ttls['queries'] ?? 300);

        $tags = $this->tagsEnabled ? [$this->prefixes['queries']] : null;

        /** @var T */
        return $this->remember($key, $ttl, $callback, $tags);
    }

    /**
     * Invalidate all cached query results.
     */
    public function flushQueries(): void
    {
        if ($this->tagsEnabled) {
            Cache::tags([$this->prefixes['queries']])->flush();
        }
    }

    /**
     * Cache an API response.
     *
     * @template T
     * @param  string        $key      API cache key
     * @param  callable(): T $callback
     * @param  int|null      $ttl      Override TTL (default API TTL)
     * @param  string[]|null $tags     Additional tags for grouped invalidation
     * @return T
     */
    public function rememberApiResponse(string $key, callable $callback, ?int $ttl = null, ?array $tags = []): mixed
    {
        $key = $this->apiKey($key);
        $ttl ??= (int) ($this->ttls['api_responses'] ?? 60);

        $cacheTags = $this->tagsEnabled
            ? array_merge([$this->prefixes['api']], $tags)
            : null;

        /** @var T */
        return $this->remember($key, $ttl, $callback, $cacheTags);
    }

    /**
     * Invalidate all cached API responses, optionally scoped to extra tags.
     */
    public function flushApiResponses(?array $tags = []): void
    {
        if (! $this->tagsEnabled) {
            return;
        }

        $cacheTags = array_merge([$this->prefixes['api']], $tags);
        Cache::tags($cacheTags)->flush();
    }

    /**
     * Check if a key exists in the cache.
     */
    public function has(string $key): bool
    {
        return $this->store->has($key);
    }

    /**
     * Retrieve a value from cache without generating on miss.
     */
    public function get(string $key): mixed
    {
        return $this->store->get($key);
    }

    /**
     * Store a value in cache.
     */
    public function put(string $key, mixed $value, int $ttl): void
    {
        $this->store->put($key, $value, $ttl);
    }

    /**
     * Remove a key from cache.
     */
    public function forget(string $key): void
    {
        $this->store->forget($key);
    }

    /**
     * Flush the cache entirely (or the current tag scope).
     */
    public function flush(): void
    {
        $this->store->flush();
    }

    /**
     * Get the current cache store, optionally scoped to tags.
     */
    private function getStore(?array $tags): CacheRepository|TaggedCache
    {
        if ($tags !== null && $this->tagsEnabled) {
            return Cache::tags($tags);
        }

        return $this->store;
    }

    /**
     * Build a model cache key.
     */
    private function modelKey(string $entityClass, string $id): string
    {
        return sprintf('%s:%s:%s', $this->prefixes['models'], str_replace('\\', '.', $entityClass), $id);
    }

    /**
     * Build a query cache key.
     */
    private function queryKey(string $signature): string
    {
        return sprintf('%s:%s', $this->prefixes['queries'], $signature);
    }

    /**
     * Build an API response cache key.
     */
    private function apiKey(string $key): string
    {
        return sprintf('%s:%s', $this->prefixes['api'], $key);
    }

    /**
     * Record a cache hit for statistics.
     */
    private function recordHit(string $key): void
    {
        $this->incrementStat('hits');
    }

    /**
     * Record a cache miss for statistics.
     */
    private function recordMiss(string $key): void
    {
        $this->incrementStat('misses');
    }

    /**
     * Increment a named counter in the stats store.
     */
    private function incrementStat(string $name): void
    {
        try {
            $this->store->increment(sprintf('%s:%s', self::STATS_KEY, $name));
        } catch (\Throwable) {
            // Stats collection is best-effort
        }
    }

    /**
     * Retrieve cache hit/miss statistics.
     *
     * @return array{hits: int, misses: int, total: int, hit_rate: float}
     */
    public function getStatistics(): array
    {
        $hits = (int) $this->store->get(sprintf('%s:%s', self::STATS_KEY, 'hits'), 0);
        $misses = (int) $this->store->get(sprintf('%s:%s', self::STATS_KEY, 'misses'), 0);
        $total = $hits + $misses;

        return [
            'hits' => $hits,
            'misses' => $misses,
            'total' => $total,
            'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 2) : 0.0,
        ];
    }

    /**
     * Reset cache statistics counters.
     */
    public function resetStatistics(): void
    {
        try {
            $this->store->forget(sprintf('%s:%s', self::STATS_KEY, 'hits'));
            $this->store->forget(sprintf('%s:%s', self::STATS_KEY, 'misses'));
        } catch (\Throwable) {
            // Best-effort
        }
    }

    /**
     * Pre-warm the cache for multiple model keys.
     *
     * Useful during deployment or after cache flush to avoid a thundering herd.
     *
     * @param array<int, array{key: string, ttl: int, callback: callable, tags: string[]|null}> $items
     */
    public function warmMany(array $items): void
    {
        foreach ($items as $item) {
            try {
                $this->remember(
                    $item['key'],
                    $item['ttl'],
                    $item['callback'],
                    $item['tags'] ?? null,
                );
            } catch (\Throwable $e) {
                Log::warning('CacheService: failed to warm key {key}', [
                    'key' => $item['key'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
