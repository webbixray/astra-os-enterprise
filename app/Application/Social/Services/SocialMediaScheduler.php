<?php

declare(strict_types=1);

namespace App\Application\Social\Services;

use App\Domain\Social\Entities\SocialPost;
use DateTimeImmutable;
use InvalidArgumentException;

final class SocialMediaScheduler
{
    private const int MIN_SCHEDULE_ADVANCE_MINUTES = 5;
    private const int MAX_SCHEDULE_DAYS_AHEAD = 90;

    private const array PLATFORM_RULES = [
        'twitter' => ['max_length' => 280, 'media_limit' => 4],
        'linkedin' => ['max_length' => 3000, 'media_limit' => 20],
        'facebook' => ['max_length' => 63206, 'media_limit' => 10],
        'instagram' => ['max_length' => 2200, 'media_limit' => 10],
    ];

    public function validateScheduleTime(DateTimeImmutable $scheduledAt, array $platforms): void
    {
        $now = new DateTimeImmutable();

        $minTime = $now->modify('+' . self::MIN_SCHEDULE_ADVANCE_MINUTES . ' minutes');
        if ($scheduledAt < $minTime) {
            throw new InvalidArgumentException(
                sprintf('Schedule time must be at least %d minutes in the future.', self::MIN_SCHEDULE_ADVANCE_MINUTES)
            );
        }

        $maxTime = $now->modify('+' . self::MAX_SCHEDULE_DAYS_AHEAD . ' days');
        if ($scheduledAt > $maxTime) {
            throw new InvalidArgumentException(
                sprintf('Cannot schedule more than %d days in advance.', self::MAX_SCHEDULE_DAYS_AHEAD)
            );
        }
    }

    public function queueForPublishing(SocialPost $post, DateTimeImmutable $scheduledAt): void
    {
        // In production: dispatch a queued job
        // SocialPostPublishJob::dispatch($post->getId())->delay($scheduledAt);
    }

    /**
     * @return array{ day: string, time: string }[]
     */
    public function getBestPostingTimes(string $platform): array
    {
        $bestTimes = [
            'twitter' => [
                ['day' => 'weekday', 'time' => '09:00'],
                ['day' => 'weekday', 'time' => '12:00'],
                ['day' => 'weekday', 'time' => '17:00'],
            ],
            'linkedin' => [
                ['day' => 'tuesday', 'time' => '10:00'],
                ['day' => 'wednesday', 'time' => '09:00'],
                ['day' => 'thursday', 'time' => '11:00'],
            ],
            'facebook' => [
                ['day' => 'weekday', 'time' => '13:00'],
                ['day' => 'saturday', 'time' => '10:00'],
            ],
            'instagram' => [
                ['day' => 'weekday', 'time' => '11:00'],
                ['day' => 'weekday', 'time' => '15:00'],
                ['day' => 'sunday', 'time' => '10:00'],
            ],
        ];

        return $bestTimes[$platform] ?? [];
    }
}
