<?php

declare(strict_types=1);

namespace Tests\Feature\Queue;

use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\ProcessCampaignLaunch;
use App\Jobs\SendWebhookNotification;
use App\Jobs\OptimizeCampaignBudget;

#[Group('feature')]
class QueueManagementTest extends TestCase
{
    public function test_queue_job_dispatch(): void
    {
        Queue::fake();

        $campaignId = 'test-campaign-uuid';
        ProcessCampaignLaunch::dispatch($campaignId);

        Queue::assertPushed(ProcessCampaignLaunch::class, function ($job) use ($campaignId) {
            return $job->campaignId === $campaignId;
        });
    }

    public function test_queue_job_chain(): void
    {
        Queue::fake();

        ProcessCampaignLaunch::withChain([
            new SendWebhookNotification('https://webhook.url'),
            new OptimizeCampaignBudget('test-campaign-uuid'),
        ])->dispatch('test-campaign-uuid');

        Queue::assertPushed(ProcessCampaignLaunch::class);
    }

    public function test_queue_failed_job_handling(): void
    {
        Queue::fake();

        // This would be tested with a job that throws an exception
        // Queue::assertFailed(ProcessCampaignLaunch::class);
    }

    public function test_horizon_supervisor_configuration(): void
    {
        $config = config('horizon');
        
        $this->assertArrayHasKey('environments', $config);
        $this->assertArrayHasKey('production', $config['environments']);
        
        $supervisors = $config['environments']['production']['supervisors'] ?? [];
        $this->assertArrayHasKey('high-priority', $supervisors);
        $this->assertArrayHasKey('default', $supervisors);
        $this->assertArrayHasKey('low-priority', $supervisors);
    }

    public function test_queue_job_retry_backoff(): void
    {
        $job = new ProcessCampaignLaunch('test-uuid');
        
        $this->assertEquals([10, 30, 60, 300, 600], $job->backoff());
    }

    public function test_queue_job_timeout(): void
    {
        $job = new ProcessCampaignLaunch('test-uuid');
        
        $this->assertEquals(120, $job->timeout);
    }

    public function test_queue_monitoring_endpoint(): void
    {
        $response = $this->get('/horizon/api/monitor');
        
        $response->assertStatus(200);
    }
}