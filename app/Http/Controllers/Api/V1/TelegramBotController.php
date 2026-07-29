<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Models\Agent;
use App\Infrastructure\Persistence\Models\AgentTask;
use App\Infrastructure\Persistence\Models\Campaign;
use App\Infrastructure\Persistence\Models\CampaignAnalytics;
use App\Models\User;
use App\Services\Telegram\TelegramCommandParser;
use App\Services\Telegram\TelegramService;
use Illuminate\Support\Facades\Log;

/**
 * Handles Telegram bot commands for Astra OS Enterprise.
 *
 * All public methods are invoked from TelegramService's command router.
 * Each method receives the chat ID and the parsed command structure.
 */
final class TelegramBotController extends Controller
{
    private const MAX_LIST_ITEMS = 10;

    public function __construct(
        private readonly TelegramService $telegram,
    ) {}

    /**
     * Handle the /campaign command family.
     */
    public function handleCampaignCommand(int $chatId, int $userId, TelegramCommandParser $parsed): void
    {
        $user = User::where('telegram_user_id', (string) $userId)->first();

        if (! $user) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Your Telegram account is not linked.\nUse /link your@email.com to get started.",
            );
            return;
        }

        match ($parsed->subcommand) {
            'list' => $this->listCampaigns($chatId, $user),
            'show' => $this->showCampaign($chatId, $user, $parsed->arguments[0] ?? ''),
            default => $this->telegram->sendMessage(
                $chatId,
                "*📋 Campaign Commands*\n\n"
                . "• `/campaign list` — List active campaigns\n"
                . "• `/campaign show <id>` — Show campaign details\n\n"
                . "_Example:_ `/campaign show abc-123`",
                ['parse_mode' => 'Markdown'],
            ),
        };
    }

    /**
     * Handle the /agent command family.
     */
    public function handleAgentCommand(int $chatId, int $userId, TelegramCommandParser $parsed): void
    {
        $user = User::where('telegram_user_id', (string) $userId)->first();

        if (! $user) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Your Telegram account is not linked.\nUse /link your@email.com to get started.",
            );
            return;
        }

        match ($parsed->subcommand) {
            'list' => $this->listAgents($chatId, $user),
            'show' => $this->showAgent($chatId, $user, $parsed->arguments[0] ?? ''),
            default => $this->telegram->sendMessage(
                $chatId,
                "*🤖 Agent Commands*\n\n"
                . "• `/agent list` — List all agents\n"
                . "• `/agent show <id>` — Show agent details and tasks\n\n"
                . "_Example:_ `/agent show agent-uuid-here`",
                ['parse_mode' => 'Markdown'],
            ),
        };
    }

    /**
     * Handle the /analytics command family.
     */
    public function handleAnalyticsCommand(int $chatId, int $userId, TelegramCommandParser $parsed): void
    {
        $user = User::where('telegram_user_id', (string) $userId)->first();

        if (! $user) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Your Telegram account is not linked.\nUse /link your@email.com to get started.",
            );
            return;
        }

        match ($parsed->subcommand) {
            'overview' => $this->analyticsOverview($chatId, $user),
            'campaign' => $this->showCampaignAnalytics($chatId, $user, $parsed->arguments[0] ?? ''),
            default => $this->analyticsOverview($chatId, $user),
        };
    }

    /**
     * Handle the /link command — initiates Telegram-to-AstraOS linking.
     */
    public function handleLinkCommand(int $chatId, int $userId, TelegramCommandParser $parsed): void
    {
        $email = $parsed->arguments[0] ?? '';

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Please provide a valid email address.\n\nExample: `/link your@email.com`",
                ['parse_mode' => 'Markdown'],
            );
            return;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ No Astra OS account found with email `{$email}`.\n\nPlease register first at the Astra OS web app.",
                ['parse_mode' => 'Markdown'],
            );
            return;
        }

        // If already linked to this user, confirm
        if ((string) $user->telegram_user_id === (string) $userId) {
            $this->telegram->sendMessage(
                $chatId,
                "*✅ Already Linked*\n\nYour Telegram account is already linked to `{$email}`.",
                ['parse_mode' => 'Markdown'],
            );
            return;
        }

        // If linked to a different user, warn
        if ($user->telegram_user_id !== null) {
            $this->telegram->sendMessage(
                $chatId,
                "⚠️ This email is already linked to another Telegram account.\n"
                . "Use /link with a different account or contact support.",
            );
            return;
        }

        // Generate verification code
        $code = (string) random_int(100000, 999999);

        \Illuminate\Support\Facades\Cache::put(
            "telegram:link:{$user->id}",
            [
                'code' => $code,
                'telegram_chat_id' => $chatId,
                'telegram_user_id' => $userId,
            ],
            600,
        );

        $this->telegram->sendMessage(
            $chatId,
            "*🔗 Link Your Account*\n\n"
            . "We found your account `{$email}`.\n\n"
            . "Your verification code: `{$code}`\n\n"
            . "Enter this code in the Astra OS web app under Settings → Telegram to complete linking.\n\n"
            . "_Code expires in 10 minutes._",
            ['parse_mode' => 'Markdown'],
        );

        Log::info('Telegram: link command initiated', [
            'user_id' => $user->id,
            'email' => $email,
            'telegram_user_id' => $userId,
        ]);
    }

    /**
     * Handle inline keyboard callback for campaigns.
     */
    public function handleCampaignCallback(int $chatId, int $userId, string $callbackId, string $action, string $entityId): void
    {
        $user = User::where('telegram_user_id', (string) $userId)->first();

        if (! $user) {
            $this->telegram->answerCallbackQuery($callbackId, 'Account not linked', true);
            return;
        }

        if ($action === 'show' && $entityId !== '') {
            $this->telegram->answerCallbackQuery($callbackId, 'Loading campaign...');
            $this->showCampaign($chatId, $user, $entityId);
        }
    }

    /**
     * Handle inline keyboard callback for agents.
     */
    public function handleAgentCallback(int $chatId, int $userId, string $callbackId, string $action, string $entityId): void
    {
        $user = User::where('telegram_user_id', (string) $userId)->first();

        if (! $user) {
            $this->telegram->answerCallbackQuery($callbackId, 'Account not linked', true);
            return;
        }

        if ($action === 'show' && $entityId !== '') {
            $this->telegram->answerCallbackQuery($callbackId, 'Loading agent...');
            $this->showAgent($chatId, $user, $entityId);
        }
    }

    /**
     * List campaigns for the user's organization.
     */
    private function listCampaigns(int $chatId, User $user): void
    {
        $organizationId = $user->organization_id;

        if (! $organizationId) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ You are not assigned to any organization.",
            );
            return;
        }

        $campaigns = Campaign::where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->limit(self::MAX_LIST_ITEMS)
            ->get();

        if ($campaigns->isEmpty()) {
            $this->telegram->sendMessage(
                $chatId,
                "📋 No campaigns found for your organization.",
            );
            return;
        }

        $lines = ["*📋 Campaigns ({$campaigns->count()})*", ''];

        foreach ($campaigns as $campaign) {
            $statusEmoji = match ($campaign->status) {
                'active' => '🟢',
                'paused' => '🟡',
                'draft' => '⚪',
                'archived' => '🔴',
                default => '❓',
            };

            $budget = number_format((float) $campaign->budget_amount, 2);
            $lines[] = "{$statusEmoji} *{$this->escapeMarkdown($campaign->name)}*";
            $lines[] = "   ID: `{$campaign->id}`";
            $lines[] = "   Status: {$campaign->status} | Budget: \${$budget}";
            $lines[] = '';
        }

        $lines[] = 'Use `/campaign show <id>` for details.';

        $this->telegram->sendMessage(
            $chatId,
            implode("\n", $lines),
            ['parse_mode' => 'Markdown'],
        );
    }

    /**
     * Show a single campaign with details.
     */
    private function showCampaign(int $chatId, User $user, string $campaignId): void
    {
        $organizationId = $user->organization_id;

        if (! $organizationId) {
            $this->telegram->sendMessage($chatId, "❌ You are not assigned to any organization.");
            return;
        }

        if ($campaignId === '') {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Please provide a campaign ID.\nExample: `/campaign show abc-123`",
                ['parse_mode' => 'Markdown'],
            );
            return;
        }

        $campaign = Campaign::where('organization_id', $organizationId)
            ->where('id', $campaignId)
            ->first();

        if (! $campaign) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Campaign not found: `{$campaignId}`",
                ['parse_mode' => 'Markdown'],
            );
            return;
        }

        $statusEmoji = match ($campaign->status) {
            'active' => '🟢',
            'paused' => '🟡',
            'draft' => '⚪',
            'archived' => '🔴',
            default => '❓',
        };

        $budget = number_format((float) $campaign->budget_amount, 2);
        $platforms = is_array($campaign->platforms)
            ? implode(', ', $campaign->platforms)
            : 'N/A';

        // Fetch basic analytics totals
        $analyticsSum = CampaignAnalytics::where('campaign_id', $campaignId)
            ->selectRaw('COALESCE(SUM(impressions), 0) as total_impressions')
            ->selectRaw('COALESCE(SUM(clicks), 0) as total_clicks')
            ->selectRaw('COALESCE(SUM(conversions), 0) as total_conversions')
            ->selectRaw('COALESCE(SUM(spend), 0) as total_spend')
            ->selectRaw('COALESCE(SUM(revenue), 0) as total_revenue')
            ->first();

        $ctr = $analyticsSum && $analyticsSum->total_impressions > 0
            ? round(($analyticsSum->total_clicks / $analyticsSum->total_impressions) * 100, 2)
            : 0;
        $cvr = $analyticsSum && $analyticsSum->total_clicks > 0
            ? round(($analyticsSum->total_conversions / $analyticsSum->total_clicks) * 100, 2)
            : 0;
        $roas = $analyticsSum && $analyticsSum->total_spend > 0
            ? round($analyticsSum->total_revenue / $analyticsSum->total_spend, 2)
            : 0;

        $lines = [
            "{$statusEmoji} *{$this->escapeMarkdown($campaign->name)}*",
            '',
            "*ID:* `{$campaign->id}`",
            "*Status:* {$campaign->status}",
            "*Budget:* \${$budget} {$campaign->budget_currency}",
            "*Platforms:* {$platforms}",
            "*Objective:* {$campaign->objective}",
            "*Period:* {$campaign->start_date?->format('M d, Y')} → {$campaign->end_date?->format('M d, Y')}",
            '',
            '*📊 Analytics (All Time)*',
            "   Impressions: " . number_format((int) ($analyticsSum->total_impressions ?? 0)),
            "   Clicks: " . number_format((int) ($analyticsSum->total_clicks ?? 0)),
            "   Conversions: " . number_format((int) ($analyticsSum->total_conversions ?? 0)),
            "   Spend: \$" . number_format((float) ($analyticsSum->total_spend ?? 0), 2),
            "   Revenue: \$" . number_format((float) ($analyticsSum->total_revenue ?? 0), 2),
            "   CTR: {$ctr}% | CVR: {$cvr}% | ROAS: {$roas}x",
        ];

        $this->telegram->sendMessage(
            $chatId,
            implode("\n", $lines),
            ['parse_mode' => 'Markdown'],
        );
    }

    /**
     * List all agents for the user's organization.
     */
    private function listAgents(int $chatId, User $user): void
    {
        $organizationId = $user->organization_id;

        if (! $organizationId) {
            $this->telegram->sendMessage($chatId, "❌ You are not assigned to any organization.");
            return;
        }

        $agents = Agent::where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->limit(self::MAX_LIST_ITEMS)
            ->get();

        if ($agents->isEmpty()) {
            $this->telegram->sendMessage(
                $chatId,
                "🤖 No agents configured for your organization.",
            );
            return;
        }

        $lines = ["*🤖 AI Agents ({$agents->count()})*", ''];

        foreach ($agents as $agent) {
            $statusEmoji = match ($agent->status) {
                'idle' => '🟢',
                'busy' => '🟡',
                'error' => '🔴',
                default => '⚪',
            };

            $taskCount = $agent->tasks()->count();
            $pendingTasks = $agent->tasks()->whereIn('status', ['pending', 'processing'])->count();

            $lines[] = "{$statusEmoji} *{$this->escapeMarkdown($agent->name)}*";
            $lines[] = "   Role: {$agent->role} | Model: {$agent->model}";
            $lines[] = "   Status: {$agent->status} | Tasks: {$taskCount} total, {$pendingTasks} pending";
            $lines[] = '';
        }

        $lines[] = 'Use `/agent show <id>` for details.';

        $this->telegram->sendMessage(
            $chatId,
            implode("\n", $lines),
            ['parse_mode' => 'Markdown'],
        );
    }

    /**
     * Show a single agent with its tasks.
     */
    private function showAgent(int $chatId, User $user, string $agentId): void
    {
        $organizationId = $user->organization_id;

        if (! $organizationId) {
            $this->telegram->sendMessage($chatId, "❌ You are not assigned to any organization.");
            return;
        }

        if ($agentId === '') {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Please provide an agent ID.\nExample: `/agent show agent-uuid-here`",
                ['parse_mode' => 'Markdown'],
            );
            return;
        }

        $agent = Agent::where('organization_id', $organizationId)
            ->where('id', $agentId)
            ->first();

        if (! $agent) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Agent not found: `{$agentId}`",
                ['parse_mode' => 'Markdown'],
            );
            return;
        }

        $statusEmoji = match ($agent->status) {
            'idle' => '🟢',
            'busy' => '🟡',
            'error' => '🔴',
            default => '⚪',
        };

        $capabilities = is_array($agent->capabilities)
            ? implode(', ', $agent->capabilities)
            : 'N/A';
        $isActive = $agent->is_active ? '✅ Active' : '⛔ Inactive';

        $lines = [
            "{$statusEmoji} *{$this->escapeMarkdown($agent->name)}*",
            '',
            "*ID:* `{$agent->id}`",
            "*Role:* {$agent->role}",
            "*Model:* {$agent->model}",
            "*Status:* {$agent->status} ({$isActive})",
            "*Capabilities:* {$capabilities}",
            "*Autonomy:* {$agent->autonomy_level}",
        ];

        // Show recent tasks
        $recentTasks = $agent->tasks()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($recentTasks->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '*Recent Tasks:*';

            foreach ($recentTasks as $task) {
                $taskEmoji = match ($task->status) {
                    'completed' => '✅',
                    'processing' => '🔄',
                    'pending' => '⏳',
                    'failed' => '❌',
                    'cancelled' => '🚫',
                    default => '❓',
                };
                $lines[] = "{$taskEmoji} `{$task->id}` — {$task->type} ({$task->status})";
            }
        }

        $this->telegram->sendMessage(
            $chatId,
            implode("\n", $lines),
            ['parse_mode' => 'Markdown'],
        );
    }

    /**
     * Provide a quick analytics overview.
     */
    private function analyticsOverview(int $chatId, User $user): void
    {
        $organizationId = $user->organization_id;

        if (! $organizationId) {
            $this->telegram->sendMessage($chatId, "❌ You are not assigned to any organization.");
            return;
        }

        // Global analytics across all campaigns
        $totalAnalytics = CampaignAnalytics::whereHas('campaign', function ($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        })->selectRaw('
            COALESCE(SUM(impressions), 0) as total_impressions,
            COALESCE(SUM(clicks), 0) as total_clicks,
            COALESCE(SUM(conversions), 0) as total_conversions,
            COALESCE(SUM(spend), 0) as total_spend,
            COALESCE(SUM(revenue), 0) as total_revenue
        ')->first();

        $activeCampaigns = Campaign::where('organization_id', $organizationId)
            ->where('status', 'active')
            ->count();

        $totalCampaigns = Campaign::where('organization_id', $organizationId)
            ->count();

        $totalAgents = Agent::where('organization_id', $organizationId)->count();
        $busyAgents = AgentTask::whereIn('status', ['pending', 'processing'])
            ->whereHas('agent', fn ($q) => $q->where('organization_id', $organizationId))
            ->count();

        $impressions = (int) ($totalAnalytics->total_impressions ?? 0);
        $clicks = (int) ($totalAnalytics->total_clicks ?? 0);
        $conversions = (int) ($totalAnalytics->total_conversions ?? 0);
        $spend = (float) ($totalAnalytics->total_spend ?? 0);
        $revenue = (float) ($totalAnalytics->total_revenue ?? 0);
        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
        $cvr = $clicks > 0 ? round(($conversions / $clicks) * 100, 2) : 0;
        $roas = $spend > 0 ? round($revenue / $spend, 2) : 0;

        $lines = [
            '*📊 Astra OS Analytics Overview*',
            '',
            "*Campaigns:* {$activeCampaigns} active / {$totalCampaigns} total",
            "*Agents:* {$totalAgents} configured, {$busyAgents} busy",
            '',
            '*Performance (All Time)*',
            "   👁 Impressions: " . number_format($impressions),
            "   👆 Clicks: " . number_format($clicks),
            "   ✅ Conversions: " . number_format($conversions),
            "   💰 Spend: \$" . number_format($spend, 2),
            "   📈 Revenue: \$" . number_format($revenue, 2),
            "   📊 CTR: {$ctr}% | CVR: {$cvr}% | ROAS: {$roas}x",
            '',
            '_Use `/analytics campaign <id>` for campaign-specific data._',
        ];

        $this->telegram->sendMessage(
            $chatId,
            implode("\n", $lines),
            ['parse_mode' => 'Markdown'],
        );
    }

    /**
     * Show analytics for a specific campaign.
     */
    private function showCampaignAnalytics(int $chatId, User $user, string $campaignId): void
    {
        $organizationId = $user->organization_id;

        if (! $organizationId) {
            $this->telegram->sendMessage($chatId, "❌ You are not assigned to any organization.");
            return;
        }

        if ($campaignId === '') {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Please provide a campaign ID.\nExample: `/analytics campaign abc-123`",
                ['parse_mode' => 'Markdown'],
            );
            return;
        }

        $campaign = Campaign::where('organization_id', $organizationId)
            ->where('id', $campaignId)
            ->first();

        if (! $campaign) {
            $this->telegram->sendMessage(
                $chatId,
                "❌ Campaign not found: `{$campaignId}`",
                ['parse_mode' => 'Markdown'],
            );
            return;
        }

        // Analytics by platform
        $platformAnalytics = CampaignAnalytics::where('campaign_id', $campaignId)
            ->selectRaw('
                platform,
                COALESCE(SUM(impressions), 0) as impressions,
                COALESCE(SUM(clicks), 0) as clicks,
                COALESCE(SUM(conversions), 0) as conversions,
                COALESCE(SUM(spend), 0) as spend,
                COALESCE(SUM(revenue), 0) as revenue
            ')
            ->groupBy('platform')
            ->get();

        $lines = [
            "*📊 Analytics: {$this->escapeMarkdown($campaign->name)}*",
            '',
        ];

        foreach ($platformAnalytics as $pa) {
            $pCtr = $pa->impressions > 0 ? round(($pa->clicks / $pa->impressions) * 100, 2) : 0;
            $pCvr = $pa->clicks > 0 ? round(($pa->conversions / $pa->clicks) * 100, 2) : 0;
            $pRoas = $pa->spend > 0 ? round($pa->revenue / $pa->spend, 2) : 0;

            $lines[] = "*{$pa->platform}*";
            $lines[] = "   👁 " . number_format((int) $pa->impressions) . " 👆 " . number_format((int) $pa->clicks) . " ✅ " . number_format((int) $pa->conversions);
            $lines[] = "   💰 \$" . number_format((float) $pa->spend, 2) . " → \$" . number_format((float) $pa->revenue, 2);
            $lines[] = "   CTR: {$pCtr}% | CVR: {$pCvr}% | ROAS: {$pRoas}x";
            $lines[] = '';
        }

        if ($platformAnalytics->isEmpty()) {
            $lines[] = 'No analytics data available yet.';
        }

        $lines[] = '_Data refreshes from platform syncs._';

        $this->telegram->sendMessage(
            $chatId,
            implode("\n", $lines),
            ['parse_mode' => 'Markdown'],
        );
    }

    /**
     * Escape special Markdown characters for Telegram.
     */
    private function escapeMarkdown(string $text): string
    {
        // Telegram Markdown escapes: _ * [ ] ( ) ~ ` > # + - = | { } . !
        $special = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        $escaped = [];

        foreach (mb_str_split($text) as $char) {
            if (in_array($char, $special, true)) {
                $escaped[] = '\\' . $char;
            } else {
                $escaped[] = $char;
            }
        }

        return implode('', $escaped);
    }
}
