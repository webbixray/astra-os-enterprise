<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    /**
     * Create a new job instance.
     *
     * @param string      $userId   The recipient user's ID.
     * @param string      $type     Notification type identifier.
     * @param string      $title    Human-readable title.
     * @param string      $body     Notification body.
     * @param array       $data     Optional payload data.
     * @param string      $priority Notification priority.
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
        public readonly string $priority = 'normal',
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $user = \App\Models\User::find($this->userId);

        if (! $user) {
            \Illuminate\Support\Facades\Log::warning('SendNotificationJob: User not found', [
                'user_id' => $this->userId,
                'type' => $this->type,
            ]);

            return;
        }

        $notificationService->send(
            user: $user,
            type: $this->type,
            title: $this->title,
            body: $this->body,
            data: $this->data,
            priority: $this->priority,
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $e): void
    {
        \Illuminate\Support\Facades\Log::error('SendNotificationJob failed', [
            'user_id' => $this->userId,
            'type' => $this->type,
            'error' => $e->getMessage(),
        ]);
    }
}
