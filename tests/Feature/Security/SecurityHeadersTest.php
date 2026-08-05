<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;

#[Group('feature')]
class SecurityHeadersTest extends TestCase
{
    public function test_security_headers_present(): void
    {
        $response = $this->get('/api/v1/health');
        
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Strict-Transport-Security');
    }

    public function test_csp_header(): void
    {
        $response = $this->get('/api/v1/health');
        
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotEmpty($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function test_permissions_policy(): void
    {
        $response = $this->get('/api/v1/health');
        
        $permissions = $response->headers->get('Permissions-Policy');
        $this->assertNotEmpty($permissions);
        $this->assertStringContainsString('camera=()', $permissions);
        $this->assertStringContainsString('microphone=()', $permissions);
        $this->assertStringContainsString('geolocation=()', $permissions);
    }

    public function test_rate_limiting_headers(): void
    {
        $response = $this->get('/api/v1/health');
        
        $this->assertHeaderPresent('X-RateLimit-Limit');
        $this->assertHeaderPresent('X-RateLimit-Remaining');
        $this->assertHeaderPresent('X-RateLimit-Reset');
    }

    public function test_cors_headers(): void
    {
        $response = $this->call('OPTIONS', '/api/v1/health', [], [], [
            'HTTP_ORIGIN' => 'https://app.astraos.io',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertHeaderPresent('Access-Control-Allow-Origin');
        $this->assertHeaderPresent('Access-Control-Allow-Methods');
        $this->assertHeaderPresent('Access-Control-Allow-Headers');
    }

    public function test_input_sanitization(): void
    {
        $response = $this->postJson('/api/v1/organizations/test-org/campaigns', [
            'name' => '<script>alert("xss")</script>',
            'objective' => 'conversions',
            'budget_amount' => 1000,
            'platforms' => ['meta'],
        ]);
        
        // Should either sanitize or reject
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    public function test_sql_injection_protection(): void
    {
        $response = $this->get('/api/v1/organizations/test-org/campaigns?search=test\' OR 1=1--');
        
        // Should not error with SQL syntax
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    public function test_authentication_required(): void
    {
        $routes = Route::getRoutes();
        $protectedCount = 0;
        $publicCount = 0;
        
        foreach ($routes as $route) {
            if (str_starts_with($route->uri(), 'api/') && !$route->uri('health')) {
                $middleware = $route->gatherMiddleware();
                if (in_array('auth:sanctum', $middleware) || in_array('auth:api', $middleware)) {
                    $protectedCount++;
                } else {
                    $publicCount++;
                }
            }
        }
        
        // Most API routes should be protected
        $this->assertGreaterThan($publicCount, $protectedCount);
    }
}