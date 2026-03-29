<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>API Reference — Polymarket Data</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] } } }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        .mono { font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', monospace; }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #17181c; }
        ::-webkit-scrollbar-thumb { background: #2e3841; border-radius: 4px; }
        [x-cloak] { display: none !important; }
        .nav-link.active { color: #0093fd !important; background: rgba(0,147,253,0.08); }
        html { scroll-behavior: smooth; }
    </style>
</head>

@php
$base = config('app.url');

$sections = [
    [
        'id'    => 'windows',
        'label' => 'Windows',
        'desc'  => 'Resolved binary market windows with all features pre-computed.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/windows',
                'title'   => 'List Windows',
                'desc'    => 'Paginated list of resolved binary market windows. Each window is a single Polymarket market with aggregated oracle, CLOB, and feature data.',
                'params'  => [
                    ['name'=>'asset',    'type'=>'text',   'placeholder'=>'BTC, ETH, SOL',  'required'=>false],
                    ['name'=>'outcome',  'type'=>'select', 'options'=>['','YES','NO'],        'required'=>false],
                    ['name'=>'from',     'type'=>'text',   'placeholder'=>'2025-01-01',       'required'=>false],
                    ['name'=>'to',       'type'=>'text',   'placeholder'=>'2025-12-31',       'required'=>false],
                    ['name'=>'page',     'type'=>'number', 'placeholder'=>'1',               'required'=>false],
                    ['name'=>'per_page', 'type'=>'number', 'placeholder'=>'50',              'required'=>false],
                ],
                'example' => "{\n  \"data\": [\n    {\n      \"id\": \"btc-updown-5m-1711000000\",\n      \"asset\": \"BTC\",\n      \"break_price_usd\": 87000,\n      \"open_ts\": 1711000000000,\n      \"close_ts\": 1711000300000,\n      \"duration_sec\": 300,\n      \"outcome\": \"YES\",\n      \"oracle_open\": 86950.5,\n      \"oracle_close\": 87120.0,\n      \"yes_twap\": 0.6842,\n      \"oracle_dist_bp_at_1m\": 142\n    }\n  ],\n  \"meta\": { \"total\": 4821, \"page\": 1, \"per_page\": 50 }\n}",
            ],
            [
                'method'  => 'GET',
                'path'    => '/api/v1/windows/{id}',
                'title'   => 'Get Window',
                'desc'    => 'Full detail for a single window including every computed feature column.',
                'params'  => [
                    ['name'=>'id', 'type'=>'text', 'placeholder'=>'btc-updown-5m-1711000000', 'required'=>true, 'pathParam'=>true],
                ],
                'example' => "{\n  \"data\": {\n    \"id\": \"btc-updown-5m-1711000000\",\n    \"asset\": \"BTC\",\n    \"break_price_usd\": 87000,\n    \"outcome\": \"YES\",\n    \"oracle_open\": 86950.5,\n    \"oracle_close\": 87120.0,\n    \"oracle_high\": 87180.0,\n    \"oracle_low\": 86900.0,\n    \"oracle_dist_bp_at_1m\": 142,\n    \"yes_twap\": 0.6842,\n    \"yes_vol\": 0.0124,\n    \"spread_mean\": 0.021,\n    \"spread_min\": 0.012\n  }\n}",
            ],
        ],
    ],
    [
        'id'    => 'features',
        'label' => 'Features',
        'desc'  => 'Flat feature vectors for bulk ML training.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/features',
                'title'   => 'List Features',
                'desc'    => 'All pre-computed feature vectors for every resolved window. Useful for loading directly into a Pandas DataFrame or NumPy array.',
                'params'  => [
                    ['name'=>'asset',    'type'=>'text',   'placeholder'=>'BTC',  'required'=>false],
                    ['name'=>'outcome',  'type'=>'select', 'options'=>['','YES','NO'], 'required'=>false],
                    ['name'=>'page',     'type'=>'number', 'placeholder'=>'1',    'required'=>false],
                    ['name'=>'per_page', 'type'=>'number', 'placeholder'=>'200',  'required'=>false],
                ],
                'example' => "{\n  \"data\": [\n    {\n      \"window_id\": \"btc-updown-5m-1711000000\",\n      \"outcome\": \"YES\",\n      \"oracle_dist_bp_at_1m\": 142,\n      \"oracle_dist_bp_at_2m\": 98,\n      \"yes_twap_1m\": 0.6842,\n      \"yes_twap_full\": 0.7100,\n      \"spread_mean\": 0.021,\n      \"oracle_vol\": 0.0041\n    }\n  ],\n  \"meta\": { \"total\": 4821, \"page\": 1, \"per_page\": 200 }\n}",
            ],
        ],
    ],
    [
        'id'    => 'oracle',
        'label' => 'Oracle Ticks',
        'desc'  => 'Raw Chainlink price recordings at ~3 second cadence.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/oracle/ticks',
                'title'   => 'Oracle Ticks',
                'desc'    => 'Raw oracle price recordings. Timestamps are Unix milliseconds. Returns up to 500 records per page.',
                'params'  => [
                    ['name'=>'asset', 'type'=>'text',   'placeholder'=>'BTC',             'required'=>false],
                    ['name'=>'from',  'type'=>'text',   'placeholder'=>'1711000000000',   'required'=>false],
                    ['name'=>'to',    'type'=>'text',   'placeholder'=>'1711000300000',   'required'=>false],
                    ['name'=>'page',  'type'=>'number', 'placeholder'=>'1',               'required'=>false],
                ],
                'example' => "{\n  \"data\": [\n    { \"ts\": 1711000000123, \"asset\": \"BTC\", \"price_usd\": 86950.5 },\n    { \"ts\": 1711000003456, \"asset\": \"BTC\", \"price_usd\": 86952.0 },\n    { \"ts\": 1711000006789, \"asset\": \"BTC\", \"price_usd\": 86948.5 }\n  ],\n  \"meta\": { \"total\": 6000, \"page\": 1, \"per_page\": 500 }\n}",
            ],
            [
                'method'  => 'GET',
                'path'    => '/api/v1/oracle/range',
                'title'   => 'Oracle Range',
                'desc'    => 'Summary statistics for oracle ticks within a time range or for a specific market window.',
                'params'  => [
                    ['name'=>'asset',     'type'=>'text', 'placeholder'=>'BTC',                        'required'=>true],
                    ['name'=>'window_id', 'type'=>'text', 'placeholder'=>'btc-updown-5m-1711000000',   'required'=>false],
                    ['name'=>'from',      'type'=>'text', 'placeholder'=>'1711000000000',              'required'=>false],
                    ['name'=>'to',        'type'=>'text', 'placeholder'=>'1711000300000',              'required'=>false],
                ],
                'example' => "{\n  \"data\": {\n    \"asset\": \"BTC\",\n    \"count\": 98,\n    \"open\": 86950.5,\n    \"close\": 87120.0,\n    \"high\": 87180.0,\n    \"low\": 86900.0\n  }\n}",
            ],
            [
                'method'  => 'GET',
                'path'    => '/api/v1/oracle/aligned',
                'title'   => 'Oracle Aligned',
                'desc'    => 'Oracle ticks joined with CLOB snapshots for a window — ideal for time-series model input.',
                'params'  => [
                    ['name'=>'window_id', 'type'=>'text', 'placeholder'=>'btc-updown-5m-1711000000', 'required'=>true],
                ],
                'example' => "{\n  \"data\": [\n    {\n      \"ts\": 1711000000123,\n      \"price_usd\": 86950.5,\n      \"yes_bid\": 0.67, \"yes_ask\": 0.69,\n      \"no_bid\": 0.31,  \"no_ask\": 0.33\n    }\n  ]\n}",
            ],
        ],
    ],
    [
        'id'    => 'clob',
        'label' => 'CLOB Snapshots',
        'desc'  => 'Bid/ask quotes for YES and NO tokens at each oracle tick.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/clob/snapshots',
                'title'   => 'CLOB Snapshots',
                'desc'    => 'Full order-book bid/ask snapshots for a market window. Synced to oracle ticks.',
                'params'  => [
                    ['name'=>'window_id', 'type'=>'text',   'placeholder'=>'btc-updown-5m-1711000000', 'required'=>true],
                    ['name'=>'from',      'type'=>'text',   'placeholder'=>'1711000000000',            'required'=>false],
                    ['name'=>'to',        'type'=>'text',   'placeholder'=>'1711000300000',            'required'=>false],
                    ['name'=>'page',      'type'=>'number', 'placeholder'=>'1',                        'required'=>false],
                ],
                'example' => "{\n  \"data\": [\n    {\n      \"ts\": 1711000000123,\n      \"yes_bid\": 0.67,\n      \"yes_ask\": 0.69,\n      \"no_bid\": 0.31,\n      \"no_ask\": 0.33,\n      \"spread\": 0.02\n    }\n  ],\n  \"meta\": { \"total\": 98, \"page\": 1, \"per_page\": 500 }\n}",
            ],
        ],
    ],
    [
        'id'    => 'markets',
        'label' => 'Markets',
        'desc'  => 'Live and upcoming market windows.',
        'pro'   => false,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/markets/active',
                'title'   => 'Active Markets',
                'desc'    => 'All currently open or upcoming binary market windows being tracked by the oracle.',
                'params'  => [
                    ['name'=>'asset', 'type'=>'text', 'placeholder'=>'BTC', 'required'=>false],
                ],
                'example' => "{\n  \"data\": [\n    {\n      \"id\": \"btc-updown-5m-1711001000\",\n      \"asset\": \"BTC\",\n      \"break_price_usd\": 87500,\n      \"open_ts\": 1711001000000,\n      \"close_ts\": 1711001300000,\n      \"yes_bid\": 0.51,\n      \"yes_ask\": 0.53\n    }\n  ]\n}",
            ],
        ],
    ],
    [
        'id'    => 'export',
        'label' => 'Export',
        'desc'  => 'Bulk downloads for offline model training.',
        'pro'   => true,
        'endpoints' => [
            [
                'method'  => 'GET',
                'path'    => '/api/v1/export/csv',
                'title'   => 'Export CSV',
                'desc'    => 'Download all window features as a CSV file. Streams directly — no pagination. Optionally filter by asset.',
                'params'  => [
                    ['name'=>'asset', 'type'=>'text', 'placeholder'=>'BTC (optional)', 'required'=>false],
                ],
                'example' => "window_id,asset,outcome,break_price_usd,oracle_dist_bp_at_1m,...\nbtc-updown-5m-1711000000,BTC,YES,87000,142,...\nbtc-updown-5m-1711001000,BTC,NO,87500,-68,...",
            ],
            [
                'method'  => 'GET',
                'path'    => '/api/v1/export/sqlite',
                'title'   => 'Export SQLite',
                'desc'    => 'Download a complete SQLite database containing all tables: windows, oracle_ticks, clob_snapshots, features.',
                'params'  => [],
                'example' => "→ Binary SQLite file download\nContent-Type: application/x-sqlite3\nContent-Disposition: attachment; filename=\"polymarket_export.db\"",
            ],
        ],
    ],
    [
        'id'    => 'backtest',
        'label' => 'Backtest',
        'desc'  => 'Run parameter sweeps against full history.',
        'pro'   => true,
        'endpoints' => [
            [
                'method'  => 'POST',
                'path'    => '/api/v1/backtest',
                'title'   => 'Run Backtest',
                'desc'    => 'Submit a strategy and receive performance metrics synchronously. Runs against the full resolved window history.',
                'params'  => [
                    ['name'=>'strategy', 'type'=>'text', 'placeholder'=>'momentum', 'required'=>true],
                    ['name'=>'from',     'type'=>'text', 'placeholder'=>'2025-01-01', 'required'=>true],
                    ['name'=>'to',       'type'=>'text', 'placeholder'=>'2025-12-31', 'required'=>true],
                ],
                'example' => "{\n  \"data\": {\n    \"strategy\": \"momentum\",\n    \"total_trades\": 1240,\n    \"win_rate\": 0.573,\n    \"sharpe\": 1.42,\n    \"total_return\": 0.183,\n    \"max_drawdown\": -0.072\n  }\n}",
            ],
        ],
    ],
];
@endphp

