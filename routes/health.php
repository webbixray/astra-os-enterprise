<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health check routes (unauthenticated)
|--------------------------------------------------------------------------
|
| These endpoints are used by load balancers, orchestrators (Kubernetes,
| Docker Swarm, Nomad), and monitoring systems to determine application
| health.  They MUST remain accessible without authentication.
|
|   GET /api/health          — Liveness probe
|   GET /api/health/readiness — Readiness probe
|   GET /api/health/startup   — Startup probe
|
*/

Route::prefix('health')->group(function () {
    Route::get('/', [HealthController::class, 'liveness'])
        ->name('health.liveness');

    Route::get('/readiness', [HealthController::class, 'readiness'])
        ->name('health.readiness');

    Route::get('/startup', [HealthController::class, 'startup'])
        ->name('health.startup');
});
