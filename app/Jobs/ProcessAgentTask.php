<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAgentTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 3;
    public array $backoff = [5, 15, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $taskId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $task = \App\Infrastructure\Persistence\Models\AgentTask::findOrFail($this->taskId);
        $task->update(['status' => 'in_progress']);

        try {
            // Get agent configuration
            $agent = $task->agent;

            // Determine AI provider and model based on agent config
            $provider = $agent->model_config['provider'] ?? config('agents.providers.default', 'openai');
            $model = $agent->model_config['model'] ?? 'gpt-4o';

            // TODO: Implement actual AI provider call
            // For now, simulate processing
            $output = [
                'response' => 'Task processed successfully by ' . $agent->name,
                'provider_used' => $provider,
                'model_used' => $model,
                'tokens_used' => rand(100, 500),
            ];

            $task->update([
                'status' => 'completed',
                'output' => $output,
                'completed_at' => now(),
            ]);

        } catch (\Throwable $e) {
            $task->update([
                'status' => 'failed',
                'output' => ['error' => $e->getMessage()],
                'completed_at' => now(),
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 30);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $e): void
    {
        $task = \App\Infrastructure\Persistence\Models\AgentTask::find($this->taskId);
        if ($task) {
            $task->update([
                'status' => 'failed',
                'output' => ['error' => 'Max retries exceeded: ' . $e->getMessage()],
                'completed_at' => now(),
            ]);
        }
    }
}
