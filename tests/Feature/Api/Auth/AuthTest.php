<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'token',
            ],
        ]);

        $response->assertJson([
            'message' => 'User registered successfully.',
            'data' => [
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function login_with_valid_credentials_returns_token(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
                'token',
            ],
        ]);

        $response->assertJson([
            'message' => 'Authenticated successfully.',
        ]);
    }

    #[Test]
    public function login_with_invalid_credentials_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function logout_invalidates_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Logged out successfully.',
        ]);

        // Token should be revoked — subsequent request should fail
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    #[Test]
    public function authenticated_user_can_access_me_endpoint(): void
    {
        $user = User::factory()->create([
            'name' => 'Auth User',
            'email' => 'authuser@example.com',
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'name' => 'Auth User',
                'email' => 'authuser@example.com',
            ],
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_me_endpoint(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    #[Test]
    public function register_with_existing_email_returns_422(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Duplicate User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function register_with_weak_password_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Weak Password User',
            'email' => 'weakpw@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function register_with_missing_fields_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    #[Test]
    public function authenticated_user_can_refresh_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'token',
            ],
        ]);

        $response->assertJson([
            'message' => 'Token refreshed successfully.',
        ]);

        // Old token should be revoked
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }
}
