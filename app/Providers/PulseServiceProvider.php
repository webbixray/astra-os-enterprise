<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\PulseServiceProvider as BasePulseServiceProvider;

class PulseServiceProvider extends BasePulseServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        Pulse::user(fn ($user) => [
            'name' => $user?->name ?? 'Guest',
            'extra' => $user?->email ?? '',
            'avatar' => $user?->profile_photo_url ?? null,
        ]);
    }

    /**
     * Register the Pulse gate.
     *
     * This gate determines who can access Pulse in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewPulse', function ($user = null) {
            return app()->environment('local')
                || optional($user)->email === 'admin@astra-os.com';
        });
    }
}
