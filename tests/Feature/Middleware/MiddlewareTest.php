<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Models\User;
use App\Infrastructure\Persistence\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $organization;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create([
            'owner_id' => $this->user->id,
        ]);
        $this->organization->members()->create([
            'user_id' => $this->user->id,
            'role' => 'admin',
            'permissions' => ['*'],
        ]);

        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    #[Test]
    public function api_requests_return_json(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type');
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type') ?? '');
    }

    #[Test]
    public function unauthorized_api_requests_return_json_error(): void
    {
        $response = $this->getJson("/api/v1/organizations/{$this->organization->id}/campaigns");

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }
}
