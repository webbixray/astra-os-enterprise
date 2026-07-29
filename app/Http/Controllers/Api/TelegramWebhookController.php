<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Telegram\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles incoming Telegram webhook updates and user-Telegram linking.
 *
 * @group Telegram
 */
final class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly TelegramService $telegram,
    ) {}

    /**
     * Receive an incoming update from Telegram.
     *
     * This is the endpoint you register as your Telegram bot's webhook URL.
     * It processes messages, commands, and callback queries.
     *
     * @bodyParam update_id int required The update's unique identifier.
     * @bodyParam message object A new incoming message.
     * @bodyParam callback_query object A new incoming callback query.
     *
     * @response 200 {"status": "ok"}
     * @response 422 {"status": "error", "message": "Invalid update payload"}
     */
    public function webhook(Request $request): JsonResponse
    {
        $update = $request->all();

        if (empty($update)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid update payload',
            ], 422);
        }

        Log::debug('Telegram: webhook received', [
            'update_id' => $update['update_id'] ?? null,
            'has_message' => isset($update['message']),
            'has_callback' => isset($update['callback_query']),
        ]);

        try {
            $this->telegram->handleWebhook($update);
        } catch (\Throwable $e) {
            Log::error('Telegram: webhook processing error', [
                'error' => $e->getMessage(),
                'update_id' => $update['update_id'] ?? null,
            ]);
        }

        // Always return 200 to acknowledge receipt
        return response()->json(['status' => 'ok']);
    }

    /**
     * Link a Telegram chat to an Astra OS user account.
     *
     * Called by the user with their email address. Sends a verification
     * code to the user's email. Once verified via the web app, the
     * Telegram chat ID is permanently linked.
     *
     * @bodyParam email string required The user's registered email address.
     *
     * @response 200 {"status": "ok", "message": "Verification code sent to your email."}
     * @response 404 {"status": "error", "message": "No user found with that email."}
     */
    public function link(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'telegram_chat_id' => 'required|integer',
            'telegram_user_id' => 'required|integer',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'No user found with that email.',
            ], 404);
        }

        // Generate a verification code (6 digits)
        $code = (string) random_int(100000, 999999);

        // Store the pending link in cache
        \Illuminate\Support\Facades\Cache::put(
            "telegram:link:{$user->id}",
            [
                'code' => $code,
                'telegram_chat_id' => (int) $request->input('telegram_chat_id'),
                'telegram_user_id' => (int) $request->input('telegram_user_id'),
            ],
            600, // 10 minutes
        );

        // Send verification code via Telegram
        $this->telegram->sendMessage(
            (int) $request->input('telegram_chat_id'),
            "*🔗 Link Your Account*\n\nA verification code has been sent to *{$user->email}*.\n\nUse the code in the Astra OS web app to complete linking.\n\nCode: `{$code}`\n\n_This code expires in 10 minutes._",
        );

        Log::info('Telegram: account link initiated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'telegram_user_id' => (int) $request->input('telegram_user_id'),
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Verification code sent to your email.',
        ]);
    }

    /**
     * Verify and confirm the Telegram account link.
     *
     * @bodyParam user_id int required The Astra OS user ID.
     * @bodyParam code string required The 6-digit verification code.
     *
     * @response 200 {"status": "ok", "message": "Account linked successfully."}
     * @response 400 {"status": "error", "message": "Invalid or expired verification code."}
     */
    public function verifyLink(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'code' => 'required|string|size:6',
        ]);

        $userId = (int) $request->input('user_id');
        $code = $request->input('code');

        $pending = \Illuminate\Support\Facades\Cache::get("telegram:link:{$userId}");

        if (! $pending || $pending['code'] !== $code) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.',
            ], 400);
        }

        $user = User::findOrFail($userId);
        $user->update([
            'telegram_chat_id' => (string) $pending['telegram_chat_id'],
            'telegram_user_id' => (string) $pending['telegram_user_id'],
            'telegram_settings' => ['linked_at' => now()->toIso8601String()],
        ]);

        \Illuminate\Support\Facades\Cache::forget("telegram:link:{$userId}");

        // Notify the user on Telegram
        $this->telegram->sendMessage(
            (int) $pending['telegram_chat_id'],
            "*✅ Account Linked!*\n\nYour Astra OS account has been successfully linked.\nUse /help to see available commands.",
        );

        Log::info('Telegram: account linked successfully', [
            'user_id' => $userId,
            'telegram_chat_id' => $pending['telegram_chat_id'],
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Account linked successfully.',
        ]);
    }

    /**
     * Unlink a Telegram account from the user.
     *
     * @response 200 {"status": "ok", "message": "Telegram account unlinked."}
     */
    public function unlink(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::findOrFail((int) $request->input('user_id'));
        $chatId = $user->telegram_chat_id;

        $user->update([
            'telegram_chat_id' => null,
            'telegram_user_id' => null,
            'telegram_settings' => null,
        ]);

        // Notify on Telegram if we still can
        if ($chatId) {
            $this->telegram->sendMessage(
                (int) $chatId,
                'Your Astra OS account has been unlinked from this chat.',
            );
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Telegram account unlinked.',
        ]);
    }
}
