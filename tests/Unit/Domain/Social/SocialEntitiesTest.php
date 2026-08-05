<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Social;

use Tests\TestCase;
use App\Domain\Social\Entities\SocialPost;
use App\Domain\Social\Entities\SocialMention;
use Ramsey\Uuid\Uuid;

#[Group('unit')]
class SocialEntitiesTest extends TestCase
{
    public function test_social_post_creation(): void
    {
        $accountId = Uuid::uuid4();
        $campaignId = Uuid::uuid4();
        
        $post = SocialPost::create(
            accountId: $accountId,
            content: 'Test social media post content',
            campaignId: $campaignId,
            media: ['https://example.com/image.jpg'],
            scheduledAt: now()->addHour()
        );
        
        $this->assertInstanceOf(SocialPost::class, $post);
        $this->assertEquals($accountId, $post->getAccountId());
        $this->assertEquals('Test social media post content', $post->getContent());
        $this->assertEquals($campaignId, $post->getCampaignId());
        $this->assertEquals(SocialPost::STATUS_SCHEDULED, $post->getStatus());
    }

    public function test_social_post_draft_creation(): void
    {
        $accountId = Uuid::uuid4();
        
        $post = SocialPost::create(
            accountId: $accountId,
            content: 'Draft post content'
        );
        
        $this->assertEquals(SocialPost::STATUS_DRAFT, $post->getStatus());
        $this->assertNull($post->getScheduledAt());
    }

    public function test_social_post_schedule(): void
    {
        $accountId = Uuid::uuid4();
        $post = SocialPost::create(
            accountId: $accountId,
            content: 'Post to schedule'
        );
        
        $scheduledAt = now()->addDay();
        $post->schedule($scheduledAt);
        
        $this->assertEquals(SocialPost::STATUS_SCHEDULED, $post->getStatus());
        $this->assertEquals($scheduledAt, $post->getScheduledAt());
    }

    public function test_social_post_publish(): void
    {
        $accountId = Uuid::uuid4();
        $post = SocialPost::create(
            accountId: $accountId,
            content: 'Post to publish'
        );
        
        $platformPostId = 'platform-12345';
        $post->publish($platformPostId);
        
        $this->assertEquals(SocialPost::STATUS_PUBLISHED, $post->getStatus());
        $this->assertEquals($platformPostId, $post->getPlatformPostId());
        $this->assertNotNull($post->getPublishedAt());
    }

    public function test_social_post_fail(): void
    {
        $accountId = Uuid::uuid4();
        $post = SocialPost::create(
            accountId: $accountId,
            content: 'Post to fail'
        );
        
        $post->fail();
        
        $this->assertEquals(SocialPost::STATUS_FAILED, $post->getStatus());
    }

    public function test_social_post_metrics_update(): void
    {
        $accountId = Uuid::uuid4();
        $post = SocialPost::create(
            accountId: $accountId,
            content: 'Post with metrics'
        );
        
        $metrics = ['impressions' => 1000, 'clicks' => 50, 'engagement_rate' => 5.0];
        $post->updateMetrics($metrics);
        
        $this->assertEquals($metrics, $post->getMetrics());
    }

    public function test_social_mention_creation(): void
    {
        $mention = SocialMention::create(
            platform: 'twitter',
            mentionUrl: 'https://twitter.com/user/status/12345',
            authorName: 'Test User',
            content: '@astraos Great product!',
            sentiment: 'positive',
            reach: 5000
        );
        
        $this->assertInstanceOf(SocialMention::class, $mention);
        $this->assertEquals('twitter', $mention->getPlatform());
        $this->assertEquals('positive', $mention->getSentiment());
        $this->assertEquals(5000, $mention->getReach());
        $this->assertEquals(SocialMention::STATUS_UNREAD, $mention->getStatus());
    }

    public function test_social_mention_mark_as_read(): void
    {
        $mention = SocialMention::create(
            platform: 'linkedin',
            mentionUrl: 'https://linkedin.com/feed/update/12345',
            authorName: 'Professional User',
            content: 'Mention content'
        );
        
        $this->assertEquals(SocialMention::STATUS_UNREAD, $mention->getStatus());
        
        $mention->markAsRead();
        $this->assertEquals(SocialMention::STATUS_READ, $mention->getStatus());
    }

    public function test_social_mention_mark_as_responded(): void
    {
        $mention = SocialMention::create(
            platform: 'facebook',
            mentionUrl: 'https://facebook.com/post/12345',
            authorName: 'Facebook User',
            content: 'Another mention'
        );
        
        $mention->markAsResponded();
        $this->assertEquals(SocialMention::STATUS_RESPONDED, $mention->getStatus());
    }

    public function test_social_mention_mark_as_ignored(): void
    {
        $mention = SocialMention::create(
            platform: 'instagram',
            mentionUrl: 'https://instagram.com/p/12345',
            authorName: 'Instagram User',
            content: 'Spam mention'
        );
        
        $mention->markAsIgnored();
        $this->assertEquals(SocialMention::STATUS_IGNORED, $mention->getStatus());
    }

    public function test_social_mention_ai_suggested_response(): void
    {
        $mention = SocialMention::create(
            platform: 'twitter',
            mentionUrl: 'https://twitter.com/user/status/12345',
            authorName: 'User',
            content: 'Question about pricing'
        );
        
        $response = 'Thanks for asking! Our pricing starts at $29/month.';
        $mention->setAiSuggestedResponse($response);
        
        $this->assertEquals($response, $mention->getAiSuggestedResponse());
    }

    public function test_social_mention_is_unread(): void
    {
        $mention = SocialMention::create(
            platform: 'twitter',
            mentionUrl: 'https://twitter.com/user/status/12345',
            authorName: 'User',
            content: 'Test'
        );
        
        $this->assertTrue($mention->isUnread());
        
        $mention->markAsRead();
        $this->assertFalse($mention->isUnread());
    }

    public function test_social_mention_is_responded(): void
    {
        $mention = SocialMention::create(
            platform: 'twitter',
            mentionUrl: 'https://twitter.com/user/status/12345',
            authorName: 'User',
            content: 'Test'
        );
        
        $this->assertFalse($mention->isResponded());
        
        $mention->markAsResponded();
        $this->assertTrue($mention->isResponded());
    }
}