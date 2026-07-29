<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

final class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        $this->hideSensitiveRequestDetails();

        Telescope::filter(function (IncomingEntry $entry): bool {
            if ($this->app->environment('local')) {
                return true;
            }

            // In non-local environments, only record entries that have
            // identifiable tags (e.g., user ID, job ID, etc.)
            return $entry->hasReportableError() || $entry->hasFailedJob();
        });
    }

    /**
     * Configure the Telescope authorization services.
     */
    protected function authorization(): void
    {
        Telescope::auth(function ($request): bool {
            return app()->environment('local') || Gate::check('viewTelescope', [$request->user()]);
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user): bool {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
