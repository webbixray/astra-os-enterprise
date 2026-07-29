<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astra OS Enterprise — AI-Native Marketing Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .gradient-bg { background: linear-gradient(135deg, #1e1b4b, #312e81, #3730a3); }
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-gray-50">
    <nav class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <span class="text-2xl font-bold">✦ Astra OS</span>
                <span class="text-indigo-300 text-sm font-medium">Enterprise</span>
                <span class="ml-2 px-2 py-0.5 bg-indigo-500 rounded text-xs font-semibold">v{{ config('astra-os.general.version') }}</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="/api/documentation" class="text-indigo-200 hover:text-white transition">API</a>
                <a href="/pulse" class="text-indigo-200 hover:text-white transition">Monitoring</a>
                <a href="/horizon" class="text-indigo-200 hover:text-white transition">Queue</a>
            </div>
        </div>
    </nav>

    <header class="gradient-bg text-white pb-24">
        <div class="max-w-7xl mx-auto px-4 pt-16 text-center">
            <h1 class="text-5xl font-bold mb-4">AI-Native Marketing Platform</h1>
            <p class="text-xl text-indigo-200 max-w-3xl mx-auto">
                Orchestrate campaigns across every channel with autonomous AI agents.
                Enterprise-grade campaign management, workflow automation, and social intelligence.
            </p>
            <div class="mt-8 flex justify-center space-x-4">
                <a href="/api/documentation" class="px-6 py-3 bg-white text-indigo-900 rounded-lg font-semibold hover:bg-indigo-50 transition">
                    API Documentation
                </a>
                <a href="/pulse" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500 transition">
                    System Health
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 -mt-16 pb-16">
        <div class="grid md:grid-cols-3 gap-6 mb-12">
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                    <span class="text-2xl">🎯</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Campaign Management</h3>
                <p class="text-gray-600 text-sm">Create, launch, and optimize multi-platform advertising campaigns with AI-powered targeting and budget optimization.</p>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                    <span class="text-2xl">🤖</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">AI Agents</h3>
                <p class="text-gray-600 text-sm">Deploy autonomous AI agents for content creation, audience analysis, bid management, and performance reporting.</p>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mb-4">
                    <span class="text-2xl">🔄</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Workflow Automation</h3>
                <p class="text-gray-600 text-sm">Design complex multi-step workflows that trigger campaigns, analyze results, and adapt strategies in real-time.</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-8 mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">API Quick Start</h2>
            <div class="bg-gray-900 rounded-lg p-6 text-sm font-mono">
                <div class="text-green-400 mb-2"># Authenticate</div>
                <div class="text-gray-300 mb-4">curl -X POST {{ config('app.url') }}/api/v1/auth/login \<br>
                    &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
                    &nbsp;&nbsp;-d '{"email":"admin@astra-os.com","password":"your-password"}'</div>
                <div class="text-green-400 mb-2"># List campaigns</div>
                <div class="text-gray-300">curl {{ config('app.url') }}/api/v1/campaigns \<br>
                    &nbsp;&nbsp;-H "Authorization: Bearer {your-token}"</div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">📊 System Status</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">API</span><span class="text-green-600 font-medium">● Operational</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Database</span><span class="text-green-600 font-medium">● Connected</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Queue</span><span class="text-green-600 font-medium">● Processing</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">Cache</span><span class="text-green-600 font-medium">● Available</span></div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-md p-6 card-hover">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">📈 Quick Links</h3>
                <div class="space-y-2 text-sm">
                    <a href="/api/documentation" class="block text-indigo-600 hover:text-indigo-800">→ API Reference</a>
                    <a href="/pulse" class="block text-indigo-600 hover:text-indigo-800">→ Pulse Dashboard</a>
                    <a href="/horizon" class="block text-indigo-600 hover:text-indigo-800">→ Horizon Queue</a>
                    <a href="/telescope" class="block text-indigo-600 hover:text-indigo-800">→ Telescope Debug</a>
                    <a href="#" class="block text-indigo-600 hover:text-indigo-800">→ GitHub Repository</a>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm">
            <p class="mb-2">✦ Astra OS Enterprise — AI-Native Marketing Platform</p>
            <p>Version {{ config('astra-os.general.version') }} · Built with Laravel 13 · Clean Architecture DDD</p>
            <p class="mt-2">© {{ date('Y') }} Astra OS. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
