<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\User;
use App\Services\Notifications\Channels\DatabaseChannel;
use App\Services\Notifications\Channels\MailChannel;
use App\Services\Notifications\Channels\WebhookChannel;
use Illuminate\Support\Facades\Log;

final class NotificationService
{
    /**
     * Default notification preferences per channel.
     *
     * @var array<string, bool>
     */
    private const DEFAULT_PREFERENCES = [
        'database' => true,
        'mail' => true,
        'webhook' => false,
    ];

    /**
     * Priority levels for notifications.
     */
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public function __construct(
        private readonly DatabaseChannel $database,
        private readonly MailChannel $mail,
        private readonly WebhookChannel $webhook,
    ) {}

    /**
     * Send a notification to a single user through their enabled channels.
     *
     * @param User   $user  The recipient user.
     * @param string $type  Notification type identifier (e.g. 'campaign.launched').
     * @param string $title Human-readable title.
     * @param string $body  Notification body / message.
     * @param array  $data  Optional payload data transmitted alongside the notification.
     * @param string $priority One of the PRIORITY_* constants.
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = [],
        string $priority = self::PRIORITY_NORMAL,
    ): void {
        $preferences = $this->getUserPreferences($user);

        foreach ($preferences as $channel => $enabled) {
            if (! $enabled) {
                continue;
            }

            try {
                match ($channel) {
                    'database' => $this->database->send($user, $type, $title, $body, $data, $priority),
                    'mail' => $this->mail->send($user, $type, $title, $body, $data, $priority),
                    'webhook' => $this->webhook->send($user, $type, $title, $body, $data, $priority),
                    default => null,
                };
            } catch (\Throwable $e) {
                Log::warning('Notification delivery failed for channel', [
                    'channel' => $channel,
                    'user_id' => $user->id,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send a notification to multiple users at once (batch).
     *
     * @param User[] $users
     */
    public function sendBatch(
        array $users,
        string $type,
        string $title,
        string $body,
        array $data = [],
        string $priority = self::PRIORITY_NORMAL,
    ): void {
        foreach ($users as $user) {
            $this->send($user, $type, $title, $body, $data, $priority);
        }
    }

    /**
     * Send a notification to all members of an organization.
     *
     * @param string $organizationId
     */
    public function sendToOrganization(
        string $organizationId,
        string $type,
        string $title,
        string $body,
        array $data = [],
        string $priority = self::PRIORITY_NORMAL,
    ): void {
        $users = User::whereHas('memberships', function ($query) use ($organizationId) {
            $query->where('organization_id', $organizationId);
        })->get();

        $this->sendBatch($users->all(), $type, $title, $body, $data, $priority);
    }

    /**
     * Get a user's notification preferences.
     *
     * @return array<string, bool>
     */
    public function getUserPreferences(User $user): array
    {
        $preferences = $user->notificationPreferences()
            ->pluck('enabled', 'channel')
            ->toArray();

        return array_merge(self::DEFAULT_PREFERENCES, $preferences);
    }

    /**
     * Update a user's notification preference for a specific channel.
     */
    public function setUserPreference(User $user, string $channel, bool $enabled): void
    {
        $user->notificationPreferences()->updateOrCreate(
            ['channel' => $channel],
            ['enabled' => $enabled],
        );
    }

    /**
     * Reset a user's notification preferences to defaults.
     */
    public function resetUserPreferences(User $user): void
    {
        $user->notificationPreferences()->delete();
    }
}
