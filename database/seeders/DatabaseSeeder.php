<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            CampaignSeeder::class,
            AgentSeeder::class,
            WorkflowSeeder::class,
            SocialSeeder::class,
            AnalyticsSeeder::class,
        ]);
    }
}
