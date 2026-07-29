<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $campaignId,
        public readonly string $campaignName,
        public readonly array $summary,
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
        $impressions = number_format($this->summary['impressions'] ?? 0);
        $clicks = number_format($this->summary['clicks'] ?? 0);
        $spend = number_format($this->summary['total_spend'] ?? 0, 2);

        return (new MailMessage)
            ->subject("Campaign Completed: {$this->campaignName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your campaign **{$this->campaignName}** has completed.")
            ->line("**Summary:**")
            ->line("- Impressions: {$impressions}")
            ->line("- Clicks: {$clicks}")
            ->line("- Total Spend: \${$spend}")
            ->action('View Report', url("/campaigns/{$this->campaignId}/analytics"))
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
            'type' => 'campaign.completed',
            'title' => 'Campaign Completed',
            'body' => "Campaign \"{$this->campaignName}\" has completed.",
            'campaign_id' => $this->campaignId,
            'campaign_name' => $this->campaignName,
            'summary' => $this->summary,
            'additional_data' => $this->additionalData,
        ];
    }
}
