<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Organization;

use App\Application\Organization\DTOs\OrganizationResponseDTO;
use App\Application\Organization\UseCases\CreateOrganizationUseCase;
use App\Application\Organization\UseCases\InviteMemberUseCase;
use App\Models\User;
use App\Infrastructure\Persistence\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class OrganizationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(function (): bool {
            return true;
        });

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    #[Test]
    public function create_organization(): void
    {
        $now = new \DateTimeImmutable();
        $mockDto = new OrganizationResponseDTO(
            id: 1,
            name: 'Acme Corp',
            slug: 'acme-corp',
            description: 'A test organization',
            logo: null,
            website: 'https://acme.example.com',
            settings: [],
            ownerId: $this->user->id,
            createdAt: $now,
            updatedAt: $now,
        );

        $mockUseCase = Mockery::mock(CreateOrganizationUseCase::class);
        $mockUseCase->shouldReceive('execute')
            ->once()
            ->andReturn($mockDto);
        $this->app->instance(CreateOrganizationUseCase::class, $mockUseCase);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/organizations', [
                'name' => 'Acme Corp',
                'slug' => 'acme-corp',
                'description' => 'A test organization',
                'website' => 'https://acme.example.com',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data',
        ]);
        $response->assertJson([
            'message' => 'Organization created successfully.',
        ]);
    }

    #[Test]
    public function show_organization_details(): void
    {
        $organization = Organization::factory()->create([
            'owner_id' => $this->user->id,
            'name' => 'Visible Org',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/organizations/{$organization->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
            ],
        ]);
        $response->assertJsonPath('data.name', 'Visible Org');
    }

    #[Test]
    public function update_organization(): void
    {
        $organization = Organization::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/v1/organizations/{$organization->id}", [
                'name' => 'Updated Corp Name',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Organization updated successfully.',
        ]);
        $response->assertJsonPath('data.name', 'Updated Corp Name');
    }

    #[Test]
    public function invite_member_to_organization(): void
    {
        $organization = Organization::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $mockUseCase = Mockery::mock(InviteMemberUseCase::class);
        $mockUseCase->shouldReceive('execute')
            ->once();
        $this->app->instance(InviteMemberUseCase::class, $mockUseCase);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/v1/organizations/{$organization->id}/members", [
                'user_id' => $this->otherUser->id,
                'role' => 'member',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Member invited successfully.',
        ]);
    }

    #[Test]
    public function remove_member_from_organization(): void
    {
        $organization = Organization::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $memberUser = User::factory()->create();
        $organization->members()->attach($memberUser->id, ['role' => 'member']);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/v1/organizations/{$organization->id}/members/{$memberUser->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Member removed successfully.',
        ]);

        $this->assertDatabaseMissing('organization_members', [
            'organization_id' => $organization->id,
            'user_id' => $memberUser->id,
        ]);
    }

    #[Test]
    public function list_organizations_for_user(): void
    {
        Organization::factory()->create([
            'owner_id' => $this->user->id,
            'name' => 'My Org A',
        ]);

        Organization::factory()->create()
            ->members()->attach($this->user->id, ['role' => 'member']);

        // Org not belonging to user
        Organization::factory()->create([
            'name' => 'Not My Org',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/organizations');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                ],
            ],
        ]);

        $orgNames = collect($response->json('data'))->pluck('name');
        $this->assertTrue($orgNames->contains('My Org A'));
        $this->assertFalse($orgNames->contains('Not My Org'));
    }

    #[Test]
    public function cannot_access_other_org_data(): void
    {
        $otherOrg = Organization::factory()->create([
            'name' => 'Secret Org',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/v1/organizations/{$otherOrg->id}");

        $response->assertStatus(403);
    }

    #[Test]
    public function validation_errors(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/v1/organizations', [
                'name' => '',
                'slug' => 'INVALID SLUG!!!',
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors',
        ]);
        $response->assertJsonValidationErrors(['name', 'slug']);
    }

    #[Test]
    public function unauthorized_access_returns_401(): void
    {
        $response = $this->getJson('/api/v1/organizations');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }
}
