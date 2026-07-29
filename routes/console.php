<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Astra OS Task Schedule
|--------------------------------------------------------------------------
|
| Define the scheduled tasks for the Astra OS platform.
|
*/

// Process pending agent tasks every minute
Schedule::command('agents:process-tasks --batch-size=10')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/schedule-agents.log'));

// Monitor social mentions every 5 minutes
Schedule::command('social:monitor-mentions --limit=100')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/schedule-social.log'));

// Publish scheduled social posts every minute
Schedule::command('social:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/schedule-social.log'));

// Sync campaign data with platforms every 15 minutes
Schedule::command('campaigns:sync-platforms')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/schedule-campaigns.log'));

// Generate scheduled reports every hour
Schedule::command('campaigns:generate-reports')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/schedule-reports.log'));

// Prune old agent memories daily at midnight
Schedule::command('agents:prune-memory --days=90')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/schedule-maintenance.log'));

// Cleanup audit logs weekly on Sunday at 2 AM
Schedule::command('maintenance:cleanup-audit-logs --days=90')
    ->weeklyOn(0, '02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/schedule-maintenance.log'));

// Run horizon metrics snapshot every 5 minutes (for Pulse)
Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->runInBackground();

// Pulse check
Schedule::command('pulse:check')
    ->everyFiveMinutes()
    ->runInBackground();
