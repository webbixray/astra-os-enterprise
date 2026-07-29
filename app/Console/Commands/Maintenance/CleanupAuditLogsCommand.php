<?php

declare(strict_types=1);

namespace App\Console\Commands\Maintenance;

use Illuminate\Console\Command;

class CleanupAuditLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:cleanup-audit-logs
        {--days=90 : Delete audit logs older than this many days}
        {--dry-run : Show what would be deleted without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old audit log entries to manage database size';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $query = \App\Infrastructure\Persistence\Models\AuditLog::query()
            ->where('created_at', '<', $cutoff);

        if ($dryRun) {
            $count = $query->count();
            $this->components->info("[DRY RUN] Would delete {$count} audit log entries older than {$days} days.");
            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->components->info("Deleted {$deleted} audit log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
