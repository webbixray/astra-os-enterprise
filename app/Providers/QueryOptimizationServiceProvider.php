<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * QueryOptimizationServiceProvider — Registers database query logging
 * for slow queries, sets default pagination limits, configures eager
 * loading defaults, and registers model observers for cache invalidation.
 */
class QueryOptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerSlowQueryLogger();
        $this->configurePaginationDefaults();
        $this->configureEagerLoadingDefaults();
        $this->registerModelCacheObservers();
    }

    /**
     * Log slow queries exceeding the configured threshold.
     *
     * Uses the DB::listen event to capture all queries and logs those
     * whose execution time exceeds the threshold defined in config.
     */
    private function registerSlowQueryLogger(): void
    {
        $threshold = (int) config('performance.query.slow_query_threshold_ms', 100);

        if ($threshold <= 0) {
            return;
        }

        DB::listen(function (QueryExecuted $query) use ($threshold): void {
            $durationMs = (float) ($query->time ?? 0.0);

            if ($durationMs < $threshold) {
                return;
            }

            $context = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'duration_ms' => $durationMs,
                'connection' => $query->connectionName,
            ];

            // Add model hint when available from the stack trace
            $caller = $this->findEloquentCaller();
            if ($caller !== null) {
                $context['caller'] = $caller;
            }

            if ($durationMs >= $threshold * 3) {
                Log::warning('SLOW_QUERY', $context);
            } else {
                Log::info('SLOW_QUERY', $context);
            }
        });
    }

    /**
     * Set default pagination limits for the application.
     *
     * Overrides the default 15-per-page to our configured default,
     * and enforces a hard maximum to prevent resource exhaustion.
     */
    private function configurePaginationDefaults(): void
    {
        $defaultPerPage = (int) config('performance.query.default_results_per_page', 15);
        $maxPerPage = (int) config('performance.query.max_results_per_page', 100);

        // Apply the default per-page to all Eloquent paginators
        Builder::macro('defaultPerPage', fn () => $defaultPerPage);

        // Override the per-page via a macro that caps at the configured maximum
        Builder::macro('smartPaginate', function (?int $perPage = null, array $columns = ['*'], string $pageName = 'page', ?int $page = null) use ($maxPerPage) {
            /** @var Builder $this */
            $perPage = min($perPage ?? $defaultPerPage, $maxPerPage);

            return $this->paginate($perPage, $columns, $pageName, $page);
        });

        // Also override the global pagination default
        // This is less invasive — it only sets the default, not the max
        Model::setPerPage($defaultPerPage);
    }

    /**
     * Configure eager loading defaults.
     *
     * Applies a global scope that prevents N+1 queries by warning when
     * a lazy-loaded relationship is accessed outside of an eager-loading
     * context (only when APP_DEBUG is true).
     */
    private function configureEagerLoadingDefaults(): void
    {
        // In non-production environments, warn about lazy loading to catch N+1 issues
        if (! $this->app->isProduction()) {
            Model::preventLazyLoading(
                (bool) env('PREVENT_LAZY_LOADING', true),
            );
        }

        // Prevent silently discarding attributes (helps catch typos)
        Model::preventSilentlyDiscardingAttributes(
            ! $this->app->isProduction(),
        );

        // Prevent accessing missing attributes (helps catch schema mismatches)
        Model::preventAccessingMissingAttributes(
            ! $this->app->isProduction(),
        );
    }

    /**
     * Register model observers that invalidate cache on changes.
     *
     * Uses model lifecycle events to flush related cache tags
     * whenever an entity is created, updated, or deleted.
     */
    private function registerModelCacheObservers(): void
    {
        // Observer for all Domain models — flushes related model cache tags
        $flushCache = function (Model $model): void {
            try {
                $cacheService = app(\App\Services\Cache\CacheService::class);
                if ($cacheService !== null) {
                    $cacheService->flushModel($model::class);
                }
            } catch (\Throwable) {
                // Cache service may not be registered yet during boot
            }
        };

        $models = [
            \App\Models\Campaign::class,
            \App\Models\CampaignCreative::class,
            \App\Models\CampaignInsight::class,
            \App\Models\CampaignAnalytic::class,
            \App\Models\Agent::class,
            \App\Models\AgentTask::class,
            \App\Models\AgentConversation::class,
            \App\Models\AgentMemory::class,
            \App\Models\Workflow::class,
            \App\Models\WorkflowExecution::class,
            \App\Models\WorkflowTemplate::class,
            \App\Models\SocialAccount::class,
            \App\Models\SocialPost::class,
            \App\Models\SocialComment::class,
            \App\Models\SocialMention::class,
            \App\Models\Organization::class,
            \App\Models\OrganizationMember::class,
            \App\Models\Report::class,
            \App\Models\AuditLog::class,
        ];

        foreach ($models as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }

            // Use saving/saved/deleting lifecycle hooks
            $modelClass::saved(fn (Model $model) => $flushCache($model));
            $modelClass::deleted(fn (Model $model) => $flushCache($model));
        }
    }

    /**
     * Walk the backtrace to find the first caller that looks like an
     * Eloquent or application model/controller method.
     *
     * @return string|null A readable caller description
     */
    private function findEloquentCaller(): ?string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);

        foreach ($trace as $frame) {
            $class = $frame['class'] ?? '';
            $function = $frame['function'] ?? '';

            // Skip framework internals
            if (str_starts_with($class, 'Illuminate\\')) {
                continue;
            }

            // Skip this provider itself
            if (str_starts_with($class, __CLASS__)) {
                continue;
            }

            // Found an application frame
            if (str_starts_with($class, 'App\\')) {
                return sprintf('%s::%s', $class, $function);
            }
        }

        return null;
    }
}
