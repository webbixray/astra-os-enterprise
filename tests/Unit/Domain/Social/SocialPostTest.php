<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Social;

use App\Domain\Social\Entities\SocialPost;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * Unit tests for the SocialPost domain entity.
 *
 * Covers post creation (draft / scheduled), scheduling, publishing,
 * failure handling, media attachment, metrics tracking, and edge cases
 * including past-date scheduling and publishing from non-draft statuses.
 *
 * @package Tests\Unit\Domain\Social
 */
final class SocialPostTest extends TestCase
{
    // ---- Helpers ----

    private function createAccountId(): \Ramsey\Uuid\UuidInterface
    {
        return Uuid::uuid4();
    }

    private function createCampaignId(): \Ramsey\Uuid\UuidInterface
    {
        return Uuid::uuid4();
    }

    // ---- Happy Path: Creation ----

    #[Test]
    public function it_creates_a_draft_post_without_schedule(): void
    {
        $post = SocialPost::create(
            accountId: $this->createAccountId(),
            content: 'Hello world!',
        );

        $this->assertInstanceOf(SocialPost::class, $post);
        $this->assertSame('Hello world!', $post->getContent());
        $this->assertSame('draft', $post->getStatus());
        $this->assertTrue($post->isDraft());
        $this->assertFalse($post->isScheduled());
        $this->assertFalse($post->isPublished());
        $this->assertNull($post->getScheduledAt());
        $this->assertNull($post->getPublishedAt());
        $this->assertNull($post->getPlatformPostId());
        $this->assertEmpty($post->getMetrics());
        $this->assertEmpty($post->getMedia());
        $this->assertNotNull($post->getId());
        $this->assertNotNull($post->getAccountId());
    }

    #[Test]
    public function it_creates_a_scheduled_post_with_future_schedule(): void
    {
        $scheduledAt = new DateTimeImmutable('+7 days');

        $post = SocialPost::create(
            accountId: $this->createAccountId(),
            content: 'Scheduled post',
            scheduledAt: $scheduledAt,
        );

        $this->assertSame('scheduled', $post->getStatus());
        $this->assertTrue($post->isScheduled());
        $this->assertSame($scheduledAt, $post->getScheduledAt());
    }

    #[Test]
    public function it_creates_post_with_campaign_id(): void
    {
        $campaignId = $this->createCampaignId();

        $post = SocialPost::create(
            accountId: $this->createAccountId(),
            content: 'Campaign post',
            campaignId: $campaignId,
        );

        $this->assertSame($campaignId->toString(), $post->getCampaignId()->toString());
    }

    #[Test]
    public function it_creates_post_with_media_attachments(): void
    {
        $media = ['https://example.com/image1.jpg', 'https://example.com/image2.png'];

        $post = SocialPost::create(
            accountId: $this->createAccountId(),
            content: 'Post with images',
            media: $media,
        );

        $this->assertSame($media, $post->getMedia());
    }

    // ---- Scheduling ----

    #[Test]
    public function it_schedules_an_existing_draft(): void
    {
        $post = SocialPost::create(
            accountId: $this->createAccountId(),
            content: 'Will be scheduled',
        );

        $future = new DateTimeImmutable('+3 days');
        $post->schedule($future);

        $this->assertSame('scheduled', $post->getStatus());
        $this->assertTrue($post->isScheduled());
        $this->assertSame($future, $post->getScheduledAt());
    }

    #[Test]
    public function it_updates_timestamp_on_schedule(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'test');
        $originalUpdatedAt = $post->getUpdatedAt();

        usleep(2000);
        $post->schedule(new DateTimeImmutable('+1 day'));

