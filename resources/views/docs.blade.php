@extends('layouts.app')

@section('title', 'API Documentation')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="mb-12">
        <h1 class="text-2xl font-mono font-bold text-white">API Reference</h1>
        <p class="text-gray-500 font-mono text-sm mt-1">v1 — REST API for Polymarket oracle &amp; CLOB data</p>
    </div>

    {{-- Base URL --}}
    <section class="mb-10">
        <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-4">Base URL</h2>
        <div class="bg-gray-900/50 border border-gray-800 rounded p-4">
            <code class="text-white font-mono text-sm">{{ config('app.url') }}/api/v1</code>
        </div>
    </section>

    {{-- Authentication --}}
    <section class="mb-10">
        <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-4">Authentication</h2>
        <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-5 space-y-3">
            <p class="text-gray-400 font-mono text-sm">
                All requests require a Bearer token in the Authorization header. Get your key from the
                <a href="{{ route('dashboard') }}" class="text-[#22c55e] hover:text-green-400">dashboard</a>.
            </p>
            <div class="bg-[#0f0f0f] border border-gray-800 rounded p-3">
                <code class="text-xs font-mono text-gray-300">Authorization: Bearer sk_live_••••••••••••••••</code>
            </div>
            <p class="text-gray-500 font-mono text-xs">
                Rate limits are enforced per API key. Exceeding limits returns HTTP 429.
            </p>
        </div>
    </section>

    {{-- Endpoints --}}
    @php
    $endpoints = [
        [
            'group'       => 'Windows',
            'description' => 'Pre-computed feature windows for resolved binary markets.',
            'items'       => [
                [
                    'method' => 'GET',
                    'path'   => '/windows',
                    'desc'   => 'List all feature windows with pagination.',
                    'params' => [
                        'market_id (optional)' => 'Filter by Polymarket condition ID',
                        'from (optional)'      => 'ISO 8601 start datetime',
                        'to (optional)'        => 'ISO 8601 end datetime',
                        'page (optional)'      => 'Page number (default: 1)',
                        'per_page (optional)'  => 'Results per page (default: 50, max: 200)',
                    ],
                    'curl'   => "curl {{ config('app.url') }}/api/v1/windows \\\n  -H \"Authorization: Bearer YOUR_KEY\"",
                    'example' => "{\n  \"data\": [\n    {\n      \"id\": 1,\n      \"market_id\": \"0xabc...\",\n      \"window_start\": \"2024-01-15T00:00:00Z\",\n      \"window_end\": \"2024-01-15T06:00:00Z\",\n      \"yes_twap_1h\": 0.6823,\n      \"oracle_vol_4h\": 0.0041,\n      \"spread_mean\": 0.018\n    }\n  ],\n  \"meta\": { \"total\": 1240, \"page\": 1, \"per_page\": 50 }\n}",
                ],
            ],
        ],
        [
            'group'       => 'Oracle Ticks',
            'description' => 'Raw Chainlink oracle price recordings (~3s cadence).',
            'items'       => [
                [
                    'method' => 'GET',
                    'path'   => '/oracle-ticks',
                    'desc'   => 'List oracle tick recordings with optional filters.',
                    'params' => [
                        'asset (optional)'    => 'Asset symbol (e.g. ETH, BTC)',
                        'from (optional)'     => 'ISO 8601 start datetime',
                        'to (optional)'       => 'ISO 8601 end datetime',
                        'page (optional)'     => 'Page number',
                    ],
                    'curl'   => "curl \"{{ config('app.url') }}/api/v1/oracle-ticks?asset=ETH\" \\\n  -H \"Authorization: Bearer YOUR_KEY\"",
                    'example' => "{\n  \"data\": [\n    {\n      \"ts\": \"2024-01-15T00:00:03Z\",\n      \"asset\": \"ETH\",\n      \"price\": \"2534.12000000\",\n      \"round_id\": 18446744073709617000\n    }\n  ]\n}",
                ],
                [
                    'method' => 'GET',
                    'path'   => '/oracle-ticks/latest',
                    'desc'   => 'Latest oracle tick for each tracked asset.',
                    'params' => [],
                    'curl'   => "curl {{ config('app.url') }}/api/v1/oracle-ticks/latest \\\n  -H \"Authorization: Bearer YOUR_KEY\"",
                    'example' => "{\n  \"data\": [\n    { \"asset\": \"ETH\", \"price\": \"2534.12\", \"ts\": \"2024-01-15T...\" },\n    { \"asset\": \"BTC\", \"price\": \"43210.00\", \"ts\": \"2024-01-15T...\" }\n  ]\n}",
                ],
            ],
        ],
        [
            'group'       => 'CLOB Snapshots',
            'description' => 'Yes/No bid-ask snapshots synced to oracle ticks.',
            'items'       => [
                [
                    'method' => 'GET',
                    'path'   => '/clob-snapshots',
                    'desc'   => 'List CLOB snapshots for a market.',
                    'params' => [
                        'market_id (required)' => 'Polymarket condition ID',
                        'from (optional)'      => 'ISO 8601 start datetime',
                        'to (optional)'        => 'ISO 8601 end datetime',
                    ],
                    'curl'   => "curl \"{{ config('app.url') }}/api/v1/clob-snapshots?market_id=0xabc\" \\\n  -H \"Authorization: Bearer YOUR_KEY\"",
                    'example' => "{\n  \"data\": [\n    {\n      \"ts\": \"2024-01-15T00:00:03Z\",\n      \"market_id\": \"0xabc...\",\n      \"yes_bid\": 0.67,\n      \"yes_ask\": 0.69,\n      \"no_bid\": 0.31,\n      \"no_ask\": 0.33,\n      \"spread\": 0.02\n    }\n  ]\n}",
                ],
            ],
        ],
        [
            'group'       => 'Window Features',
            'description' => 'Granular computed features per market window.',
            'items'       => [
                [
                    'method' => 'GET',
                    'path'   => '/window-features/{windowId}',
                    'desc'   => 'Get all computed features for a specific window.',
                    'params' => [
                        'windowId (path)' => 'Window record ID',
                    ],
                    'curl'   => "curl {{ config('app.url') }}/api/v1/window-features/42 \\\n  -H \"Authorization: Bearer YOUR_KEY\"",
                    'example' => "{\n  \"data\": {\n    \"window_id\": 42,\n    \"yes_twap_1h\": 0.6823,\n    \"yes_twap_4h\": 0.6741,\n    \"oracle_vol_1h\": 0.0021,\n    \"oracle_vol_4h\": 0.0041,\n    \"volume_1h\": 12400,\n    \"spread_mean\": 0.018,\n    \"spread_min\": 0.012\n  }\n}",
                ],
            ],
        ],
        [
            'group'       => 'Backtest',
            'description' => 'Run parameter sweeps over historical data. Pro tier required.',
            'items'       => [
                [
                    'method' => 'POST',
                    'path'   => '/backtest',
                    'desc'   => 'Submit a backtest job and receive results. Pro only.',
                    'params' => [
                        'strategy (required)'  => 'Strategy identifier string',
                        'params (required)'    => 'JSON object of strategy parameters',
                        'from (required)'      => 'ISO 8601 backtest start date',
                        'to (required)'        => 'ISO 8601 backtest end date',
                    ],
                    'curl'   => "curl -X POST {{ config('app.url') }}/api/v1/backtest \\\n  -H \"Authorization: Bearer YOUR_KEY\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\"strategy\": \"momentum\", \"params\": {\"window\": \"1h\"}, \"from\": \"2024-01-01\", \"to\": \"2024-06-01\"}'",
                    'example' => "{\n  \"data\": {\n    \"job_id\": \"bt_abc123\",\n    \"status\": \"completed\",\n    \"sharpe\": 1.42,\n    \"total_return\": 0.183,\n    \"max_drawdown\": -0.072\n  }\n}",
                ],
            ],
        ],
    ];
    @endphp

    @foreach ($endpoints as $section)
        <section class="mb-10" x-data="{}">
            <div class="flex items-baseline gap-3 mb-4">
                <h2 class="text-base font-mono font-bold text-white">{{ $section['group'] }}</h2>
                <span class="text-xs font-mono text-gray-500">{{ $section['description'] }}</span>
            </div>

            @foreach ($section['items'] as $ep)
                <div x-data="{ open: false }" class="border border-gray-800 rounded-lg mb-3 overflow-hidden">
                    {{-- Endpoint header --}}
                    <button
                        @click="open = !open"
                        class="w-full flex items-center gap-4 px-4 py-3 bg-gray-900/40 hover:bg-gray-900/70 transition-colors text-left"
                    >
                        <span class="text-xs font-mono font-bold px-2 py-0.5 rounded
                            {{ $ep['method'] === 'GET' ? 'bg-blue-500/20 text-blue-300' : 'bg-orange-500/20 text-orange-300' }}">
                            {{ $ep['method'] }}
                        </span>
                        <code class="text-sm font-mono text-white">/v1{{ $ep['path'] }}</code>
                        <span class="text-xs font-mono text-gray-500 flex-1">{{ $ep['desc'] }}</span>
                        <svg class="w-4 h-4 text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- Endpoint details --}}
                    <div x-show="open" x-cloak class="border-t border-gray-800 p-4 space-y-4 bg-[#0f0f0f]/50">

                        @if (! empty($ep['params']))
                            <div>
                                <div class="text-xs font-mono text-gray-500 uppercase tracking-widest mb-2">Parameters</div>
                                <div class="space-y-1">
                                    @foreach ($ep['params'] as $param => $paramDesc)
                                        <div class="flex items-start gap-3 text-xs font-mono">
                                            <code class="text-[#22c55e] min-w-0 flex-shrink-0">{{ $param }}</code>
                                            <span class="text-gray-400">{{ $paramDesc }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div>
                            <div class="text-xs font-mono text-gray-500 uppercase tracking-widest mb-2">Example Request</div>
                            <div class="bg-gray-900 border border-gray-800 rounded p-3">
                                <pre class="text-xs font-mono text-gray-300 whitespace-pre-wrap">{{ $ep['curl'] }}</pre>
                            </div>
                        </div>

                        <div>
                            <div class="text-xs font-mono text-gray-500 uppercase tracking-widest mb-2">Example Response</div>
                            <div class="bg-gray-900 border border-gray-800 rounded p-3">
                                <pre class="text-xs font-mono text-gray-300 whitespace-pre">{{ $ep['example'] }}</pre>
                            </div>
                        </div>

                    </div>
                </div>
            @endforeach
        </section>
    @endforeach

    {{-- Error codes --}}
    <section class="mb-10">
        <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-4">Error Codes</h2>
        <div class="bg-gray-900/50 border border-gray-800 rounded-lg overflow-hidden">
            <table class="w-full text-xs font-mono">
                <thead>
                    <tr class="border-b border-gray-800 bg-gray-900/50">
                        <th class="text-left text-gray-500 px-4 py-2.5 uppercase tracking-widest">HTTP</th>
                        <th class="text-left text-gray-500 px-4 py-2.5 uppercase tracking-widest">Code</th>
                        <th class="text-left text-gray-500 px-4 py-2.5 uppercase tracking-widest">Meaning</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <tr><td class="px-4 py-2.5 text-red-400">401</td><td class="px-4 py-2.5 text-gray-300">UNAUTHENTICATED</td><td class="px-4 py-2.5 text-gray-500">Missing or invalid API key</td></tr>
                    <tr><td class="px-4 py-2.5 text-red-400">403</td><td class="px-4 py-2.5 text-gray-300">FORBIDDEN</td><td class="px-4 py-2.5 text-gray-500">Tier does not have access to this endpoint</td></tr>
                    <tr><td class="px-4 py-2.5 text-yellow-400">422</td><td class="px-4 py-2.5 text-gray-300">VALIDATION_ERROR</td><td class="px-4 py-2.5 text-gray-500">Request parameters failed validation</td></tr>
                    <tr><td class="px-4 py-2.5 text-yellow-400">429</td><td class="px-4 py-2.5 text-gray-300">RATE_LIMIT_EXCEEDED</td><td class="px-4 py-2.5 text-gray-500">Daily request quota exceeded</td></tr>
                    <tr><td class="px-4 py-2.5 text-red-400">404</td><td class="px-4 py-2.5 text-gray-300">NOT_FOUND</td><td class="px-4 py-2.5 text-gray-500">Resource does not exist</td></tr>
                    <tr><td class="px-4 py-2.5 text-red-400">500</td><td class="px-4 py-2.5 text-gray-300">SERVER_ERROR</td><td class="px-4 py-2.5 text-gray-500">Internal server error</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- Rate limits --}}
    <section class="mb-10">
        <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-4">Rate Limits</h2>
        <div class="bg-gray-900/50 border border-gray-800 rounded-lg overflow-hidden">
            <table class="w-full text-xs font-mono">
                <thead>
                    <tr class="border-b border-gray-800 bg-gray-900/50">
                        <th class="text-left text-gray-500 px-4 py-2.5 uppercase tracking-widest">Tier</th>
                        <th class="text-left text-gray-500 px-4 py-2.5 uppercase tracking-widest">Requests/day</th>
                        <th class="text-left text-gray-500 px-4 py-2.5 uppercase tracking-widest">History</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <tr><td class="px-4 py-2.5 text-gray-300">Free</td><td class="px-4 py-2.5 text-gray-400">100</td><td class="px-4 py-2.5 text-gray-400">7 days</td></tr>
                    <tr><td class="px-4 py-2.5 text-blue-300">Builder</td><td class="px-4 py-2.5 text-gray-400">10,000</td><td class="px-4 py-2.5 text-gray-400">90 days</td></tr>
                    <tr><td class="px-4 py-2.5 text-purple-300">Pro</td><td class="px-4 py-2.5 text-gray-400">100,000</td><td class="px-4 py-2.5 text-gray-400">Unlimited</td></tr>
                </tbody>
            </table>
        </div>
    </section>

</div>
@endsection
