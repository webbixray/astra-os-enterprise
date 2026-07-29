<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(DomainServiceProvider::class);
        $this->app->register(AstraOsServiceProvider::class);
        $this->app->register(QueryOptimizationServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register global middleware aliases
        $router = $this->app['router'];

        // Force JSON responses for API routes
        $router->aliasMiddleware('force-json', \App\Http\Middleware\ForceJsonResponse::class);

        // Organization access middleware
        $router->aliasMiddleware('organization.access', \App\Http\Middleware\OrganizationAccess::class);

        // Audit logger middleware
        $router->aliasMiddleware('audit.logger', \App\Http\Middleware\AuditLogger::class);

        // Response caching middleware
        $router->aliasMiddleware('cache.response', \App\Http\Middleware\CacheResponse::class);
        $router->aliasMiddleware('cache.response.long', \App\Http\Middleware\CacheResponse::class . ':3600');
        $router->aliasMiddleware('cache.response.short', \App\Http\Middleware\CacheResponse::class . ':30');

        // Configure Sanctum for SPA authentication
        if (config('sanctum.stateful')) {
            \Laravel\Sanctum\Sanctum::getAccessTokenFromRequestUsing(function ($request) {
                return $request->bearerToken()
                    ?? $request->cookie(config('sanctum.cookie_prefix', 'sanctum') . '_token');
            });
        }
    }
}
