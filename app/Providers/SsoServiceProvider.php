<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Config;

class SsoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Only register SSO if enabled
        if (!config('services.sso.enabled', false)) {
            return;
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!config('services.sso.enabled', false)) {
            return;
        }

        // Configure OAuth2 providers via Socialite
        $this->configureSocialiteProviders();

        // Configure Passport for OAuth2 server if needed
        if (config('services.sso.passport_enabled', false)) {
            Passport::tokensExpireIn(now()->addDays(15));
            Passport::refreshTokensExpireIn(now()->addDays(30));
            Passport::personalAccessTokensExpireIn(now()->addMonths(6));
        }
    }

    /**
     * Configure Socialite OAuth providers.
     */
    protected function configureSocialiteProviders(): void
    {
        $providers = config('services.sso.providers', []);

        foreach ($providers as $name => $config) {
            if (empty($config['client_id']) || empty($config['client_secret'])) {
                continue;
            }

            Socialite::extend($name, function ($app) use ($name, $config) {
                $socialiteConfig = [
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect' => $config['redirect'] ?? config('app.url') . "/auth/{$name}/callback",
                ];

                // Build the provider dynamically
                $providerClass = $config['provider_class'] ?? "\\Laravel\\Socialite\\Two\\{$name}Provider";
                return new $providerClass(
                    $app->make('request'),
                    $socialiteConfig['client_id'],
                    $socialiteConfig['client_secret'],
                    $socialiteConfig['redirect']
                );
            });
        }
    }
}