        $this->assertGreaterThan($originalUpdatedAt, $post->getUpdatedAt());
    }

    // Note: The current domain entity does not validate that scheduledAt
    // is in the future. It accepts any DateTimeImmutable value.
    #[Test]
    public function it_accepts_past_date_on_schedule(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Late post');

        $past = new DateTimeImmutable('-2 days');
        $post->schedule($past);

        // The entity currently allows past dates — test actual behavior
        $this->assertSame('scheduled', $post->getStatus());
        $this->assertSame($past, $post->getScheduledAt());
    }

    // ---- Publishing ----

    #[Test]
    public function it_publishes_from_draft(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Publishing now');
        $post->publish('platform-post-123');

        $this->assertSame('published', $post->getStatus());
        $this->assertTrue($post->isPublished());
        $this->assertSame('platform-post-123', $post->getPlatformPostId());
        $this->assertNotNull($post->getPublishedAt());
    }

    #[Test]
    public function it_publishes_from_scheduled(): void
    {
        $post = SocialPost::create(
            $this->createAccountId(),
            'Scheduled publishing',
            scheduledAt: new DateTimeImmutable('+1 day'),
        );

        $post->publish('scheduled-post-id');

        $this->assertSame('published', $post->getStatus());
        $this->assertNotNull($post->getPublishedAt());
    }

    #[Test]
    public function it_updates_timestamp_on_publish(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'test');
        $originalUpdatedAt = $post->getUpdatedAt();

        usleep(2000);
        $post->publish('pid-1');

        $this->assertGreaterThan($originalUpdatedAt, $post->getUpdatedAt());
    }

    // Note: The current domain entity does not restrict publishing from
    // any status. It allows publish() regardless of current status.
    #[Test]
    public function it_publishes_from_failed_status(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Retry');
        $post->fail();
        $post->publish('retry-id');

        // Current behavior allows publishing from any status
        $this->assertSame('published', $post->getStatus());
    }

    // ---- Failure ----

    #[Test]
    public function it_marks_as_failed(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Will fail');
        $post->fail();

        $this->assertSame('failed', $post->getStatus());
    }

    #[Test]
    public function it_fails_from_any_status(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Fail from draft');
        $post->fail();
        $this->assertSame('failed', $post->getStatus());

        $post2 = SocialPost::create($this->createAccountId(), 'Fail from scheduled',
            scheduledAt: new DateTimeImmutable('+1 day'));
        $post2->fail();
        $this->assertSame('failed', $post2->getStatus());

        $post3 = SocialPost::create($this->createAccountId(), 'Fail from published');
        $post3->publish('pid');
        $post3->fail();
        $this->assertSame('failed', $post3->getStatus());
    }

    // ---- Content Updates ----

    #[Test]
    public function it_updates_content(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Original content');

        $post->setContent('Updated content');

        $this->assertSame('Updated content', $post->getContent());
    }

    // ---- Metrics ----

    #[Test]
    public function it_tracks_metrics(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Tracked post');
        $metrics = ['likes' => 42, 'shares' => 10, 'comments' => 5];

        $post->updateMetrics($metrics);

        $this->assertSame($metrics, $post->getMetrics());
    }

    #[Test]
    public function it_overwrites_previous_metrics(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Metric overwrite');
        $post->updateMetrics(['likes' => 10]);

        $post->updateMetrics(['likes' => 99, 'shares' => 3]);

        $this->assertSame(['likes' => 99, 'shares' => 3], $post->getMetrics());
    }

    #[Test]
    public function it_starts_with_empty_metrics(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'No metrics yet');

        $this->assertSame([], $post->getMetrics());
    }

    // ---- Timestamps via Trait ----

    #[Test]
    public function it_has_timestamps_on_creation(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Timestamped');

        $this->assertNotNull($post->getCreatedAt());
        $this->assertNotNull($post->getUpdatedAt());
    }

    // ---- Reconstitution ----

    #[Test]
    public function it_reconstitutes_from_persistence(): void
    {
        $id = Uuid::uuid4();
        $accountId = Uuid::uuid4();
        $campaignId = Uuid::uuid4();
        $scheduledAt = new DateTimeImmutable('+5 days');
        $publishedAt = new DateTimeImmutable('+6 days');
        $createdAt = new DateTimeImmutable('2024-01-01 00:00:00');
        $updatedAt = new DateTimeImmutable('2024-06-01 00:00:00');

        $post = SocialPost::reconstitute(
            $id,
            $accountId,
            $campaignId,
            'Reconstituted content',
            ['https://img.jpg'],
            $scheduledAt,
            $publishedAt,
            'published',
            'platform-abc',
            ['likes' => 100],
            $createdAt,
            $updatedAt,
        );

        $this->assertSame($id->toString(), $post->getId()->toString());
        $this->assertSame($accountId->toString(), $post->getAccountId()->toString());
        $this->assertSame($campaignId->toString(), $post->getCampaignId()->toString());
        $this->assertSame('Reconstituted content', $post->getContent());
        $this->assertSame(['https://img.jpg'], $post->getMedia());
        $this->assertSame('published', $post->getStatus());
        $this->assertSame('platform-abc', $post->getPlatformPostId());
        $this->assertSame(['likes' => 100], $post->getMetrics());
        $this->assertSame($createdAt, $post->getCreatedAt());
        $this->assertSame($updatedAt, $post->getUpdatedAt());
    }

    // ---- Edge Cases ----

    #[Test]
    public function it_accepts_empty_content(): void
    {
        $post = SocialPost::create($this->createAccountId(), '');

        $this->assertSame('', $post->getContent());
    }

    #[Test]
    public function it_accepts_null_campaign_id(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'No campaign');

        $this->assertNull($post->getCampaignId());
    }

    #[Test]
    public function it_accepts_empty_media_array(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'No media');

        $this->assertEmpty($post->getMedia());
    }

    #[Test]
    public function it_preserves_created_at_after_publish(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Stable created');
        $createdAt = $post->getCreatedAt();

        $post->publish('pid-999');

        $this->assertSame($createdAt, $post->getCreatedAt());
    }

    #[Test]
    public function it_updates_platform_post_id_on_publish(): void
    {
        $post = SocialPost::create($this->createAccountId(), 'Platform ID test');

        $this->assertNull($post->getPlatformPostId());

        $post->publish('abc-123-def');

        $this->assertSame('abc-123-def', $post->getPlatformPostId());
        $this->assertNotNull($post->getPublishedAt());
    }
}
