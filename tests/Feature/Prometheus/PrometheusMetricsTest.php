<?php

declare(strict_types=1);

namespace Tests\Feature\Prometheus;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

#[Group('feature')]
class PrometheusMetricsTest extends TestCase
{
    public function test_metrics_endpoint_returns_200(): void
    {
        $response = $this->get('/metrics');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    }

    public function test_metrics_endpoint_contains_http_metrics(): void
    {
        // Make a request to generate metrics
        $this->get('/api/v1/health');

        $response = $this->get('/metrics');
        $content = $response->getContent();

        $this->assertStringContainsString('astra_os_http_requests_total', $content);
        $this->assertStringContainsString('astra_os_http_request_duration_seconds', $content);
    }

    public function test_metrics_includes_method_and_path_labels(): void
    {
        $this->get('/api/v1/health');

        $response = $this->get('/metrics');
        $content = $response->getContent();

        $this->assertStringContainsString('method="GET"', $content);
        $this->assertStringContainsString('path="/api/v1/health"', $content);
    }

    public function test_metrics_includes_status_code_label(): void
    {
        $this->get('/api/v1/health');

        $response = $this->get('/metrics');
        $content = $response->getContent();

        $this->assertStringContainsString('status="200"', $content);
    }

    public function test_metrics_endpoint_requires_auth_in_production(): void
    {
        config(['prometheus.authorization.enabled' => true]);
        config(['prometheus.authorization.callback' => fn () => false]);

        $response = $this->get('/metrics');

        // Should be forbidden when auth enabled and callback returns false
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_metrics_includes_global_labels(): void
    {
        $this->get('/api/v1/health');

        $response = $this->get('/metrics');
        $content = $response->getContent();

        $this->assertStringContainsString('app="Astra OS"', $content);
        $this->assertStringContainsString('environment="testing"', $content);
    }

    public function test_histogram_buckets_configured(): void
    {
        $this->get('/api/v1/health');

        $response = $this->get('/metrics');
        $content = $response->getContent();

        // Check for histogram buckets
        $this->assertStringContainsString('_bucket{', $content);
        $this->assertStringContainsString('le="', $content);
    }
}