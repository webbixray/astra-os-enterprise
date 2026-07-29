<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register domain-to-infrastructure bindings.
     */
    public function register(): void
    {
        // Repositories
        // $this->app->bind(
        //     \Domain\Campaign\Repositories\CampaignRepositoryInterface::class,
        //     \Infrastructure\Persistence\Repositories\EloquentCampaignRepository::class
        // );

        // Services
        // $this->app->bind(
        //     \Domain\Agent\Services\AIServiceInterface::class,
        //     \Infrastructure\AI\Services\OpenAIService::class
        // );

        // Use Cases
        // $this->app->bind(
        //     \Application\Campaign\UseCases\CreateCampaignUseCase::class,
        //     \Application\Campaign\UseCases\CreateCampaignUseCase::class
        // );
    }

    /**
     * Bootstrap any domain services.
     */
    public function boot(): void
    {
        //
    }
}
