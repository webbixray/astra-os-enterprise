<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishSocialPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $postId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $post = \App\Infrastructure\Persistence\Models\SocialPost::findOrFail($this->postId);
        $account = $post->account;

        // TODO: Implement platform-specific publishing via API
        $post->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        \Illuminate\Support\Facades\Log::info("Published social post {$this->postId}");
    }
}
