<?php

declare(strict_types=1);

namespace App\Console\Commands\Telegram;

use App\Services\Telegram\TelegramService;
use Illuminate\Console\Command;

/**
 * Set the Telegram bot webhook URL.
 *
 * Usage:
 *   php artisan telegram:set-webhook
 *   php artisan telegram:set-webhook --url=https://example.com/api/v1/telegram/webhook
 *   php artisan telegram:set-webhook --delete
 */
class SetWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook
        {--url= : The webhook URL (defaults to config services.telegram.webhook_url)}
        {--delete : Delete the current webhook instead of setting it}
        {--max-connections=40 : Maximum number of simultaneous HTTPS connections}
        {--secret= : Secret token to validate webhook requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set or delete the Telegram bot webhook URL';

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

        // --delete flag: remove the webhook
        if ($this->option('delete')) {
            return $this->deleteWebhook($telegram);
        }

        // Determine the webhook URL
        $url = $this->option('url') ?? config('services.telegram.webhook_url');

        if (! $url) {
            $this->components->error(
                'No webhook URL provided. Use --url= or set TELEGRAM_WEBHOOK_URL in your .env file.'
            );

            return self::FAILURE;
        }

        if (! str_starts_with($url, 'https://')) {
            $this->components->warn(
                'Webhook URL must be HTTPS. Telegram rejects non-HTTPS URLs.'
            );
        }

        $options = [
            'max_connections' => (int) $this->option('max-connections'),
        ];

        if ($secret = $this->option('secret')) {
            $options['secret_token'] = $secret;
        }

        $this->components->task('Setting Telegram webhook', function () use ($telegram, $url, $options) {
            $result = $telegram->setWebhook($url, $options);

            if (($result['ok'] ?? false) === true) {
                $this->components->info('✓ Webhook set successfully');

                return true;
            }

            $description = $result['description'] ?? 'Unknown error';
            $this->components->error("Failed: {$description}");

            return false;
        });

        // Show current webhook info
        $this->showWebhookInfo($telegram);

        return self::SUCCESS;
    }

    /**
     * Delete the current webhook.
     */
    private function deleteWebhook(TelegramService $telegram): int
    {
        $this->components->task('Deleting Telegram webhook', function () use ($telegram) {
            $result = $telegram->deleteWebhook();

            if (($result['ok'] ?? false) === true) {
                $this->components->info('✓ Webhook deleted');

                return true;
            }

            $this->components->error($result['description'] ?? 'Failed to delete webhook');

            return false;
        });

        return self::SUCCESS;
    }

    /**
     * Display current webhook information.
     */
    private function showWebhookInfo(TelegramService $telegram): void
    {
        $info = $telegram->getWebhookInfo();

        if (($info['ok'] ?? false) === true && isset($info['result'])) {
            $result = $info['result'];

            $this->components->twoColumnDetail('Webhook URL', $result['url'] ?? '(not set)');
            $this->components->twoColumnDetail(
                'Has Custom Certificate',
                $result['has_custom_certificate'] ?? false ? 'Yes' : 'No',
            );
            $this->components->twoColumnDetail(
                'Pending Updates',
                (string) ($result['pending_update_count'] ?? 0),
            );
            $this->components->twoColumnDetail(
                'Max Connections',
                (string) ($result['max_connections'] ?? 40),
            );

            if (! empty($result['last_error_date']) && ! empty($result['last_error_message'])) {
                $this->components->twoColumnDetail(
                    'Last Error',
                    $result['last_error_message'],
                );
            }
        }
    }
}
