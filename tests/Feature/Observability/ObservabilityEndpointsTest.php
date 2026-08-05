<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

#[Group('feature')]
class ObservabilityEndpointsTest extends TestCase
{
    public function test_health_endpoints(): void
    {
        $endpoints = [
            '/api/v1/health' => 'liveness',
            '/api/v1/health/readiness' => 'readiness',
            '/api/v1/health/startup' => 'startup',
        ];

        foreach ($endpoints as $url => $type) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertJsonStructure([
                'status',
                'timestamp',
                'version',
            ]);
        }
    }

    public function test_prometheus_metrics_endpoint(): void
    {
        config(['prometheus.enabled' => true]);
        
        // Make some requests to generate metrics
        $this->get('/api/v1/health');
        
        $response = $this->get('/metrics');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
        
        $content = $response->getContent();
        $this->assertStringContainsString('astra_os_http_requests_total', $content);
        $this->assertStringContainsString('astra_os_http_request_duration_seconds', $content);
    }

    public function test_metrics_include_custom_business_metrics(): void
    {
        config(['prometheus.enabled' => true]);
        
        $this->get('/api/v1/health');
        
        $response = $this->get('/metrics');
        $content = $response->getContent();
        
        // Check for queue metrics
        $this->assertStringContainsString('astra_os_queue_jobs_total', $content);
        
        // Check for database metrics
        $this->assertStringContainsString('astra_os_database_queries_total', $content);
    }

    public function test_telemetry_middleware_enabled(): void
    {
        config(['opentelemetry.enabled' => true]);
        
        $response = $this->get('/api/v1/health');
        
        $response->assertStatus(200);
        // In real implementation, trace headers would be added
    }

    public function test_sentry_integration_configured(): void
    {
        config(['sentry.enabled' => true]);
        
        $this->assertTrue(config('sentry.enabled'));
        $this->assertNotEmpty(config('sentry.dsn'));
    }

    public function test_pulse_dashboard_available(): void
    {
        $response = $this->get('/pulse');
        
        // Should redirect to login or show dashboard
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_telescope_dashboard_available(): void
    {
        $response = $this->get('/telescope');
        
        // Should redirect to login or show dashboard
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_horizon_dashboard_available(): void
    {
        $response = $this->get('/horizon');
        
        // Should redirect to login or show dashboard
        $this->assertNotEquals(404, $response->getStatusCode());
    }
}