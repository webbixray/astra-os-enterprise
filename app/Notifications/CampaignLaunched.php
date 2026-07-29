<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignLaunched extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $campaignId,
        public readonly string $campaignName,
        public readonly array $additionalData = [],
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Campaign Launched: {$this->campaignName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your campaign **{$this->campaignName}** has been launched successfully.")
            ->action('View Campaign', url("/campaigns/{$this->campaignId}"))
            ->line('Monitor your campaign analytics to track performance.')
            ->salience('Thank you for using Astra OS!');
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'campaign.launched',
            'title' => 'Campaign Launched',
            'body' => "Campaign \"{$this->campaignName}\" has been launched successfully.",
            'campaign_id' => $this->campaignId,
            'campaign_name' => $this->campaignName,
            'additional_data' => $this->additionalData,
        ];
    }
}