<body class="bg-[#0a0b10] text-[#e5e5e5] antialiased" x-data="docsApp()" x-init="init()">

{{-- ── Top Nav ──────────────────────────────────────────────────────────── --}}
<nav class="fixed top-0 left-0 right-0 z-50 h-14 border-b border-[#1f2937] bg-[#0a0b10]/95 backdrop-blur-sm flex items-center px-5 gap-4">
    <a href="{{ route('home') }}" class="flex items-center gap-2 flex-shrink-0">
        <div class="w-6 h-6 rounded-md bg-[#0093fd] flex items-center justify-center">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M7 1L13 4.5V9.5L7 13L1 9.5V4.5L7 1Z" fill="white" fill-opacity="0.9"/></svg>
        </div>
        <span class="text-white font-semibold text-sm hidden sm:block">Polymarket Data</span>
    </a>
    <span class="text-[#2e3841] hidden sm:block">/</span>
    <span class="text-[#697d91] text-sm font-medium hidden sm:block">API Reference</span>

    <div class="flex-1"></div>

    {{-- Inline key input --}}
    <div class="flex items-center gap-2">
        <label class="text-[#697d91] text-xs hidden md:block flex-shrink-0">API Key</label>
        <div class="relative">
            <input
                type="password"
                x-model="apiKey"
                @input="saveKey()"
                placeholder="Paste key to test live"
                class="bg-[#17181c] border border-[#1f2937] focus:border-[#0093fd] rounded-lg px-3 py-1.5 text-xs text-[#e5e5e5] placeholder-[#2e3841] w-44 sm:w-56 outline-none transition-colors mono"
            />
            <div x-show="apiKey" class="absolute right-2.5 top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full bg-[#0093fd]"></div>
        </div>
    </div>
    <a href="{{ route('dashboard') }}" class="text-[#697d91] hover:text-[#e5e5e5] text-sm font-medium transition-colors hidden sm:block">Dashboard</a>
