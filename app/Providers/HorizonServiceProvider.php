<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Register Horizon tags for monitoring
        Horizon::routeMailNotificationsTo(env('HORIZON_NOTIFICATION_EMAIL'));
        Horizon::routeSlackNotificationsTo(env('HORIZON_NOTIFICATION_SLACK_WEBHOOK_URL'));

        // Automatically tag jobs based on their class and queue
        Horizon::defaultTags(function ($job) {
            $tags = [];

            if (isset($job->queue)) {
                $tags[] = 'queue:'.$job->queue;
            }

            $tags[] = 'class:'.get_class($job);

            return $tags;
        });
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user): bool {
            return in_array($user->email, [
                //
            ]);
        });
    }
}
