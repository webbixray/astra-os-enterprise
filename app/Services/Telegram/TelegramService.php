<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Core service for interacting with the Telegram Bot API.
 *
 * Handles sending messages, processing webhook updates, managing
 * inline keyboards, and rate-limited API calls.
 */
final class TelegramService
{
    private const API_BASE = 'https://api.telegram.org/bot';
    private const CACHE_KEY_PREFIX = 'telegram:chat:';
    private const CACHE_TTL = 86400; // 24 hours
    private const RATE_LIMIT_PER_SECOND = 30;

    private string $token;
    private string $apiBase;
    private int $lastRequestTime = 0;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token', '');
        $this->apiBase = self::API_BASE . $this->token;
    }

    /**
     * Check if the Telegram bot is configured with a valid token.
     */
    public function isConfigured(): bool
    {
        return $this->token !== '' && $this->token !== null;
    }

    /**
     * Send a text message to a Telegram chat.
     *
     * @param  int          $chatId         Target chat ID.
     * @param  string       $text           Message text (supports MarkdownV2 or HTML).
     * @param  array{
     *     parse_mode?: string,
     *     disable_web_page_preview?: bool,
     *     disable_notification?: bool,
     *     reply_to_message_id?: int,
     *     reply_markup?: array,
     * }                $options         Optional parameters.
     * @return array<string, mixed>         Decoded API response.
     *
     * @throws \RuntimeException When the bot is not configured.
     */
    public function sendMessage(int $chatId, string $text, array $options = []): array
    {
        $this->ensureConfigured();
        $this->rateLimit();

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $options['parse_mode'] ?? 'Markdown',
            'disable_web_page_preview' => $options['disable_web_page_preview'] ?? false,
            'disable_notification' => $options['disable_notification'] ?? false,
        ];

        if (isset($options['reply_to_message_id'])) {
            $payload['reply_to_message_id'] = $options['reply_to_message_id'];
        }

        if (isset($options['reply_markup'])) {
            $payload['reply_markup'] = $options['reply_markup'];
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiBase}/sendMessage", $payload);

            $response->throw();

            $result = $response->json();

            Log::debug('Telegram: message sent', [
                'chat_id' => $chatId,
                'length' => strlen($text),
                'ok' => $result['ok'] ?? false,
            ]);

            return $result ?? ['ok' => false];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Telegram: failed to send message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a message with an inline keyboard.
     *
     * @param  int                    $chatId    Target chat ID.
     * @param  string                 $text      Message text.
     * @param  array<int, array<int, array<string, string>>> $buttons  Inline keyboard rows.
     * @param  array<string, mixed>   $options   Additional sendMessage options.
     * @return array<string, mixed>
     */
    public function sendWithKeyboard(int $chatId, string $text, array $buttons, array $options = []): array
    {
        $options['reply_markup'] = [
            'inline_keyboard' => $buttons,
        ];

        return $this->sendMessage($chatId, $text, $options);
    }

    /**
     * Send a photo from a URL or file ID.
     *
     * @param  int          $chatId  Target chat ID.
     * @param  string       $photo   URL or Telegram file ID.
     * @param  string       $caption Optional caption.
     * @param  array<string, mixed> $options Additional options.
     * @return array<string, mixed>
     */
    public function sendPhoto(int $chatId, string $photo, string $caption = '', array $options = []): array
    {
        $this->ensureConfigured();
        $this->rateLimit();

        $payload = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => $options['parse_mode'] ?? 'Markdown',
        ];

        try {
            $response = Http::timeout(15)
                ->post("{$this->apiBase}/sendPhoto", $payload);

            $response->throw();

            return $response->json() ?? ['ok' => false];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Telegram: failed to send photo', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a document/file.
     *
     * @param  int          $chatId   Target chat ID.
     * @param  string       $document File path or Telegram file ID.
     * @param  string       $caption  Optional caption.
     * @param  array<string, mixed> $options  Additional options.
     * @return array<string, mixed>
     */
    public function sendDocument(int $chatId, string $document, string $caption = '', array $options = []): array
    {
        $this->ensureConfigured();
        $this->rateLimit();

        $payload = [
            'chat_id' => $chatId,
            'document' => $document,
            'caption' => $caption,
            'parse_mode' => $options['parse_mode'] ?? 'Markdown',
        ];

        try {
            $response = Http::timeout(30)
                ->attach('document', fopen($document, 'r'), basename($document))
                ->post("{$this->apiBase}/sendDocument", $payload);

            $response->throw();

            return $response->json() ?? ['ok' => false];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Telegram: failed to send document', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Edit a message that was sent earlier.
     *
     * @param  int    $chatId    Target chat ID.
     * @param  int    $messageId Message ID to edit.
     * @param  string $text      New text.
     * @param  array<string, mixed> $options   Additional options.
     * @return array<string, mixed>
     */
    public function editMessageText(int $chatId, int $messageId, string $text, array $options = []): array
    {
        $this->ensureConfigured();
        $this->rateLimit();

        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $options['parse_mode'] ?? 'Markdown',
        ];

        if (isset($options['reply_markup'])) {
            $payload['reply_markup'] = $options['reply_markup'];
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiBase}/editMessageText", $payload);

            $response->throw();

            return $response->json() ?? ['ok' => false];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Telegram: failed to edit message', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Answer a callback query from an inline keyboard.
     *
     * @param  string $callbackQueryId The callback_query.id from the update.
     * @param  string $text            Notification text.
     * @param  bool   $showAlert       Show as an alert instead of a toast.
     * @return array<string, mixed>
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): array
    {
        $this->ensureConfigured();
        $this->rateLimit();

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiBase}/answerCallbackQuery", [
                    'callback_query_id' => $callbackQueryId,
                    'text' => $text,
                    'show_alert' => $showAlert,
                ]);

            $response->throw();

            return $response->json() ?? ['ok' => false];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Telegram: failed to answer callback query', [
                'callback_query_id' => $callbackQueryId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process an incoming Telegram webhook update.
     *
     * Routes messages to the appropriate command handler.
     *
     * @param  array<string, mixed> $update The raw Telegram Update object.
     */
    public function handleWebhook(array $update): void
    {
        $message = $update['message'] ?? [];
        $callbackQuery = $update['callback_query'] ?? [];

        if (! empty($callbackQuery)) {
            $this->handleCallbackQuery($callbackQuery);
            return;
        }

        if (empty($message)) {
            return;
        }

        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'] ?? 0;
        $userId = $message['from']['id'] ?? 0;

        if ($chatId === 0) {
            return;
        }

        // Store the chat ID mapped to this user
        if ($userId > 0) {
            $this->cacheChatId($userId, $chatId);
        }

        if (str_starts_with($text, '/')) {
            $this->handleCommand($text, $chatId, $userId);
        }
    }

    /**
     * Route a parsed command to its handler.
     *
     * Looks up the linked Astra OS user by telegram_user_id, then
     * delegates to the appropriate logic.
     */
    private function handleCommand(string $text, int $chatId, int $userId): void
    {
        $parsed = TelegramCommandParser::parse($text);

        if ($parsed === null) {
            return;
        }

        // Load the TelegramBotController to handle the command
        $controller = app(\App\Http\Controllers\Api\V1\TelegramBotController::class);

        match ($parsed->command) {
            'start' => $this->sendMessage($chatId, self::getWelcomeMessage()),

            'help' => $this->sendMessage(
                $chatId,
                TelegramCommandParser::getHelpText(),
                ['parse_mode' => 'Markdown'],
            ),

            'status' => $this->sendMessage(
                $chatId,
                TelegramCommandParser::getSystemStatusText(),
                ['parse_mode' => 'Markdown'],
            ),

            'campaign' => $controller->handleCampaignCommand($chatId, $userId, $parsed),

            'agent' => $controller->handleAgentCommand($chatId, $userId, $parsed),

            'analytics' => $controller->handleAnalyticsCommand($chatId, $userId, $parsed),

            'link' => $controller->handleLinkCommand($chatId, $userId, $parsed),

            default => $this->sendMessage(
                $chatId,
                "❌ Unknown command: /{$parsed->command}\n\nSend /help to see available commands.",
            ),
        };
    }

    /**
     * Handle an inline keyboard callback query.
     */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $data = $callbackQuery['data'] ?? '';
        $chatId = $callbackQuery['message']['chat']['id'] ?? 0;
        $userId = $callbackQuery['from']['id'] ?? 0;
        $callbackId = $callbackQuery['id'] ?? '';

        if ($chatId === 0 || $callbackId === '') {
            return;
        }

        // Parse callback data: "campaign:show:uuid" or "agent:show:uuid"
        $parts = explode(':', $data);
        $action = $parts[0] ?? '';
        $subAction = $parts[1] ?? '';
        $entityId = $parts[2] ?? '';

        $controller = app(\App\Http\Controllers\Api\V1\TelegramBotController::class);

        match ($action) {
            'campaign' => $controller->handleCampaignCallback($chatId, $userId, $callbackId, $subAction, $entityId),
            'agent' => $controller->handleAgentCallback($chatId, $userId, $callbackId, $subAction, $entityId),
            default => $this->answerCallbackQuery($callbackId, 'Unknown action'),
        };
    }

    /**
     * Set the bot's webhook URL.
     *
     * @param  string $url    Public HTTPS URL pointing to the webhook endpoint.
     * @param  array{
     *     max_connections?: int,
     *     allowed_updates?: list<string>,
     *     secret_token?: string,
     * } $options Additional webhook options.
     * @return array<string, mixed>
     */
    public function setWebhook(string $url, array $options = []): array
    {
        $this->ensureConfigured();

        $payload = [
            'url' => $url,
            'max_connections' => $options['max_connections'] ?? 40,
            'allowed_updates' => $options['allowed_updates'] ?? ['message', 'callback_query'],
        ];

        if (isset($options['secret_token'])) {
            $payload['secret_token'] = $options['secret_token'];
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiBase}/setWebhook", $payload);

            $response->throw();

            $result = $response->json();

            Log::info('Telegram: webhook set', [
                'url' => $url,
                'ok' => $result['ok'] ?? false,
                'description' => $result['description'] ?? '',
            ]);

            return $result ?? ['ok' => false];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Telegram: failed to set webhook', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete the current webhook (used for switching to poll mode).
     *
     * @return array<string, mixed>
     */
    public function deleteWebhook(): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiBase}/deleteWebhook");

            $response->throw();

            $result = $response->json();

            Log::info('Telegram: webhook deleted', [
                'ok' => $result['ok'] ?? false,
            ]);

            return $result ?? ['ok' => false];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Telegram: failed to delete webhook', [
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the current webhook status.
     *
     * @return array<string, mixed>
     */
    public function getWebhookInfo(): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiBase}/getWebhookInfo");

            $response->throw();

            return $response->json() ?? ['ok' => false];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Telegram: failed to get webhook info', [
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Poll for updates (used in development mode without webhooks).
     *
     * @param  int   $offset  The update ID offset (last processed ID + 1).
     * @param  int   $limit   Max updates to return (1-100).
     * @param  int   $timeout Long-polling timeout in seconds.
     * @return array<int, array<string, mixed>> Array of Update objects.
     */
    public function getUpdates(int $offset = 0, int $limit = 100, int $timeout = 30): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::timeout($timeout + 5)
                ->post("{$this->apiBase}/getUpdates", [
                    'offset' => $offset,
                    'limit' => min($limit, 100),
                    'timeout' => $timeout,
                    'allowed_updates' => ['message', 'callback_query'],
                ]);

            $response->throw();

            $result = $response->json();

            return $result['result'] ?? [];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Telegram: failed to get updates', [
                'offset' => $offset,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get the bot's user information.
     *
     * @return array<string, mixed>
     */
    public function getMe(): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::timeout(10)
                ->post("{$this->apiBase}/getMe");

            $response->throw();

            return $response->json() ?? ['ok' => false];
        } catch (RequestException|ConnectionException $e) {
            Log::error('Telegram: failed to get bot info', [
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get the cached chat ID for a Telegram user.
     *
     * @param  int      $userId Telegram user ID.
     * @return int|null Chat ID if cached, null otherwise.
     */
    public function getCachedChatId(int $userId): ?int
    {
        $chatId = Cache::get(self::CACHE_KEY_PREFIX . $userId);
        return $chatId !== null ? (int) $chatId : null;
    }

    /**
     * Cache a chat ID for a Telegram user.
     */
    private function cacheChatId(int $userId, int $chatId): void
    {
        Cache::put(self::CACHE_KEY_PREFIX . $userId, $chatId, self::CACHE_TTL);
    }

    /**
     * Get the welcome message for the /start command.
     */
    private static function getWelcomeMessage(): string
    {
        $botUsername = config('services.telegram.bot_username', 'AstraOSBot');

        return implode("\n", [
            "*👋 Welcome to Astra OS Enterprise!*",
            '',
            "I'm *{$botUsername}*, your AI-powered campaign management assistant.",
            '',
            'I can help you:',
            '• 📋 Monitor your marketing campaigns',
            '• 🤖 Check AI agent status and tasks',
            '• 📊 View analytics summaries',
            '• ⚡ Get quick system status updates',
            '',
            'To get started, link your account:',
            '*/link your@email.com*',
            '',
            'Then try:',
            '*/help* — See all available commands',
            '*/status* — System overview',
            '*/campaign list* — Your active campaigns',
            '*/agent list* — Your AI agents',
        ]);
    }

    /**
     * Ensure the bot token is configured.
     *
     * @throws \RuntimeException
     */
    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException(
                'Telegram bot is not configured. Set TELEGRAM_BOT_TOKEN in your .env file.'
            );
        }
    }

    /**
     * Simple rate limiter: ensures no more than N requests per second.
     */
    private function rateLimit(): void
    {
        $now = (int) (microtime(true) * 1000);
        $elapsed = $now - $this->lastRequestTime;
        $minInterval = (int) ceil(1000 / self::RATE_LIMIT_PER_SECOND);

        if ($elapsed < $minInterval) {
            usleep(($minInterval - $elapsed) * 1000);
        }

        $this->lastRequestTime = (int) (microtime(true) * 1000);
    }
}
