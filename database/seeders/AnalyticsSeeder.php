<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $activeCampaignIds = DB::table('campaigns')
            ->whereIn('status', ['active', 'paused'])
            ->pluck('id');

        foreach ($activeCampaignIds as $campaignId) {
            $startDate = now()->subDays(30);

            for ($day = 0; $day < 30; $day++) {
                $date = $startDate->copy()->addDays($day)->toDateString();
                $impressions = rand(1000, 15000);
                $clicks = intval($impressions * rand(10, 50) / 1000);
                $conversions = intval($clicks * rand(20, 100) / 1000);
                $spend = round(rand(1000, 10000) / 100, 2);

                DB::table('campaign_analytics')->insert([
                    'campaign_id' => $campaignId,
                    'date' => $date,
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'spend' => $spend,
                    'revenue' => round($spend * rand(100, 500) / 100, 2),
                    'roas' => round(rand(100, 500) / 100, 2),
                    'cpc' => $clicks > 0 ? round($spend / $clicks, 4) : 0,
                    'cpm' => $impressions > 0 ? round(($spend / $impressions) * 1000, 4) : 0,
                    'ctr' => $impressions > 0 ? round(($clicks / $impressions) * 100, 4) : 0,
                    'source' => 'aggregated',
                    'metadata' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
