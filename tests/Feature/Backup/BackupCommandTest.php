<?php

declare(strict_types=1);

namespace Tests\Feature\Backup;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

#[Group('feature')]
class BackupCommandTest extends TestCase
{
    public function test_backup_command_exists(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('backup:run', $commands);
        $this->assertArrayHasKey('backup:clean', $commands);
        $this->assertArrayHasKey('backup:monitor', $commands);
    }

    public function test_backup_config_loaded(): void
    {
        $config = Config::get('backup');
        
        $this->assertArrayHasKey('backup', $config);
        $this->assertArrayHasKey('notifications', $config);
        $this->assertArrayHasKey('monitor_backups', $config);
        $this->assertArrayHasKey('cleanup', $config);
    }

    public function test_backup_run_command_help(): void
    {
        $this->artisan('backup:run', ['--help' => true])
            ->expectsOutputToContain('Run the backup')
            ->assertExitCode(0);
    }

    public function test_backup_clean_command_help(): void
    {
        $this->artisan('backup:clean', ['--help' => true])
            ->expectsOutputToContain('Clean up old backups')
            ->assertExitCode(0);
    }

    public function test_backup_monitor_command_help(): void
    {
        $this->artisan('backup:monitor', ['--help' => true])
            ->expectsOutputToContain('Monitor backup health')
            ->assertExitCode(0);
    }

    public function test_backup_disk_configuration(): void
    {
        $disk = Config::get('backup.backup.destination.disks.0', 'local');
        $this->assertEquals('local', $disk);
    }

    public function test_backup_retention_configuration(): void
    {
        $cleanup = Config::get('backup.cleanup.default_strategy');
        
        $this->assertArrayHasKey('keep_all_backups_for_days', $cleanup);
        $this->assertArrayHasKey('keep_weekly_backups_for_weeks', $cleanup);
        $this->assertArrayHasKey('keep_monthly_backups_for_months', $cleanup);
        $this->assertArrayHasKey('keep_yearly_backups_for_years', $cleanup);
    }

    public function test_backup_notifications_configured(): void
    {
        $notifications = Config::get('backup.notifications.notifications');
        
        $this->assertArrayHasKey(\Spatie\Backup\Notifications\Notifications\BackupHasFailed::class, $notifications);
        $this->assertArrayHasKey(\Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFound::class, $notifications);
        $this->assertArrayHasKey(\Spatie\Backup\Notifications\Notifications\BackupWasSuccessful::class, $notifications);
    }

    public function test_backup_monitor_health_checks(): void
    {
        $monitors = Config::get('backup.monitor_backups');
        
        $this->assertCount(3, $monitors); // Daily, Weekly, Monthly
        
        foreach ($monitors as $monitor) {
            $this->assertArrayHasKey('name', $monitor);
            $this->assertArrayHasKey('disks', $monitor);
            $this->assertArrayHasKey('health_checks', $monitor);
            
            $healthChecks = $monitor['health_checks'];
            $this->assertArrayHasKey('maximum_age_in_days', $healthChecks);
            $this->assertArrayHasKey('maximum_storage_in_megabytes', $healthChecks);
        }
    }
}