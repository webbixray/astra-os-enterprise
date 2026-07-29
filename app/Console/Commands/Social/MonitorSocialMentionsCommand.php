<?php

declare(strict_types=1);

namespace App\Console\Commands\Social;

use Illuminate\Console\Command;

class MonitorSocialMentionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'social:monitor-mentions
        {--limit=50 : Maximum mentions to process}
        {--dry-run : Show results without processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for new social media mentions and process them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->components->info('Checking for new social media mentions...');

        $accounts = \App\Infrastructure\Persistence\Models\SocialAccount::query()
            ->where('is_active', true)
            ->get();

        $totalMentions = 0;
        foreach ($accounts as $account) {
            if ($dryRun) {
                $this->line("[DRY RUN] Would check mentions for {$account->platform}:{$account->account_name}");
                $totalMentions += 10; // Simulated count
            } else {
                \App\Jobs\FetchSocialMentions::dispatch($account->id, $limit)->onQueue('social');
                $totalMentions += $limit;
            }
        }

        $verb = $dryRun ? 'Would check' : 'Dispatched checks for';
        $this->components->info("{$verb} {$accounts->count()} accounts (up to {$totalMentions} mentions).");

        return self::SUCCESS;
    }
}
