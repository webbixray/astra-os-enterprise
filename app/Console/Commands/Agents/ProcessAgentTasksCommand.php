<?php

declare(strict_types=1);

namespace App\Console\Commands\Agents;

use Illuminate\Console\Command;

class ProcessAgentTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:process-tasks
        {--batch-size=10 : Number of tasks to process per batch}
        {--timeout=120 : Maximum execution time per task in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending agent tasks in the queue';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $timeout = (int) $this->option('timeout');

        $this->components->info("Processing up to {$batchSize} pending agent tasks...");

        // Dispatch batch of agent tasks to the queue
        $dispatched = \App\Infrastructure\Persistence\Models\AgentTask::query()
            ->where('status', 'pending')
            ->whereNull('started_at')
            ->limit($batchSize)
            ->get()
            ->each(function ($task) {
                // Dispatch to Laravel queue for async processing
                \App\Jobs\ProcessAgentTask::dispatch($task)->onQueue('agents');
                $task->update(['started_at' => now()]);
            });

        $count = $dispatched->count();
        $this->components->info("Dispatched {$count} tasks to agent queue.");

        return self::SUCCESS;
    }
}