</nav>

{{-- ── Layout ───────────────────────────────────────────────────────────── --}}
<div class="flex pt-14">

    {{-- Sidebar --}}
    <aside class="fixed top-14 left-0 bottom-0 w-56 border-r border-[#1f2937] overflow-y-auto hidden lg:block py-6 px-3">
        <div class="space-y-0.5">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-[#2e3841] px-3 py-2">Getting Started</p>
            <a href="#introduction"   class="nav-link block px-3 py-1.5 rounded-lg text-sm text-[#697d91] hover:text-[#e5e5e5] hover:bg-[#17181c] transition-colors cursor-pointer">Introduction</a>
            <a href="#authentication" class="nav-link block px-3 py-1.5 rounded-lg text-sm text-[#697d91] hover:text-[#e5e5e5] hover:bg-[#17181c] transition-colors cursor-pointer">Authentication</a>
            <a href="#rate-limits"    class="nav-link block px-3 py-1.5 rounded-lg text-sm text-[#697d91] hover:text-[#e5e5e5] hover:bg-[#17181c] transition-colors cursor-pointer">Rate Limits</a>
            <a href="#errors"         class="nav-link block px-3 py-1.5 rounded-lg text-sm text-[#697d91] hover:text-[#e5e5e5] hover:bg-[#17181c] transition-colors cursor-pointer">Errors</a>

            <p class="text-[10px] font-semibold uppercase tracking-widest text-[#2e3841] px-3 py-2 mt-4">Endpoints</p>
            @foreach($sections as $s)
            <a href="#{{ $s['id'] }}" class="nav-link flex items-center justify-between px-3 py-1.5 rounded-lg text-sm text-[#697d91] hover:text-[#e5e5e5] hover:bg-[#17181c] transition-colors">
                {{ $s['label'] }}
                @if($s['pro'])
                    <span class="text-[10px] font-semibold text-purple-400 bg-purple-500/10 px-1.5 py-0.5 rounded-full border border-purple-500/20 leading-none">Pro</span>
                @endif
            </a>
            @endforeach
        </div>
    </aside>

    {{-- Content --}}
    <main class="lg:ml-56 flex-1 min-w-0">
        <div class="max-w-3xl mx-auto px-5 sm:px-8 lg:px-12 py-12 space-y-20">

        {{-- ── Introduction ─────────────────────────────────────────── --}}
        <section id="introduction" class="scroll-mt-20">
            <span class="text-xs font-semibold text-[#0093fd] uppercase tracking-widest">Getting Started</span>
            <h1 class="text-3xl font-bold text-white mt-2 mb-3">API Reference</h1>
            <p class="text-[#697d91] leading-relaxed mb-6">
                Programmatic access to raw oracle recordings, CLOB bid-ask snapshots, and pre-computed ML features for every resolved Polymarket binary market.
            </p>

            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-5 mb-4">
                <p class="text-xs text-[#697d91] mb-1.5">Base URL</p>
                <div class="flex items-center gap-3">
                    <code class="mono text-sm text-[#0093fd] flex-1 break-all">{{ $base }}/api</code>
                    <button onclick="navigator.clipboard.writeText('{{ $base }}/api')"
                        class="text-xs text-[#697d91] hover:text-[#e5e5e5] border border-[#2e3841] px-2.5 py-1 rounded-lg transition-colors flex-shrink-0">Copy</button>
                </div>
            </div>

            <div class="bg-[#0093fd]/5 border border-[#0093fd]/15 rounded-2xl p-4 flex items-start gap-3">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="mt-0.5 flex-shrink-0"><circle cx="8" cy="8" r="7" stroke="#0093fd" stroke-width="1.3"/><path d="M8 7v5M8 5h.01" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round"/></svg>
                <p class="text-sm text-[#697d91]">
                    Paste your API key in the nav bar above to make every endpoint on this page testable with one click.
                    @guest <a href="{{ route('register') }}" class="text-[#0093fd] hover:underline">Create a free account</a> to get your key. @endguest
                </p>
            </div>
        </section>

        {{-- ── Authentication ───────────────────────────────────────── --}}
        <section id="authentication" class="scroll-mt-20">
            <h2 class="text-xl font-bold text-white mb-2">Authentication</h2>
            <p class="text-[#697d91] text-sm mb-5">Every data request requires a <code class="mono text-xs text-[#0093fd]">Bearer</code> token. Get yours from the <a href="{{ route('dashboard') }}" class="text-[#0093fd] hover:underline">dashboard</a>.</p>

            <div x-data="codeBlock()" class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-[#1f2937]">
                    <div class="flex gap-1">
                        @foreach(['curl','python','js'] as $lang)
                        <button @click="tab='{{ $lang }}'" :class="tab==='{{ $lang }}' ? 'bg-[#0093fd]/15 text-[#0093fd]' : 'text-[#697d91] hover:text-[#e5e5e5]'"
                            class="mono text-xs px-3 py-1 rounded-lg transition-colors">{{ $lang }}</button>
                        @endforeach
                    </div>
                    <button @click="copy(tab)" :class="copied ? 'text-[#0093fd]' : 'text-[#697d91] hover:text-[#e5e5e5]'" class="mono text-xs transition-colors" x-text="copied ? 'Copied!' : 'Copy'"></button>
                </div>
                <div class="p-5 bg-[#0a0b10]">
                    <pre x-show="tab==='curl'"   class="mono text-xs text-[#697d91] whitespace-pre leading-6" x-html="'curl &quot;{{ $base }}/api/v1/windows&quot; \\\n  -H &quot;Authorization: Bearer ' + (apiKey || 'YOUR_API_KEY') + '&quot;'"></pre>
                    <pre x-show="tab==='python'" class="mono text-xs text-[#697d91] whitespace-pre leading-6" x-html="'import requests\n\nheaders = {&quot;Authorization&quot;: &quot;Bearer ' + (apiKey || 'YOUR_API_KEY') + '&quot;}\nres = requests.get(&quot;{{ $base }}/api/v1/windows&quot;, headers=headers)\nprint(res.json())'"></pre>
                    <pre x-show="tab==='js'"     class="mono text-xs text-[#697d91] whitespace-pre leading-6" x-html="'const res = await fetch(\'{{ $base }}/api/v1/windows\', {\n  headers: { \'Authorization\': `Bearer ' + (apiKey || 'YOUR_API_KEY') + '` }\n});\nconsole.log(await res.json());'"></pre>
                </div>
            </div>
        </section>

        {{-- ── Rate Limits ───────────────────────────────────────────── --}}
        <section id="rate-limits" class="scroll-mt-20">
            <h2 class="text-xl font-bold text-white mb-2">Rate Limits</h2>
            <p class="text-[#697d91] text-sm mb-5">Limits reset at midnight UTC. Responses include <code class="mono text-xs text-[#0093fd]">X-RateLimit-Remaining</code> and <code class="mono text-xs text-[#0093fd]">Retry-After</code> headers.</p>
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead><tr class="border-b border-[#1f2937]">
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">Tier</th>
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">Requests / day</th>
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">History</th>
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">Export</th>
                    </tr></thead>
                    <tbody class="divide-y divide-[#1f2937]">
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-[#e5e5e5] font-medium">Free</td><td class="px-5 py-3 text-[#697d91] mono text-xs">100</td><td class="px-5 py-3 text-[#697d91]">7 days</td><td class="px-5 py-3 text-[#2e3841]">—</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-[#0093fd] font-medium">Builder</td><td class="px-5 py-3 text-[#697d91] mono text-xs">10,000</td><td class="px-5 py-3 text-[#697d91]">90 days</td><td class="px-5 py-3 text-[#2e3841]">—</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-purple-300 font-medium">Pro</td><td class="px-5 py-3 text-[#697d91] mono text-xs">100,000</td><td class="px-5 py-3 text-[#697d91]">Unlimited</td><td class="px-5 py-3 text-[#26a05e] text-xs">CSV · SQLite</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ── Errors ────────────────────────────────────────────────── --}}
        <section id="errors" class="scroll-mt-20">
            <h2 class="text-xl font-bold text-white mb-2">Errors</h2>
            <p class="text-[#697d91] text-sm mb-5">All errors use the same JSON envelope:</p>
            <div class="bg-[#0a0b10] border border-[#1f2937] rounded-xl p-4 mono text-xs leading-6 mb-5">
<span class="text-[#697d91]">{
  </span><span class="text-[#9cdcfe]">"error"</span><span class="text-[#697d91]">: </span><span class="text-[#ce9178]">"Rate limit exceeded."</span><span class="text-[#697d91]">,
  </span><span class="text-[#9cdcfe]">"code"</span><span class="text-[#697d91]">: </span><span class="text-[#ce9178]">"RATE_LIMIT_EXCEEDED"</span><span class="text-[#697d91]">
}</span>
            </div>
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
                <table class="w-full">
                    <thead><tr class="border-b border-[#1f2937]">
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">HTTP</th>
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">code</th>
                        <th class="text-left text-xs font-medium text-[#697d91] px-5 py-3.5">Meaning</th>
                    </tr></thead>
                    <tbody class="divide-y divide-[#1f2937] text-xs">
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-red-400 mono font-bold">401</td><td class="px-5 py-3 text-[#e5e5e5] mono">UNAUTHENTICATED</td><td class="px-5 py-3 text-[#697d91]">Missing or invalid API key</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-red-400 mono font-bold">403</td><td class="px-5 py-3 text-[#e5e5e5] mono">FORBIDDEN</td><td class="px-5 py-3 text-[#697d91]">Tier does not include this endpoint</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-red-400 mono font-bold">403</td><td class="px-5 py-3 text-[#e5e5e5] mono">EMAIL_NOT_VERIFIED</td><td class="px-5 py-3 text-[#697d91]">Email address not yet verified</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-amber-400 mono font-bold">422</td><td class="px-5 py-3 text-[#e5e5e5] mono">VALIDATION_ERROR</td><td class="px-5 py-3 text-[#697d91]">Invalid or missing parameters</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-amber-400 mono font-bold">429</td><td class="px-5 py-3 text-[#e5e5e5] mono">RATE_LIMIT_EXCEEDED</td><td class="px-5 py-3 text-[#697d91]">Daily quota exhausted</td></tr>
                        <tr class="hover:bg-[#1e2428]/50"><td class="px-5 py-3 text-red-400 mono font-bold">404</td><td class="px-5 py-3 text-[#e5e5e5] mono">NOT_FOUND</td><td class="px-5 py-3 text-[#697d91]">Resource does not exist</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ── Endpoint Sections ─────────────────────────────────────── --}}
        @foreach($sections as $section)
        <section id="{{ $section['id'] }}" class="scroll-mt-20">
            <div class="flex items-center gap-3 mb-2">
                <h2 class="text-xl font-bold text-white">{{ $section['label'] }}</h2>
                @if($section['pro'])
                    <span class="text-xs font-semibold text-purple-300 bg-purple-500/10 px-2.5 py-1 rounded-full border border-purple-500/20">Pro only</span>
                @endif
            </div>
            <p class="text-[#697d91] text-sm mb-6">{{ $section['desc'] }}</p>

            <div class="space-y-4">
            @foreach($section['endpoints'] as $ep)
            {{-- Each endpoint is a self-contained Alpine component --}}
            <div x-data="endpt({{ Js::from($ep) }}, {{ Js::from($base) }})"
                 class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden hover:border-[#2e3841] transition-colors">

                {{-- Header (click to expand) --}}
                <div class="flex items-center gap-3 px-5 py-4 cursor-pointer select-none" @click="open = !open">
                    <span class="mono text-[11px] font-bold px-2 py-0.5 rounded-md flex-shrink-0"
                          :class="cfg.method === 'GET' ? 'bg-[#0093fd]/15 text-[#0093fd]' : 'bg-orange-500/15 text-orange-300'"
                          x-text="cfg.method"></span>
                    <code class="mono text-sm text-[#e5e5e5] flex-1 min-w-0 truncate" x-text="cfg.path"></code>
                    @if($section['pro'])
                        <span class="text-[10px] font-semibold text-purple-300 bg-purple-500/10 px-2 py-0.5 rounded-full border border-purple-500/20 flex-shrink-0 hidden sm:block">Pro</span>
                    @endif
                    <span class="text-sm text-[#697d91] hidden md:block flex-shrink-0">{{ $ep['title'] }}</span>
                    <svg class="w-4 h-4 text-[#697d91] transition-transform duration-200 flex-shrink-0" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </div>

                {{-- Body --}}
                <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     class="border-t border-[#1f2937] p-5 space-y-5">

                    <p class="text-sm text-[#697d91]">{{ $ep['desc'] }}</p>

                    {{-- Parameters --}}
                    @if(count($ep['params']) > 0)
                    <div>
                        <p class="text-xs font-semibold text-[#697d91] uppercase tracking-widest mb-3">Parameters</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach($ep['params'] as $p)
                            <div>
                                <label class="block text-xs text-[#697d91] mb-1 mono">
                                    {{ $p['name'] }}@if(!empty($p['required'])) <span class="text-red-400">*</span>@endif
                                    @if(!empty($p['pathParam'])) <span class="text-[#2e3841]">(path)</span>@endif
                                </label>
                                @if(isset($p['options']))
                                <select x-model="vals.{{ $p['name'] }}"
                                    class="w-full bg-[#0a0b10] border border-[#1f2937] focus:border-[#0093fd] rounded-lg px-3 py-2 text-sm text-[#e5e5e5] outline-none transition-colors mono">
                                    @foreach($p['options'] as $opt)
                                    <option value="{{ $opt }}">{{ $opt ?: '— any —' }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="{{ $p['type'] ?? 'text' }}"
                                    x-model="vals.{{ $p['name'] }}"
                                    placeholder="{{ $p['placeholder'] ?? '' }}"
                                    class="w-full bg-[#0a0b10] border border-[#1f2937] focus:border-[#0093fd] focus:ring-1 focus:ring-[#0093fd]/20 rounded-lg px-3 py-2 text-sm text-[#e5e5e5] placeholder-[#2e3841] outline-none transition-colors mono" />
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Code tabs --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex gap-1">
                                @foreach(['curl','python','js'] as $lang)
                                <button @click="tab='{{ $lang }}'" :class="tab==='{{ $lang }}' ? 'bg-[#0093fd]/15 text-[#0093fd]' : 'text-[#697d91] hover:text-[#e5e5e5]'"
                                    class="mono text-xs px-3 py-1 rounded-lg border border-transparent transition-colors">{{ $lang }}</button>
                                @endforeach
                            </div>
                            <button @click="copy()" :class="copiedCode ? 'text-[#0093fd]' : 'text-[#697d91] hover:text-[#e5e5e5]'"
                                class="mono text-xs transition-colors" x-text="copiedCode ? 'Copied!' : 'Copy'"></button>
                        </div>
                        <div class="bg-[#0a0b10] border border-[#1f2937] rounded-xl p-4 overflow-x-auto">
                            <pre class="mono text-xs text-[#697d91] whitespace-pre leading-6" x-text="codeExample()"></pre>
                        </div>
                    </div>

                    {{-- Send button --}}
                    <div class="flex items-center gap-3">
                        <button @click="run()" :disabled="loading"
                            class="flex items-center gap-2 bg-[#0093fd] hover:bg-[#0080e0] disabled:opacity-50 text-white font-semibold text-sm px-5 py-2 rounded-xl transition-colors">
                            <span x-show="!loading">Send request</span>
                            <span x-show="loading" x-cloak class="flex items-center gap-2">
                                <svg class="animate-spin w-3.5 h-3.5" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                Sending…
                            </span>
                        </button>
                        <span x-show="elapsed && !loading" class="text-xs text-[#697d91] mono" x-text="elapsed + 'ms'"></span>
                        <span x-show="!apiKey && !loading" class="text-xs text-amber-400/80">← paste your API key in the nav to test live</span>
                    </div>

                    {{-- Response / Example --}}
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <p class="text-xs font-semibold text-[#697d91] uppercase tracking-widest" x-text="response !== null ? 'Response' : 'Example Response'"></p>
                            <span x-show="status !== null" class="mono text-xs font-bold px-2 py-0.5 rounded-md"
                                :class="status >= 200 && status < 300 ? 'bg-[#26a05e]/15 text-[#26a05e]' : 'bg-red-500/15 text-red-400'"
                                x-text="'HTTP ' + status"></span>
                        </div>
                        <div class="bg-[#0a0b10] rounded-xl overflow-hidden border"
                             :class="status !== null ? (status >= 200 && status < 300 ? 'border-[#26a05e]/20' : 'border-red-500/20') : 'border-[#1f2937]'">
                            <pre class="p-4 mono text-xs text-[#697d91] overflow-x-auto max-h-72 leading-6 whitespace-pre-wrap"
                                 x-text="response !== null ? response : cfg.example"></pre>
                        </div>
                    </div>

                </div>
            </div>
            @endforeach
            </div>
        </section>
        @endforeach

        </div>{{-- /content --}}
    </main>
</div>

<script>
function docsApp() {
    return {
        apiKey: '',
        init() {
            this.apiKey = localStorage.getItem('pm_api_key') || '';
            // Sidebar active link on scroll
            const links = document.querySelectorAll('.nav-link');
            const obs = new IntersectionObserver(entries => {
                entries.forEach(e => {
                    if (e.isIntersecting) {
                        links.forEach(l => l.classList.remove('active'));
                        const match = document.querySelector(`.nav-link[href="#${e.target.id}"]`);
                        if (match) match.classList.add('active');
                    }
                });
            }, { rootMargin: '-20% 0px -70% 0px' });
            document.querySelectorAll('section[id]').forEach(s => obs.observe(s));
        },
        saveKey() {
            localStorage.setItem('pm_api_key', this.apiKey);
        },
    }
}

function codeBlock() {
    return {
        tab: 'curl',
        copied: false,
        copy(tab) {
            const pre = this.$el.querySelectorAll('pre');
            const idx = ['curl','python','js'].indexOf(tab);
            navigator.clipboard.writeText(pre[idx]?.innerText || '');
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
    }
}

function endpt(cfg, base) {
    // Build initial vals from params
    const vals = {};
    (cfg.params || []).forEach(p => vals[p.name] = '');

    return {
        cfg, base,
        vals,
        tab: 'curl',
        open: false,
        loading: false,
        response: null,
        status: null,
        elapsed: null,
        copiedCode: false,

        get apiKey() {
            // Reach into parent Alpine scope
            return document.querySelector('[x-model="apiKey"]')?.value || '';
        },

        buildUrl() {
            let url = base + cfg.path;
            const qs = new URLSearchParams();
            (cfg.params || []).forEach(p => {
                const v = this.vals[p.name];
                if (url.includes('{' + p.name + '}')) {
                    url = url.replace('{' + p.name + '}', v ? encodeURIComponent(v) : ('<' + p.name + '>'));
                } else if (v) {
                    qs.append(p.name, v);
                }
            });
            return url + (qs.toString() ? '?' + qs.toString() : '');
        },

        codeExample() {
            const k = this.apiKey || 'YOUR_API_KEY';
            const url = this.buildUrl();
            if (this.tab === 'curl') {
                const method = cfg.method === 'POST' ? " \\\n  -X POST" : '';
                return `curl "${url}"${method} \\\n  -H "Authorization: Bearer ${k}"`;
            }
            if (this.tab === 'python') {
                const qp = (cfg.params||[]).filter(p => !cfg.path.includes('{'+p.name+'}') && this.vals[p.name]);
                const ps = qp.length ? `params = {${qp.map(p => `"${p.name}": "${this.vals[p.name]}"`).join(', ')}}\n` : '';
                return `import requests\n\nheaders = {"Authorization": "Bearer ${k}"}\n${ps}res = requests.${cfg.method.toLowerCase()}("${base + cfg.path.replace(/\{[^}]+\}/g, m => { const n=m.slice(1,-1); return this.vals[n]||m; })}"${ps?', params=params':''}, headers=headers)\nprint(res.json())`;
            }
            // js
            return `const res = await fetch('${url}', {\n  headers: { 'Authorization': \`Bearer ${k}\` }\n});\nconsole.log(await res.json());`;
        },

        copy() {
            navigator.clipboard.writeText(this.codeExample());
            this.copiedCode = true;
            setTimeout(() => this.copiedCode = false, 2000);
        },

        async run() {
            const k = this.apiKey;
            if (!k) { alert('Paste your API key in the nav bar first.'); return; }
            this.loading = true; this.response = null; this.status = null;
            const t0 = performance.now();
            try {
                const res = await fetch(this.buildUrl(), {
                    method: cfg.method,
                    headers: { 'Authorization': 'Bearer ' + k, 'Accept': 'application/json' },
                });
                this.elapsed = Math.round(performance.now() - t0);
                this.status = res.status;
                const ct = res.headers.get('content-type') || '';
                this.response = ct.includes('json')
                    ? JSON.stringify(await res.json(), null, 2)
                    : await res.text();
            } catch(e) {
                this.status = 0;
                this.response = 'Network error: ' + e.message;
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>

</body>
</html>
