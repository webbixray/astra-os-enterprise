<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCampaignReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $reportId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $report = \App\Infrastructure\Persistence\Models\Report::findOrFail($this->reportId);

        // TODO: Implement actual report generation logic
        $report->update(['last_run_at' => now()]);

        \Illuminate\Support\Facades\Log::info("Generated report {$this->reportId}");
    }
}
