<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use Illuminate\Support\Str;

/**
 * Parses inbound Telegram bot commands and extracts structured arguments.
 *
 * Supports:
 *   /command
 *   /command arg1 arg2
 *   /command subcommand arg1 --flag=value
 */
final class TelegramCommandParser
{
    /**
     * Parsed command structure.
     *
     * @param string     $command    The base command (without leading slash).
     * @param string     $subcommand Optional subcommand segment.
     * @param array<int, string> $arguments Positional arguments.
     * @param array<string, string> $flags     Named flags (--key=value).
     * @param string     $rawText    The full original message text.
     */
    public function __construct(
        public readonly string $command,
        public readonly string $subcommand = '',
        public readonly array $arguments = [],
        public readonly array $flags = [],
        public readonly string $rawText = '',
    ) {}

    /**
     * Parse a raw Telegram message text into a structured command.
     *
     * @param  string $text The raw message text (e.g. "/campaign show abc-123").
     * @return self|null    Null when the text is not a bot command (no leading slash).
     */
    public static function parse(string $text): ?self
    {
        $text = trim($text);

        if ($text === '' || $text[0] !== '/') {
            return null;
        }

        // Strip bot username suffix: /command@AstraOSBot -> /command
        $text = preg_replace('/@[\w]+/', '', $text);

        $rawText = $text;

        // Split on whitespace
        $parts = preg_split('/\s+/', $text);
        $parts = array_values(array_filter($parts, fn (string $p): bool => $p !== ''));

        if (empty($parts)) {
            return null;
        }

        // First part is the command, strip leading slash
        $command = ltrim((string) array_shift($parts), '/');
        $command = Str::lower($command);

        // Separate flags (--key=value or --flag) from positional arguments
        $arguments = [];
        $flags = [];

        foreach ($parts as $part) {
            if (str_starts_with($part, '--')) {
                $flag = substr($part, 2);
                if (str_contains($flag, '=')) {
                    [$key, $value] = explode('=', $flag, 2);
                    $flags[trim($key)] = trim($value);
                } else {
                    $flags[trim($flag)] = 'true';
                }
            } else {
                $arguments[] = $part;
            }
        }

        // If first argument doesn't look like an ID, treat it as a subcommand
        $subcommand = '';
        if (! empty($arguments)) {
            $first = $arguments[0];
            // Subcommands are short alpha strings, not UUIDs/IDs
            if (preg_match('/^[a-z]{2,20}$/', $first)) {
                $subcommand = $first;
                array_shift($arguments);
                // Re-index
                $arguments = array_values($arguments);
            }
        }

        return new self(
            command: $command,
            subcommand: $subcommand,
            arguments: $arguments,
            flags: $flags,
            rawText: $rawText,
        );
    }

    /**
     * Get a help text listing all available commands.
     */
    public static function getHelpText(): string
    {
        return implode("\n", [
            '*🤖 Astra OS Bot — Available Commands*',
            '',
            '*/help* — Show this help message',
            '*/status* — System status overview',
            '*/campaign list* — List active campaigns',
            '*/campaign show <id>* — Campaign details',
            '*/agent list* — List all agents',
            '*/agent show <id>* — Agent details and tasks',
            '*/analytics overview* — Quick analytics summary',
            '*/analytics campaign <id>* — Analytics for a specific campaign',
            '*/link <email>* — Link your Telegram account to Astra OS',
            '',
            '_Tip: Type any command to get started._',
        ]);
    }

    /**
     * Get a formatted status message for the system.
     */
    public static function getSystemStatusText(): string
    {
        $campaignCount = \App\Infrastructure\Persistence\Models\Campaign::count();
        $activeCampaigns = \App\Infrastructure\Persistence\Models\Campaign::where('status', 'active')->count();
        $agentCount = \App\Infrastructure\Persistence\Models\Agent::count();
        $busyAgents = \App\Infrastructure\Persistence\Models\AgentTask::whereIn('status', ['pending', 'processing'])->count();

        return implode("\n", [
            '*📊 Astra OS System Status*',
            '',
            "*Campaigns:* {$campaignCount} total, {$activeCampaigns} active",
            "*Agents:* {$agentCount} configured",
            "*Pending Tasks:* {$busyAgents}",
            '*System:* ' . (app()->isDownForMaintenance() ? '🔴 Maintenance' : '🟢 Operational'),
            '*Environment:* ' . app()->environment(),
            '*Version:* ' . config('astra-os.general.version', '1.2.0'),
        ]);
    }
}
