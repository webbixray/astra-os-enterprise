<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AstraOsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge configuration files
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/astra-os/general.php',
            'astra-os.general'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/astra-os/features.php',
            'astra-os.features'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/agents/providers.php',
            'agents.providers'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/agents/roles.php',
            'agents.roles'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/campaigns/platforms.php',
            'campaigns.platforms'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/campaigns/defaults.php',
            'campaigns.defaults'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/workflows/nodes.php',
            'workflows.nodes'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/workflows/templates.php',
            'workflows.templates'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration files
        $this->publishes([
            __DIR__ . '/../../config/astra-os' => config_path('astra-os'),
            __DIR__ . '/../../config/agents' => config_path('agents'),
            __DIR__ . '/../../config/campaigns' => config_path('campaigns'),
            __DIR__ . '/../../config/workflows' => config_path('workflows'),
        ], 'astra-os-config');

        // Register commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\SetupAstraOsCommand::class,
                \App\Console\Commands\Agents\ProcessAgentTasksCommand::class,
                \App\Console\Commands\Agents\PruneAgentMemoryCommand::class,
                \App\Console\Commands\Campaigns\SyncPlatformDataCommand::class,
                \App\Console\Commands\Campaigns\GenerateReportsCommand::class,
                \App\Console\Commands\Social\MonitorSocialMentionsCommand::class,
                \App\Console\Commands\Social\PublishScheduledPostsCommand::class,
                \App\Console\Commands\Maintenance\CleanupAuditLogsCommand::class,
            ]);
        }
    }
}
