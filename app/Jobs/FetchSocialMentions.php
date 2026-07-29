<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchSocialMentions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $accountId,
        public int $limit = 50
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $account = \App\Infrastructure\Persistence\Models\SocialAccount::findOrFail($this->accountId);

        // TODO: Implement platform-specific mention fetching
        \Illuminate\Support\Facades\Log::info("Fetching mentions for account {$this->accountId}");
    }
}
