<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SetupAstraOsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'astra-os:setup
        {--force : Force the operation to run without confirmation}
        {--seed : Seed the database after migration}
        {--with-demo : Include demo data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Astra OS for first use - runs migrations, seeds, and configures the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('🚀 Setting up Astra OS...');

        // Step 1: Generate application key
        if (! config('app.key')) {
            $this->components->task('Generating application key', function () {
                Artisan::call('key:generate', ['--force' => true]);
                return true;
            });
        } else {
            $this->components->info('✓ Application key already set');
        }

        // Step 2: Create storage link
        if (! File::exists(public_path('storage'))) {
            $this->components->task('Creating storage symlink', function () {
                Artisan::call('storage:link', ['--force' => true]);
                return true;
            });
        } else {
            $this->components->info('✓ Storage link already exists');
        }

        // Step 3: Publish configuration
        $this->components->task('Publishing Astra OS configuration', function () {
            Artisan::call('vendor:publish', [
                '--tag' => 'astra-os-config',
                '--force' => true,
            ]);
            return true;
        });

        // Step 4: Run database migrations
        $this->components->task('Running database migrations', function () {
            Artisan::call('migrate', ['--force' => true]);
            return true;
        });

        // Step 5: Seed the database
        if ($this->option('seed') || $this->option('with-demo')) {
            $this->components->task('Seeding database', function () {
                Artisan::call('db:seed', [
                    '--force' => true,
                    '--class' => $this->option('with-demo')
                        ? 'Database\Seeders\DemoDatabaseSeeder'
                        : 'Database\Seeders\DatabaseSeeder',
                ]);
                return true;
            });
        }

        // Step 6: Create Horizon configuration
        $this->components->task('Optimizing application', function () {
            Artisan::call('optimize', ['--force' => true]);
            return true;
        });

        $this->newLine();
        $this->components->info('✅ Astra OS setup completed successfully!');
        $this->components->info('   API: ' . config('app.url') . '/api/v1');
        $this->components->info('   Documentation: ' . config('app.url') . '/docs');

        if ($this->option('with-demo')) {
            $this->components->warn('   Demo data has been seeded. Reset with: php artisan migrate:fresh --seed');
        }

        return self::SUCCESS;
    }
}
