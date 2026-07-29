<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@astraos.io',
            'password' => bcrypt('password'),
        ]);

        $org1Id = Str::uuid();
        DB::table('organizations')->insert([
            'id' => $org1Id,
            'name' => 'Astra Corp',
            'slug' => 'astra-corp',
            'settings' => json_encode([
                'timezone' => 'UTC',
                'date_format' => 'Y-m-d',
                'currency' => 'USD',
                'language' => 'en',
            ]),
            'is_active' => true,
            'extras' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $org2Id = Str::uuid();
        DB::table('organizations')->insert([
            'id' => $org2Id,
            'name' => 'Nexus Media',
            'slug' => 'nexus-media',
            'settings' => json_encode([
                'timezone' => 'America/New_York',
                'date_format' => 'm/d/Y',
                'currency' => 'USD',
                'language' => 'en',
            ]),
            'is_active' => true,
            'extras' => json_encode(['industry' => 'media', 'tier' => 'enterprise']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('organization_members')->insert([
            [
                'organization_id' => $org1Id,
                'user_id' => $user->id,
                'role' => 'owner',
                'permissions' => json_encode(['*']),
                'invited_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'organization_id' => $org2Id,
                'user_id' => $user->id,
                'role' => 'admin',
                'permissions' => json_encode(['campaigns.*', 'agents.*', 'workflows.*', 'social.*', 'analytics.read']),
                'invited_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
