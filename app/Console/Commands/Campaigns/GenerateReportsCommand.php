<?php

declare(strict_types=1);

namespace App\Console\Commands\Campaigns;

use Illuminate\Console\Command;

class GenerateReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:generate-reports
        {--schedule=now : Schedule identifier or now for immediate}
        {--type= : Specific report type (weekly, monthly, custom)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and distribute scheduled campaign reports';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $schedule = $this->option('schedule');
        $type = $this->option('type');

        $this->components->info('Generating scheduled reports...');

        $query = \App\Infrastructure\Persistence\Models\Report::query()
            ->where('status', 'active');

        if ($type) {
            $query->where('type', $type);
        }

        $reports = $query->get();

        $generated = 0;
        foreach ($reports as $report) {
            \App\Jobs\GenerateCampaignReport::dispatch($report->id)->onQueue('reports');
            $generated++;
        }

        $this->components->info("Dispatched {$generated} report generation jobs.");

        return self::SUCCESS;
    }
}
