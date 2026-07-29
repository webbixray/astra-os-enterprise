<?php

declare(strict_types=1);

namespace App\Console\Commands\Social;

use Illuminate\Console\Command;

class PublishScheduledPostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'social:publish-scheduled
        {--dry-run : Show what would be published without publishing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish social media posts that are scheduled for now or past';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->components->info('Publishing scheduled social posts...');

        $posts = \App\Infrastructure\Persistence\Models\SocialPost::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($dryRun) {
            $this->components->info("[DRY RUN] Would publish {$posts->count()} scheduled posts.");
            foreach ($posts as $post) {
                $this->line("  - {$post->id}: scheduled at {$post->scheduled_at}");
            }
            return self::SUCCESS;
        }

        $published = 0;
        foreach ($posts as $post) {
            \App\Jobs\PublishSocialPost::dispatch($post->id)->onQueue('social');
            $published++;
        }

        $this->components->info("Dispatched {$published} social post publishing jobs.");

        return self::SUCCESS;
    }
}
