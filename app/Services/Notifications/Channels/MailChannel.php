<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class MailChannel
{
    /**
     * Send a notification via email.
     *
     * Uses Laravel's Mail facade to send a simple email notification.
     * For production use, consider implementing a dedicated Mailable class.
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = [],
        string $priority = NotificationService::PRIORITY_NORMAL,
    ): void {
        if (empty($user->email)) {
            Log::warning('Mail channel skipped – user has no email address', [
                'user_id' => $user->id,
                'type' => $type,
            ]);

            return;
        }

        Mail::send([], [], function ($message) use ($user, $type, $title, $body, $data, $priority) {
            $message->to($user->email)
                ->subject($title)
                ->text(view('emails.notification', [
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => $data,
                    'priority' => $priority,
                ])->render());
        });
    }
}
