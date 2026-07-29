<?php

declare(strict_types=1);

namespace App\Console\Commands\Telegram;

use App\Services\Telegram\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Poll for Telegram updates in development mode (no webhook required).
 *
 * This command runs continuously, fetching new updates from Telegram
 * and processing them locally. Useful during local development when
 * you don't have a public HTTPS endpoint for webhooks.
 *
 * Usage:
 *   php artisan telegram:poll
 *   php artisan telegram:poll --timeout=60
 */
class PollUpdatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll
        {--timeout=30 : Long-polling timeout in seconds (max 60)}
        {--limit=10 : Updates per poll cycle (max 100)}
        {--sleep=1 : Seconds to wait between poll cycles}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll for Telegram updates (development mode, no webhook needed)';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram): int
    {
        if (! $telegram->isConfigured()) {
            $this->components->error(
                'Telegram bot is not configured. Set TELEGRAM_BOT_TOKEN in your .env file.'
            );

            return self::FAILURE;
        }

        $timeout = min((int) $this->option('timeout'), 60);
        $limit = min((int) $this->option('limit'), 100);
        $sleep = (int) $this->option('sleep');

        $offset = 0;

        $this->components->info('🤖 Telegram bot polling started');
        $this->components->twoColumnDetail('Mode', 'Long-poll (development)');
        $this->components->twoColumnDetail('Timeout', "{$timeout}s");
        $this->components->twoColumnDetail('Limit', (string) $limit);
        $this->components->info('Press Ctrl+C to stop');

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () {
                $this->components->info("\n👋 Polling stopped.");
                exit(0);
            });
        }

        $pollCount = 0;

        while (true) {
            try {
                $updates = $telegram->getUpdates($offset, $limit, $timeout);

                foreach ($updates as $update) {
                    $updateId = $update['update_id'] ?? 0;

                    $this->components->task(
                        "Processing update #{$updateId}",
                        function () use ($telegram, $update) {
                            $telegram->handleWebhook($update);

                            return true;
                        },
                    );

                    $pollCount++;

                    // Move offset past this update
                    if ($updateId >= $offset) {
                        $offset = $updateId + 1;
                    }
                }

                if (empty($updates)) {
                    // No updates — brief pause before polling again
                    if ($sleep > 0) {
                        usleep($sleep * 1000000);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Telegram: polling error', [
                    'error' => $e->getMessage(),
                    'offset' => $offset,
                ]);

                $this->components->warn("Poll error: {$e->getMessage()}");

                // Wait before retrying
                sleep(5);
            }

            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }

        // Unreachable, but satisfies the return type
        return self::SUCCESS;
    }
}
