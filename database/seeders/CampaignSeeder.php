<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = DB::table('organizations')->where('slug', 'astra-corp')->value('id');

        $campaigns = [
            [
                'id' => Str::uuid(),
                'name' => 'Summer Sale 2026',
                'objective' => 'conversions',
                'status' => 'active',
                'budget_amount' => 50000.00,
                'budget_currency' => 'USD',
                'target_audience' => json_encode(['age' => ['18-35'], 'gender' => 'all', 'locations' => ['US', 'CA']]),
                'platforms' => json_encode(['meta', 'google']),
                'start_date' => '2026-06-01',
                'end_date' => '2026-08-31',
                'metadata' => json_encode(['season' => 'summer', 'discount' => '25%']),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Brand Awareness Q3',
                'objective' => 'awareness',
                'status' => 'active',
                'budget_amount' => 75000.00,
                'budget_currency' => 'USD',
                'target_audience' => json_encode(['age' => ['25-45'], 'gender' => 'all', 'locations' => ['US', 'UK', 'AU']]),
                'platforms' => json_encode(['meta', 'google', 'linkedin', 'tiktok']),
                'start_date' => '2026-07-01',
                'end_date' => '2026-09-30',
                'metadata' => json_encode(['brand' => 'astra', 'campaign_type' => 'branding']),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Product Launch - AstraOS v2',
                'objective' => 'traffic',
                'status' => 'scheduled',
                'budget_amount' => 100000.00,
                'budget_currency' => 'USD',
                'target_audience' => json_encode(['age' => ['22-50'], 'gender' => 'all', 'locations' => ['US', 'CA', 'UK', 'DE']]),
                'platforms' => json_encode(['google', 'linkedin', 'meta']),
                'start_date' => '2026-10-01',
                'end_date' => '2026-12-31',
                'metadata' => json_encode(['product' => 'AstraOS v2', 'launch_type' => 'major']),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Retargeting - Abandoned Cart',
                'objective' => 'retargeting',
                'status' => 'draft',
                'budget_amount' => 15000.00,
                'budget_currency' => 'USD',
                'target_audience' => json_encode(['type' => 'retargeting', 'days_since_visit' => 30]),
                'platforms' => json_encode(['meta', 'google']),
                'start_date' => null,
                'end_date' => null,
                'metadata' => json_encode(['strategy' => 'dynamic_retargeting']),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Holiday Campaign 2026',
                'objective' => 'conversions',
                'status' => 'paused',
                'budget_amount' => 200000.00,
                'budget_currency' => 'USD',
                'target_audience' => json_encode(['age' => ['18-65'], 'gender' => 'all', 'locations' => ['US', 'CA', 'UK', 'AU', 'DE', 'FR']]),
                'platforms' => json_encode(['meta', 'google', 'tiktok']),
                'start_date' => '2026-11-15',
                'end_date' => '2027-01-05',
                'metadata' => json_encode(['season' => 'holiday', 'budget_peak' => 'december']),
            ],
        ];

        foreach ($campaigns as $campaign) {
            $campaign['organization_id'] = $orgId;
            $campaign['created_at'] = now();
            $campaign['updated_at'] = now();
            DB::table('campaigns')->insert($campaign);

            // Create creatives for each campaign
            for ($i = 1; $i <= 2; $i++) {
                DB::table('campaign_creatives')->insert([
                    'id' => Str::uuid(),
                    'campaign_id' => $campaign['id'],
                    'type' => $i === 1 ? 'image' : 'video',
                    'content' => json_encode([
                        'headline' => "{$campaign['name']} - Variant {$i}",
                        'body' => "Check out our amazing offers!",
                        'cta' => $i === 1 ? 'Shop Now' : 'Learn More',
                        'asset_url' => "https://assets.astraos.io/campaigns/{$campaign['id']}/variant_{$i}.jpg",
                    ]),
                    'variant' => "variant_{$i}",
                    'status' => $campaign['status'] === 'active' ? 'approved' : 'draft',
                    'version' => 1,
                    'approved_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Create insights for active campaigns
            if (in_array($campaign['status'], ['active', 'paused'])) {
                $metrics = ['impressions', 'clicks', 'ctr', 'cpc', 'spend', 'conversions'];
                foreach ($metrics as $metric) {
                    DB::table('campaign_insights')->insert([
                        'campaign_id' => $campaign['id'],
                        'date' => now()->subDays(rand(1, 7))->toDateString(),
                        'metric' => $metric,
                        'value' => match ($metric) {
                            'impressions' => rand(5000, 50000),
                            'clicks' => rand(100, 2500),
                            'ctr' => round(rand(150, 500) / 100, 2),
                            'cpc' => round(rand(50, 500) / 100, 2),
                            'spend' => rand(100, 5000),
                            'conversions' => rand(5, 100),
                            default => 0,
                        },
                        'source' => 'aggregated',
                        'metadata' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
