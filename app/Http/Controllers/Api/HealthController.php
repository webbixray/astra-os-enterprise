<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class HealthController extends Controller
{
    /**
     * Basic liveness check.
     *
     * Returns a lightweight 200 OK response to confirm the application
     * process is running and able to handle HTTP requests.
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => config('astra-os.general.version', '1.2.0'),
            'service' => 'astra-os-enterprise',
        ]);
    }

    /**
     * Readiness probe.
     *
     * Verifies that critical downstream dependencies (database, cache,
     * environment) are reachable and responsive.  Returns HTTP 503 when
     * any check fails.
     */
    public function readiness(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'environment' => $this->checkEnvironment(),
        ];

        $healthy = collect($checks)->every(fn (array $c): bool => $c['healthy']);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'healthy' => $healthy,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    /**
     * Startup probe.
     *
     * A deep check that validates every service the application depends
     * on, including configuration loading and system uptime.  Intended
     * for initial container / pod startup gates.
     */
    public function startup(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'config' => $this->checkConfigLoaded(),
            'environment' => $this->checkEnvironment(),
        ];

        $healthy = collect($checks)->every(fn (array $c): bool => $c['healthy']);

        return response()->json([
            'status' => $healthy ? 'ok' : 'failed',
            'message' => $healthy ? 'Application started successfully' : 'Application startup failed',
            'healthy' => $healthy,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
            'uptime' => $this->getUptime(),
        ], $healthy ? 200 : 503);
    }

    /**
     * Verify the database connection is alive.
     *
     * @return array{healthy: bool, message: string, driver?: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'healthy' => true,
                'message' => 'Database connection OK',
                'driver' => DB::getDriverName(),
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify the cache driver is responsive.
     *
     * @return array{healthy: bool, message: string, driver?: string}
     */
    private function checkCache(): array
    {
        try {
            Cache::store()->has('health-check-key');

            return [
                'healthy' => true,
                'message' => 'Cache connection OK',
                'driver' => config('cache.default'),
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm the application environment is configured.
     *
     * @return array{healthy: bool, message: string, app_env: string}
     */
    private function checkEnvironment(): array
    {
        $env = app()->environment();

        return [
            'healthy' => true,
            'message' => "Environment: {$env}",
            'app_env' => $env,
        ];
    }

    /**
     * Check that the Astra OS config has been loaded correctly.
     *
     * @return array{healthy: bool, message: string, version: string}
     */
    private function checkConfigLoaded(): array
    {
        $version = config('astra-os.general.version');

        return [
            'healthy' => $version !== null,
            'message' => $version
                ? "Config loaded, version {$version}"
                : 'Config not loaded',
            'version' => $version ?? 'unknown',
        ];
    }

    /**
     * Retrieve the system boot time via `uptime -s`.
     *
     * Returns null when the exec function is disabled or the command
     * fails (e.g. inside a container without the host's procfs).
     */
    private function getUptime(): ?string
    {
        if (function_exists('exec')) {
            /** @var list<string> $output */
            @exec('uptime -s 2>/dev/null', $output, $code);

            if ($code === 0 && ! empty($output[0])) {
                return trim($output[0]);
            }
        }

        return null;
    }
}
