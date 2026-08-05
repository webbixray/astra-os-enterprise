<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\RenderTextFormat;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the collector registry
        $this->app->singleton(CollectorRegistry::class, function () {
            return new CollectorRegistry(new InMemory());
        });

        // Bind the renderer
        $this->app->singleton(RenderTextFormat::class, function () {
            return new RenderTextFormat();
        });

        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/prometheus.php',
            'prometheus'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!config('prometheus.enabled')) {
            return;
        }

        // Register the metrics endpoint
        $this->registerMetricsRoute();

        // Register default metrics collectors
        $this->registerDefaultMetrics();

        // Register middleware for HTTP metrics
        $this->app['router']->pushMiddlewareToGroup('api', \App\Http\Middleware\PrometheusMetrics::class);

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/prometheus.php' => config_path('prometheus.php'),
        ], 'prometheus-config');
    }

    /**
     * Register the metrics exposition endpoint.
     */
    protected function registerMetricsRoute(): void
    {
        \Illuminate\Support\Facades\Route::get(
            config('prometheus.path', 'metrics'),
            [\App\Http\Controllers\PrometheusMetricsController::class, 'index']
        )->name('prometheus.metrics')
        ->middleware(config('prometheus.middleware', ['web']));
    }

    /**
     * Register default metrics collectors.
     */
    protected function registerDefaultMetrics(): void
    {
        if (config('prometheus.default_metrics.http_requests_total')) {
            \App\Metrics\HttpRequestsTotal::register();
        }
        if (config('prometheus.default_metrics.http_request_duration_seconds')) {
            \App\Metrics\HttpRequestDuration::register();
        }
        if (config('prometheus.default_metrics.queue_jobs_total')) {
            \App\Metrics\QueueJobsTotal::register();
        }
        if (config('prometheus.default_metrics.database_queries_total')) {
            \App\Metrics\DatabaseQueriesTotal::register();
        }
    }
}