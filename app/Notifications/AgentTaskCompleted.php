<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgentTaskCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $taskId,
        public readonly string $agentName,
        public readonly string $taskDescription,
        public readonly string $status,
        public readonly array $output = [],
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
        $statusEmoji = $this->status === 'completed' ? '✅' : '❌';

        return (new MailMessage)
            ->subject("{$statusEmoji} Agent Task {$this->status}: {$this->taskDescription}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Agent **{$this->agentName}** has completed a task.")
            ->line("**Task:** {$this->taskDescription}")
            ->line("**Status:** {$this->status}")
            ->action('View Task', url("/agents/tasks/{$this->taskId}"))
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
            'type' => 'agent.task.completed',
            'title' => "Agent Task {$this->status}",
            'body' => "Agent \"{$this->agentName}\" completed task: {$this->taskDescription}",
            'task_id' => $this->taskId,
            'agent_name' => $this->agentName,
            'task_description' => $this->taskDescription,
            'status' => $this->status,
            'output' => $this->output,
            'additional_data' => $this->additionalData,
        ];
    }
}
