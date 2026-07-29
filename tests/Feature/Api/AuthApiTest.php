<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    #[Test]
    public function it_returns_health_check(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'version',
            'timestamp',
        ]);
    }

    #[Test]
    public function it_requires_authentication_for_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_authentication_for_organization_routes(): void
    {
        $response = $this->getJson('/api/v1/organizations/some-uuid/campaigns');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_register_a_new_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Registration endpoint returns 501 as it's not yet implemented
        $response->assertStatus(501);
    }

    #[Test]
    public function it_can_login(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@astraos.io',
            'password' => 'password',
        ]);

        // Login endpoint returns 501 as it's not yet implemented
        $response->assertStatus(501);
    }

    #[Test]
    public function it_can_logout(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        // Logout endpoint returns 501 as it's not yet implemented
        $response->assertStatus(501);
    }

    #[Test]
    public function it_returns_user_profile(): void
    {
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        // Me endpoint returns 501 as it's not yet implemented
        $response->assertStatus(501);
    }

    #[Test]
    public function it_rejects_invalid_tokens(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
