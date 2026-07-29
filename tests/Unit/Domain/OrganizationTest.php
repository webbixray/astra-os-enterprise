<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    #[Test]
    public function it_can_create_an_organization(): void
    {
        $data = [
            'name' => 'Test Organization',
            'slug' => 'test-org',
            'settings' => ['timezone' => 'UTC', 'currency' => 'USD'],
            'is_active' => true,
        ];

        $organization = \App\Models\Organization::create(array_merge($data, [
            'id' => \Illuminate\Support\Str::uuid(),
        ]));

        $this->assertNotNull($organization);
        $this->assertEquals('Test Organization', $organization->name);
        $this->assertEquals('test-org', $organization->slug);
        $this->assertTrue($organization->is_active);
    }

    #[Test]
    public function it_can_have_members(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $user = \App\Models\User::factory()->create();

        $organization->members()->create([
            'user_id' => $user->id,
            'role' => 'admin',
            'permissions' => ['campaigns.*'],
        ]);

        $this->assertCount(1, $organization->members);
        $this->assertEquals('admin', $organization->members->first()->role);
    }

    #[Test]
    public function it_can_be_deactivated(): void
    {
        $organization = \App\Models\Organization::factory()->create(['is_active' => true]);

        $organization->update(['is_active' => false]);

        $this->assertFalse($organization->fresh()->is_active);
    }

    #[Test]
    public function it_enforces_unique_slugs(): void
    {
        \App\Models\Organization::factory()->create(['slug' => 'unique-slug']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        \App\Models\Organization::factory()->create(['slug' => 'unique-slug']);
    }

    #[Test]
    public function it_has_timestamps(): void
    {
        $organization = \App\Models\Organization::factory()->create();

        $this->assertNotNull($organization->created_at);
        $this->assertNotNull($organization->updated_at);
    }

    #[Test]
    public function it_supports_soft_deletes(): void
    {
        $organization = \App\Models\Organization::factory()->create();
        $organization->delete();

        $this->assertSoftDeleted($organization);
        $this->assertNotNull($organization->deleted_at);
    }

    #[Test]
    public function it_can_store_extras(): void
    {
        $extras = ['industry' => 'tech', 'tier' => 'enterprise'];
        $organization = \App\Models\Organization::factory()->create(['extras' => $extras]);

        $this->assertEquals($extras, $organization->extras);
    }
}
