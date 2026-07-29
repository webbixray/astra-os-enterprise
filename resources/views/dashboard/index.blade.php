<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astra OS Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <span class="text-xl font-bold text-indigo-600">✦ Astra OS</span>
                <span class="text-gray-400">|</span>
                <span class="text-gray-600">Dashboard</span>
            </div>
            <div class="text-sm text-gray-500">
                Logged in as <span class="font-medium text-gray-700">{{ $user?->name ?? 'Guest' }}</span>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-500">Overview of your marketing operations</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Active Campaigns</p>
                <p class="text-2xl font-bold text-indigo-600">{{ $stats['active_campaigns'] ?? '—' }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">AI Agents</p>
                <p class="text-2xl font-bold text-purple-600">{{ $stats['active_agents'] ?? '—' }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Workflows</p>
                <p class="text-2xl font-bold text-pink-600">{{ $stats['active_workflows'] ?? '—' }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-sm text-gray-500">Total Impressions</p>
                <p class="text-2xl font-bold text-green-600">{{ isset($stats['total_impressions']) ? number_format($stats['total_impressions']) : '—' }}</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="flex flex-wrap gap-3">
                <a href="#" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700">New Campaign</a>
                <a href="#" class="px-4 py-2 bg-purple-600 text-white rounded-lg text-sm hover:bg-purple-700">Deploy Agent</a>
                <a href="#" class="px-4 py-2 bg-pink-600 text-white rounded-lg text-sm hover:bg-pink-700">Create Workflow</a>
                <a href="#" class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm hover:bg-gray-700">Export Report</a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
            <div class="space-y-3">
                @forelse(($recentActivity ?? []) as $activity)
                <div class="flex items-center justify-between py-2 border-b last:border-0">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $activity['description'] ?? '' }}</p>
                        <p class="text-xs text-gray-500">{{ isset($activity['created_at']) ? \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() : '' }}</p>
                    </div>
                    @if(isset($activity['type']))
                    <span class="px-2 py-1 text-xs font-medium rounded-full
                        @if($activity['type'] === 'campaign') bg-indigo-100 text-indigo-700
                        @elseif($activity['type'] === 'agent') bg-purple-100 text-purple-700
                        @else bg-gray-100 text-gray-700 @endif">
                        {{ ucfirst($activity['type']) }}
                    </span>
                    @endif
                </div>
                @empty
                <p class="text-sm text-gray-500">No recent activity. Start by creating your first campaign!</p>
                @endforelse
            </div>
        </div>
    </main>
</body>
</html>
