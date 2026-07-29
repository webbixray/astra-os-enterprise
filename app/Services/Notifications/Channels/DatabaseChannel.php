<?php

declare(strict_types=1);

namespace App\Services\Notifications\Channels;

use App\Models\User;
use App\Services\Notifications\NotificationService;

final class DatabaseChannel
{
    /**
     * Send a notification via the database (in-app notifications table).
     *
     * Stores the notification as a record in the notifications table so that
     * it can be polled via an in-app notification centre.
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = [],
        string $priority = NotificationService::PRIORITY_NORMAL,
    ): void {
        $user->notifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'priority' => $priority,
            'read_at' => null,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(User $user, string $notificationId): void
    {
        $user->notifications()
            ->where('id', $notificationId)
            ->update(['read_at' => now()]);
    }

    /**
     * Mark all notifications as read for the given user.
     */
    public function markAllAsRead(User $user): void
    {
        $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread notifications for a user.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnread(User $user)
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get paginated notifications for a user.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaginated(User $user, int $perPage = 15)
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Delete a notification.
     */
    public function delete(User $user, string $notificationId): void
    {
        $user->notifications()
            ->where('id', $notificationId)
            ->delete();
    }

    /**
     * Purge all read notifications older than the given number of days.
     */
    public function purgeOldRead(User $user, int $days = 30): int
    {
        return $user->notifications()
            ->whereNotNull('read_at')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
