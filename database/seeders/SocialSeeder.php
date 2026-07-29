<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SocialSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = DB::table('organizations')->where('slug', 'astra-corp')->value('id');
        $campaignId = DB::table('campaigns')->where('status', 'active')->value('id');

        // Create social accounts
        $accountIds = [];
        $accounts = [
            ['platform' => 'meta', 'account_name' => 'Astra Corp Facebook'],
            ['platform' => 'linkedin', 'account_name' => 'Astra Corp LinkedIn'],
            ['platform' => 'tiktok', 'account_name' => '@astracorp'],
        ];

        foreach ($accounts as $acc) {
            $id = Str::uuid();
            $accountIds[$acc['platform']] = $id;
            DB::table('social_accounts')->insert([
                'id' => $id,
                'organization_id' => $orgId,
                'platform' => $acc['platform'],
                'account_id' => "acc_{$acc['platform']}_" . Str::random(8),
                'account_name' => $acc['account_name'],
                'access_token' => encrypt('mock_access_token_' . Str::random(32)),
                'refresh_token' => encrypt('mock_refresh_token_' . Str::random(32)),
                'token_expires_at' => now()->addDays(60),
                'is_active' => true,
                'settings' => json_encode(['auto_publish' => true, 'timezone' => 'UTC']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create social posts
        foreach ($accountIds as $platform => $accountId) {
            for ($i = 1; $i <= 3; $i++) {
                $postId = Str::uuid();
                $status = $i === 1 ? 'published' : ($i === 2 ? 'scheduled' : 'draft');
                DB::table('social_posts')->insert([
                    'id' => $postId,
                    'account_id' => $accountId,
                    'campaign_id' => $campaignId,
                    'content' => "Exciting news! Check out our latest updates on {$platform}! #AstraOS #Innovation",
                    'media' => $i === 1 ? json_encode([['type' => 'image', 'url' => "https://assets.astraos.io/social/{$platform}_post_{$i}.jpg"]]) : null,
                    'scheduled_at' => $status === 'scheduled' ? now()->addDays(7) : null,
                    'published_at' => $status === 'published' ? now()->subDays($i) : null,
                    'status' => $status,
                    'platform_post_id' => $status === 'published' ? "post_{$platform}_" . Str::random(16) : null,
                    'metrics' => $status === 'published' ? json_encode(['likes' => rand(50, 500), 'shares' => rand(10, 100), 'comments' => rand(5, 50)]) : null,
                    'created_at' => now()->subDays($i),
                    'updated_at' => now(),
                ]);

                // Add some comments to published posts
                if ($status === 'published') {
                    for ($j = 1; $j <= 2; $j++) {
                        DB::table('social_comments')->insert([
                            'post_id' => $postId,
                            'platform' => $platform,
                            'author_name' => "User_{$platform}_" . Str::random(6),
                            'author_id' => "uid_" . Str::random(12),
                            'content' => $j === 1 ? 'Great content! Keep it up!' : 'When will this be available?',
                            'sentiment' => $j === 1 ? 'positive' : 'neutral',
                            'is_flagged' => false,
                            'is_replied' => $j === 1,
                            'ai_reply' => $j === 1 ? 'Thank you for your support! Stay tuned for more updates.' : null,
                            'replied_at' => $j === 1 ? now()->subHours(2) : null,
                            'created_at' => now()->subDay(),
                            'updated_at' => now()->subHours(2),
                        ]);
                    }
                }
            }
        }

        // Create social mentions
        DB::table('social_mentions')->insert([
            [
                'id' => Str::uuid(),
                'organization_id' => $orgId,
                'platform' => 'twitter',
                'mention_url' => 'https://twitter.com/someuser/status/123456',
                'author_name' => 'tech_enthusiast',
                'content' => 'Just tried AstraOS - amazing platform! #AstraOS #Productivity',
                'sentiment' => 'positive',
                'reach' => 2500,
                'ai_suggested_response' => "Thank you for the kind words! We're thrilled you're enjoying AstraOS. 🚀",
                'status' => 'new',
                'created_at' => now()->subHours(3),
                'updated_at' => now()->subHours(3),
            ],
            [
                'id' => Str::uuid(),
                'organization_id' => $orgId,
                'platform' => 'reddit',
                'mention_url' => 'https://reddit.com/r/technology/comments/abc123',
                'author_name' => 'dev_guru',
                'content' => 'Does anyone have experience with AstraOS for ad campaign management? Looking for reviews.',
                'sentiment' => 'neutral',
                'reach' => 15000,
                'ai_suggested_response' => 'Great question! AstraOS offers comprehensive campaign management with AI-powered optimization. Would you like a demo?',
                'status' => 'acknowledged',
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subHours(12),
            ],
        ]);
    }
}
