<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncPlatformData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $campaignId,
        public string $platform
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $campaign = \App\Infrastructure\Persistence\Models\Campaign::findOrFail($this->campaignId);

        // TODO: Implement platform-specific API calls
        // This would use the platform adapters to sync data
        \Illuminate\Support\Facades\Log::info("Syncing campaign {$this->campaignId} with {$this->platform}");
    }
}
