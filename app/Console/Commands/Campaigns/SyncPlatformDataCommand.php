<?php

declare(strict_types=1);

namespace App\Console\Commands\Campaigns;

use Illuminate\Console\Command;

class SyncPlatformDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:sync-platforms
        {--platform=all : Specific platform to sync (meta, google, linkedin, tiktok)}
        {--campaign-id= : Sync a specific campaign}
        {--dry-run : Show what would be synced without syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync campaign data with connected ad platforms';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $platform = $this->option('platform');
        $campaignId = $this->option('campaign-id');
        $dryRun = $this->option('dry-run');

        $this->components->info("Syncing campaign data with platforms...");

        $query = \App\Infrastructure\Persistence\Models\Campaign::query()
            ->whereIn('status', ['active', 'paused']);

        if ($campaignId) {
            $query->where('id', $campaignId);
        }

        $campaigns = $query->get();

        if ($dryRun) {
            $this->components->info("[DRY RUN] Would sync {$campaigns->count()} campaigns.");
            foreach ($campaigns as $campaign) {
                $this->line("  - {$campaign->name} ({$campaign->id})");
            }
            return self::SUCCESS;
        }

        $synced = 0;
        foreach ($campaigns as $campaign) {
            $platforms = is_array($campaign->platforms) ? $campaign->platforms : [];
            foreach ($platforms as $p) {
                if ($platform !== 'all' && $p !== $platform) {
                    continue;
                }
                // Dispatch sync job to queue
                \App\Jobs\SyncPlatformData::dispatch($campaign->id, $p)->onQueue('platforms');
                $synced++;
            }
        }

        $this->components->info("Dispatched {$synced} platform sync jobs for {$campaigns->count()} campaigns.");

        return self::SUCCESS;
    }
}
