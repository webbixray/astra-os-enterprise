<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TelegramService
{
    private readonly string $token;

    private readonly string $apiBase;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token', '');
        $this->apiBase = "https://api.telegram.org/bot{$this->token}";
    }

    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => false,
        ], $options);

        return $this->call('sendMessage', $payload);
    }

    public function setWebhook(string $url): array
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query'],
        ]);
    }

    public function deleteWebhook(): array
    {
        return $this->call('deleteWebhook');
    }

    public function getWebhookInfo(): array
    {
        return $this->call('getWebhookInfo');
    }

    public function sendButtons(int $chatId, string $text, array $buttons): array
    {
        return $this->call('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons,
            ]),
        ]);
    }

    public function sendChatAction(int $chatId, string $action = 'typing'): array
    {
        return $this->call('sendChatAction', [
            'chat_id' => $chatId,
            'action' => $action,
        ]);
    }

    /**
     * Get updates (long polling for development).
     */
    public function getUpdates(int $offset = 0, int $timeout = 30, int $limit = 100): array
    {
        return $this->call('getUpdates', [
            'offset' => $offset,
            'timeout' => $timeout,
            'limit' => $limit,
            'allowed_updates' => ['message', 'callback_query'],
        ]);
    }

    private function call(string $method, array $params = []): array
    {
        if ($this->token === '') {
            Log::warning('Telegram bot token not configured');

            return ['ok' => false, 'description' => 'Bot token not configured'];
        }

        try {
            $response = Http::timeout(15)
                ->retry(2, 1000)
                ->post("{$this->apiBase}/{$method}", $params);

            $data = $response->json();

            if (! ($data['ok'] ?? false)) {
                Log::warning('Telegram API error', [
                    'method' => $method,
                    'error' => $data['description'] ?? 'Unknown error',
                ]);
            }

            return $data ?? ['ok' => false, 'description' => 'Empty response'];
        } catch (\Throwable $e) {
            Log::error('Telegram API request failed', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }
}
