<?php

declare(strict_types=1);

namespace App\Console\Commands\Agents;

use Illuminate\Console\Command;

class PruneAgentMemoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:prune-memory
        {--days=90 : Archive memories older than this many days}
        {--dry-run : Show what would be pruned without actually pruning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune and archive old agent memories';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $query = \App\Infrastructure\Persistence\Models\AgentMemory::query()
            ->where('importance', '<', config('agents.providers.memory.importance_threshold', 4))
            ->where('last_accessed_at', '<', $cutoff);

        if ($dryRun) {
            $count = $query->count();
            $this->components->info("[DRY RUN] Would prune {$count} low-importance memories older than {$days} days.");
            return self::SUCCESS;
        }

        $pruned = $query->delete();
        $this->components->info("Pruned {$pruned} low-importance memories older than {$days} days.");

        return self::SUCCESS;
    }
}
