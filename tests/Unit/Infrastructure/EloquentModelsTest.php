<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use Tests\TestCase;
use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\Organization;
use App\Infrastructure\Persistence\Models\Agent;
use App\Infrastructure\Persistence\Models\SocialPost;
use App\Infrastructure\Persistence\Models\SocialMention;
use Ramsey\Uuid\Uuid;

#[Group('unit')]
class EloquentModelsTest extends TestCase
{
    public function test_campaign_model_fillable(): void
    {
        $fillable = (new Campaign())->getFillable();
        
        $expected = [
            'name', 'objective', 'status', 'budget_amount', 'budget_currency',
            'target_audience', 'platforms', 'start_date', 'end_date',
            'organization_id', 'created_at', 'updated_at',
        ];
        
        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    public function test_organization_model_fillable(): void
    {
        $fillable = (new Organization())->getFillable();
        
        $expected = [
            'name', 'slug', 'description', 'logo', 'website', 'settings',
            'owner_id', 'billing_email', 'currency', 'timezone',
        ];
        
        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    public function test_agent_model_fillable(): void
    {
        $fillable = (new Agent())->getFillable();
        
        $expected = [
            'name', 'role', 'autonomy_level', 'model_config',
            'parent_agent_id', 'organization_id', 'configuration',
            'status', 'last_active_at',
        ];
        
        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    public function test_social_post_model_fillable(): void
    {
        $fillable = (new SocialPost())->getFillable();
        
        $expected = [
            'account_id', 'campaign_id', 'content', 'media',
            'scheduled_at', 'published_at', 'status',
            'platform_post_id', 'metrics',
        ];
        
        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    public function test_social_mention_model_fillable(): void
    {
        $fillable = (new SocialMention())->getFillable();
        
        $expected = [
            'platform', 'mention_url', 'author_name', 'content',
            'sentiment', 'reach', 'ai_suggested_response',
            'status', 'organization_id',
        ];
        
        foreach ($expected as $field) {
            $this->assertContains($field, $fillable);
        }
    }

    public function test_campaign_casts(): void
    {
        $casts = (new Campaign())->getCasts();
        
        $this->assertArrayHasKey('target_audience', $casts);
        $this->assertArrayHasKey('platforms', $casts);
        $this->assertEquals('array', $casts['target_audience']);
        $this->assertEquals('array', $casts['platforms']);
    }

    public function test_agent_casts(): void
    {
        $casts = (new Agent())->getCasts();
        
        $this->assertArrayHasKey('model_config', $casts);
        $this->assertArrayHasKey('configuration', $casts);
        $this->assertEquals('array', $casts['model_config']);
        $this->assertEquals('array', $casts['configuration']);
    }

    public function test_social_post_casts(): void
    {
        $casts = (new SocialPost())->getCasts();
        
        $this->assertArrayHasKey('media', $casts);
        $this->assertArrayHasKey('metrics', $casts);
        $this->assertEquals('array', $casts['media']);
        $this->assertEquals('array', $casts['metrics']);
    }

    public function test_model_relationships_defined(): void
    {
        // Campaign relationships
        $campaign = new Campaign();
        $this->assertTrue(method_exists($campaign, 'organization'));
        $this->assertTrue(method_exists($campaign, 'creatives'));
        $this->assertTrue(method_exists($campaign, 'insights'));
        $this->assertTrue(method_exists($campaign, 'analytics'));
        
        // Organization relationships
        $org = new Organization();
        $this->assertTrue(method_exists($org, 'campaigns'));
        $this->assertTrue(method_exists($org, 'members'));
        $this->assertTrue(method_exists($org, 'agents'));
        
        // Agent relationships
        $agent = new Agent();
        $this->assertTrue(method_exists($agent, 'organization'));
        $this->assertTrue(method_exists($agent, 'tasks'));
        $this->assertTrue(method_exists($agent, 'memories'));
    }
}