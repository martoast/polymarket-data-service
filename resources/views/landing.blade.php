@extends('layouts.app')

@section('title', 'Polymarket Data API — Institutional-Grade Oracle & CLOB Data')

@section('content')

{{-- ============================================================
    HERO
============================================================ --}}
<section class="relative overflow-hidden">

    {{-- Glow --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-[-120px] left-1/2 -translate-x-1/2 w-[900px] h-[500px] bg-[#0093fd]/10 rounded-full blur-[120px]"></div>
        <div class="absolute top-[100px] left-[15%] w-[300px] h-[300px] bg-[#0093fd]/5 rounded-full blur-[80px]"></div>
    </div>

    {{-- Grid overlay --}}
    <div class="absolute inset-0 pointer-events-none opacity-[0.03]"
         style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-28 pb-20">

        {{-- Badge --}}
        <div class="flex justify-start mb-8">
            <div class="inline-flex items-center gap-2 bg-[#0093fd]/8 border border-[#0093fd]/20 rounded-full px-4 py-1.5 text-xs font-medium text-[#0093fd]">
                <span class="w-1.5 h-1.5 bg-[#0093fd] rounded-full animate-pulse"></span>
                Live oracle recording — BTC · ETH · SOL
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

            {{-- Left: copy --}}
            <div>
                <h1 class="text-5xl sm:text-6xl font-extrabold text-white leading-[1.08] tracking-tight mb-6">
                    The data layer<br>
                    for <span class="relative">
                        <span class="text-[#0093fd]">Polymarket</span>
                        <span class="absolute -bottom-1 left-0 right-0 h-px bg-gradient-to-r from-[#0093fd]/60 to-transparent"></span>
                    </span><br>
                    algo traders
                </h1>

                <p class="text-[#8a9ab0] text-lg leading-relaxed mb-10 max-w-lg">
                    Millisecond-accurate oracle ticks, full CLOB order book snapshots, and 40+ pre-computed ML features — streamed live and queryable from day one.
                </p>

                <div class="flex flex-wrap gap-3 mb-12">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-2 bg-[#0093fd] hover:bg-[#007fd6] text-white font-semibold px-6 py-3 rounded-xl transition-all text-sm shadow-lg shadow-[#0093fd]/20">
                        Start for free
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <a href="{{ route('docs') }}"
                       class="inline-flex items-center gap-2 bg-transparent hover:bg-[#17181c] border border-[#2e3841] hover:border-[#3d4f5e] text-[#8a9ab0] hover:text-[#e5e5e5] font-medium px-6 py-3 rounded-xl transition-all text-sm">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.2"/><path d="M4.5 4.5h5M4.5 7h5M4.5 9.5h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
                        View docs
                    </a>
                </div>

                {{-- Trust signals --}}
                <div class="flex items-center gap-4 text-xs text-[#697d91]">
                    <div class="flex items-center gap-1.5">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M6.5 1l1.4 2.9 3.1.4-2.25 2.2.55 3.1L6.5 8.05 3.7 9.6l.55-3.1L2 4.3l3.1-.4L6.5 1z" stroke="#26a05e" stroke-width="1.1" fill="#26a05e" fill-opacity=".2"/></svg>
                        No credit card required
                    </div>
                    <span class="text-[#2e3841]">·</span>
                    <div class="flex items-center gap-1.5">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><circle cx="6.5" cy="6.5" r="5" stroke="#26a05e" stroke-width="1.1"/><path d="M4 6.5l2 2 3-3" stroke="#26a05e" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Up and running in 60 seconds
                    </div>
                    <span class="text-[#2e3841]">·</span>
                    <div class="flex items-center gap-1.5">
                        <svg width="13" height="13" viewBox="0 0 13 13" fill="none"><path d="M6.5 1.5v2M6.5 9.5v2M1.5 6.5h2M9.5 6.5h2M3.4 3.4l1.4 1.4M8.2 8.2l1.4 1.4M3.4 9.6l1.4-1.4M8.2 4.8l1.4-1.4" stroke="#26a05e" stroke-width="1.1" stroke-linecap="round"/></svg>
                        REST API — any language
                    </div>
                </div>
            </div>

            {{-- Right: live data preview --}}
            <div class="relative">
                <div class="absolute -inset-4 bg-[#0093fd]/5 rounded-3xl blur-xl"></div>
                <div class="relative rounded-2xl overflow-hidden border border-[#1f2937] bg-[#0d0e13] shadow-2xl">

                    {{-- Terminal bar --}}
                    <div class="flex items-center justify-between px-5 py-3 bg-[#17181c] border-b border-[#1f2937]">
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-[#ff5f57]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#febc2e]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#28c840]"></div>
                        </div>
                        <span class="text-xs font-mono text-[#697d91]">GET /api/v1/windows</span>
                        <div class="flex items-center gap-1.5 text-xs text-[#26a05e]">
                            <div class="w-1.5 h-1.5 rounded-full bg-[#26a05e] animate-pulse"></div>
                            200 OK
                        </div>
                    </div>

                    {{-- JSON output --}}
                    <div class="p-5 font-mono text-xs leading-6 overflow-hidden">
                        <div class="text-[#697d91]">{</div>
                        <div class="pl-4">
                            <span class="text-[#8a9ab0]">"data"</span><span class="text-[#697d91]">: [</span>
                        </div>
                        <div class="pl-8">
                            <span class="text-[#697d91]">{</span>
                        </div>
                        <div class="pl-12 space-y-0.5">
                            <div><span class="text-[#8a9ab0]">"id"</span><span class="text-[#697d91]">: </span><span class="text-[#f97316]">"btc-updown-5m-1774770300"</span><span class="text-[#697d91]">,</span></div>
                            <div><span class="text-[#8a9ab0]">"asset"</span><span class="text-[#697d91]">: </span><span class="text-[#f97316]">"BTC"</span><span class="text-[#697d91]">,</span></div>
                            <div><span class="text-[#8a9ab0]">"break_price_usd"</span><span class="text-[#697d91]">: </span><span class="text-[#26a05e]">84231.50</span><span class="text-[#697d91]">,</span></div>
                            <div><span class="text-[#8a9ab0]">"outcome"</span><span class="text-[#697d91]">: </span><span class="text-[#0093fd]">"YES"</span><span class="text-[#697d91]">,</span></div>
                            <div><span class="text-[#8a9ab0]">"oracle_dist_bp_at_1m"</span><span class="text-[#697d91]">: </span><span class="text-[#26a05e]">142</span><span class="text-[#697d91]">,</span></div>
                            <div><span class="text-[#8a9ab0]">"yes_bid_open"</span><span class="text-[#697d91]">: </span><span class="text-[#26a05e]">0.48</span><span class="text-[#697d91]">,</span></div>
                            <div><span class="text-[#8a9ab0]">"clob_imbalance_open"</span><span class="text-[#697d91]">: </span><span class="text-[#26a05e]">0.062</span></div>
                        </div>
                        <div class="pl-8 text-[#697d91]">},</div>
                        <div class="pl-8 text-[#697d91] opacity-40">{ ... },</div>
                        <div class="pl-8 text-[#697d91] opacity-20">{ ... }</div>
                        <div class="pl-4 text-[#697d91]">],</div>
                        <div class="pl-4"><span class="text-[#8a9ab0]">"meta"</span><span class="text-[#697d91]">: {</span> <span class="text-[#8a9ab0]">"total"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">2734</span><span class="text-[#697d91]">, </span><span class="text-[#8a9ab0]">"page"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">1</span> <span class="text-[#697d91]">}</span></div>
                        <div class="text-[#697d91]">}</div>
                    </div>
                </div>

                {{-- Floating stat chips --}}
                <div class="absolute -bottom-4 -left-4 bg-[#17181c] border border-[#1f2937] rounded-xl px-3.5 py-2.5 shadow-xl">
                    <div class="text-xs text-[#697d91] mb-0.5">Oracle latency</div>
                    <div class="text-sm font-bold text-white">~3s <span class="text-[#26a05e] text-xs font-normal">live</span></div>
                </div>
                <div class="absolute -top-4 -right-4 bg-[#17181c] border border-[#1f2937] rounded-xl px-3.5 py-2.5 shadow-xl">
                    <div class="text-xs text-[#697d91] mb-0.5">Resolved windows</div>
                    <div class="text-sm font-bold text-white">2,734+</div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    STATS BAR
============================================================ --}}
<section class="border-y border-[#1f2937] py-6 bg-[#17181c]/40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-[#1f2937]">
            @foreach([
                ['~3s',        'Oracle tick cadence',      'Chainlink RTDS WebSocket'],
                ['40+',        'Pre-computed ML features',  'Per resolved window'],
                ['BTC/ETH/SOL','Tracked assets',           '5m · 15m windows'],
                ['100%',       'Outcome verified',          'Via Gamma API'],
            ] as [$val, $label, $sub])
            <div class="bg-[#0a0b10] px-8 py-6 text-center">
                <div class="text-2xl font-bold text-white mb-0.5">{{ $val }}</div>
                <div class="text-sm text-[#e5e5e5] mb-0.5">{{ $label }}</div>
                <div class="text-xs text-[#697d91]">{{ $sub }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================
    HOW IT WORKS
============================================================ --}}
<section class="py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-widest mb-3">How it works</div>
            <h2 class="text-3xl font-bold text-white">Up and running in minutes</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
            {{-- Connector line --}}
            <div class="hidden md:block absolute top-8 left-[calc(16.66%+2rem)] right-[calc(16.66%+2rem)] h-px bg-gradient-to-r from-[#0093fd]/30 via-[#0093fd]/60 to-[#0093fd]/30"></div>

            @foreach([
                ['01', 'Create account', 'Sign up free — no credit card needed. You\'ll get an API key instantly.', 'route("register")'],
                ['02', 'Grab your API key', 'Find your key on the dashboard. Use it as a Bearer token in every request.', 'route("docs")'],
                ['03', 'Query the data', 'Call REST endpoints to pull windows, oracle ticks, CLOB snapshots, and candles.', 'route("docs")'],
            ] as [$num, $title, $desc, $href])
            <div class="relative text-center">
                <div class="inline-flex w-14 h-14 rounded-2xl bg-[#0093fd]/10 border border-[#0093fd]/20 items-center justify-center mb-5 relative z-10">
                    <span class="text-lg font-bold text-[#0093fd]">{{ $num }}</span>
                </div>
                <h3 class="text-white font-semibold text-base mb-2">{{ $title }}</h3>
                <p class="text-[#697d91] text-sm leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================
    DATA SOURCES
============================================================ --}}
<section class="border-t border-[#1f2937] py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">

            {{-- Left: section header --}}
            <div class="lg:sticky lg:top-24">
                <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-widest mb-3">Data sources</div>
                <h2 class="text-3xl font-bold text-white mb-4">Every signal that matters</h2>
                <p class="text-[#697d91] text-base leading-relaxed mb-8">
                    We capture data directly from Chainlink's RTDS WebSocket and Polymarket's CLOB WebSocket — the same feeds institutional market makers use.
                </p>
                <div class="space-y-3">
                    @foreach([
                        ['Chainlink RTDS', 'wss://ws-live-data.polymarket.com'],
                        ['Polymarket CLOB', 'wss://ws-subscriptions-clob.polymarket.com'],
                        ['Gamma API', 'https://gamma-api.polymarket.com'],
                    ] as [$name, $url])
                    <div class="flex items-center gap-3 text-sm">
                        <div class="w-1.5 h-1.5 rounded-full bg-[#26a05e] flex-shrink-0"></div>
                        <span class="text-[#e5e5e5] font-medium w-36">{{ $name }}</span>
                        <span class="text-[#697d91] font-mono text-xs truncate">{{ $url }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Right: endpoint cards --}}
            <div class="space-y-4">

                @foreach([
                    [
                        'GET /api/v1/windows',
                        'Market Windows',
                        'Every binary market window — open/close timestamps, break price, outcome, and all pre-computed ML features. The core dataset for training prediction models.',
                        ['break_price_usd', 'outcome', 'oracle_dist_bp_at_1m', 'yes_bid_open', 'clob_imbalance_open', '40+ more features'],
                    ],
                    [
                        'GET /api/v1/oracle-ticks',
                        'Oracle Ticks',
                        'Raw Chainlink price recordings at ~3s cadence. Full price history for BTC, ETH, and SOL with millisecond timestamps.',
                        ['asset', 'price_usd', 'price_bp', 'ts'],
                    ],
                    [
                        'GET /api/v1/clob-snapshots',
                        'CLOB Snapshots',
                        'Full bid-ask order book state captured at every oracle tick. Yes/No spreads, mid prices, and imbalance ratios per market window.',
                        ['yes_bid', 'yes_ask', 'no_bid', 'no_ask', 'window_id', 'ts'],
                    ],
                    [
                        'GET /api/v1/candles',
                        '1m OHLCV Candles',
                        'Standard 1-minute candlestick data aggregated from oracle ticks. Ready to plug into charting libraries or technical analysis pipelines.',
                        ['open_usd', 'high_usd', 'low_usd', 'close_usd', 'volume', 'ts'],
                    ],
                ] as [$route, $title, $desc, $fields])
                <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-6 hover:border-[#0093fd]/25 transition-colors group">
                    <div class="flex items-start justify-between gap-4 mb-3">
                        <div>
                            <div class="font-mono text-xs text-[#0093fd] mb-1.5">{{ $route }}</div>
                            <h3 class="text-white font-semibold">{{ $title }}</h3>
                        </div>
                    </div>
                    <p class="text-[#697d91] text-sm leading-relaxed mb-4">{{ $desc }}</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($fields as $f)
                        <span class="text-xs font-mono bg-[#0a0b10] border border-[#1f2937] text-[#697d91] px-2 py-0.5 rounded-md">{{ $f }}</span>
                        @endforeach
                    </div>
                </div>
                @endforeach

            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    PRICING
============================================================ --}}
<section class="border-t border-[#1f2937] py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-widest mb-3">Pricing</div>
            <h2 class="text-3xl font-bold text-white mb-3">Simple, transparent pricing</h2>
            <p class="text-[#697d91]">Start free. Scale when you need it. Cancel anytime.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">

            {{-- Free --}}
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-7 flex flex-col">
                <div class="mb-6">
                    <div class="text-xs font-semibold text-[#697d91] uppercase tracking-widest mb-3">Free</div>
                    <div class="flex items-end gap-1 mb-1">
                        <span class="text-4xl font-bold text-white">$0</span>
                        <span class="text-[#697d91] text-sm mb-1.5">/mo</span>
                    </div>
                    <p class="text-xs text-[#697d91]">For exploration and prototyping</p>
                </div>
                <ul class="space-y-3 text-sm mb-8 flex-1">
                    @foreach(['100 requests / day', '7 days history', 'All endpoints', 'REST API access'] as $f)
                    <li class="flex items-center gap-2.5 text-[#8a9ab0]">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" class="flex-shrink-0"><path d="M2.5 7.5l3.5 3.5 6.5-7" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center bg-[#0a0b10] hover:bg-[#17181c] border border-[#2e3841] hover:border-[#3d4f5e] text-[#e5e5e5] font-medium px-4 py-2.5 rounded-xl transition-colors text-sm">
                    Get started free
                </a>
            </div>

            {{-- Builder --}}
            <div class="relative bg-gradient-to-b from-[#0093fd]/8 to-[#17181c] border border-[#0093fd]/40 rounded-2xl p-7 flex flex-col shadow-lg shadow-[#0093fd]/5">
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                    <span class="bg-[#0093fd] text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider">Most popular</span>
                </div>
                <div class="mb-6">
                    <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-widest mb-3">Builder</div>
                    <div class="flex items-end gap-1 mb-1">
                        <span class="text-4xl font-bold text-white">$29</span>
                        <span class="text-[#697d91] text-sm mb-1.5">/mo</span>
                    </div>
                    <p class="text-xs text-[#697d91]">For active strategy development</p>
                </div>
                <ul class="space-y-3 text-sm mb-8 flex-1">
                    @foreach(['10,000 requests / day', '90 days history', 'All endpoints', 'REST API access', 'Priority email support'] as $f)
                    <li class="flex items-center gap-2.5 text-[#8a9ab0]">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" class="flex-shrink-0"><path d="M2.5 7.5l3.5 3.5 6.5-7" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center bg-[#0093fd] hover:bg-[#007fd6] text-white font-semibold px-4 py-2.5 rounded-xl transition-colors text-sm shadow-lg shadow-[#0093fd]/20">
                    Start building
                </a>
            </div>

            {{-- Pro --}}
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-7 flex flex-col">
                <div class="mb-6">
                    <div class="text-xs font-semibold text-[#697d91] uppercase tracking-widest mb-3">Pro</div>
                    <div class="flex items-end gap-1 mb-1">
                        <span class="text-4xl font-bold text-white">$99</span>
                        <span class="text-[#697d91] text-sm mb-1.5">/mo</span>
                    </div>
                    <p class="text-xs text-[#697d91]">For production trading systems</p>
                </div>
                <ul class="space-y-3 text-sm mb-8 flex-1">
                    @foreach(['100,000 requests / day', 'Full history (unlimited)', 'All endpoints', 'CSV / SQLite export', 'Backtest endpoint', 'Dedicated support'] as $f)
                    <li class="flex items-center gap-2.5 text-[#8a9ab0]">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" class="flex-shrink-0"><path d="M2.5 7.5l3.5 3.5 6.5-7" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center bg-[#0a0b10] hover:bg-[#17181c] border border-[#2e3841] hover:border-[#3d4f5e] text-[#e5e5e5] font-medium px-4 py-2.5 rounded-xl transition-colors text-sm">
                    Go pro
                </a>
            </div>

        </div>

        {{-- Feature comparison table --}}
        <div class="mt-12 max-w-5xl mx-auto">
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#1f2937]">
                            <th class="text-left px-6 py-4 text-[#697d91] font-medium w-1/2">Feature</th>
                            <th class="text-center px-4 py-4 text-[#697d91] font-medium">Free</th>
                            <th class="text-center px-4 py-4 text-[#0093fd] font-semibold">Builder</th>
                            <th class="text-center px-4 py-4 text-[#697d91] font-medium">Pro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1f2937]">
                        @foreach([
                            ['Daily requests',    '100',       '10,000',    '100,000'],
                            ['History',           '7 days',    '90 days',   'Unlimited'],
                            ['Oracle ticks',      true,        true,        true],
                            ['CLOB snapshots',    true,        true,        true],
                            ['Window features',   true,        true,        true],
                            ['1m candles',        true,        true,        true],
                            ['CSV/SQLite export', false,       false,       true],
                            ['Backtest endpoint', false,       false,       true],
                        ] as [$feature, $free, $builder, $pro])
                        <tr class="hover:bg-[#0a0b10]/50 transition-colors">
                            <td class="px-6 py-3.5 text-[#8a9ab0]">{{ $feature }}</td>
                            @foreach([$free, $builder, $pro] as $val)
                            <td class="text-center px-4 py-3.5">
                                @if($val === true)
                                    <svg class="w-4 h-4 text-[#26a05e] mx-auto" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @elseif($val === false)
                                    <span class="text-[#2e3841]">—</span>
                                @else
                                    <span class="text-[#8a9ab0] font-mono text-xs">{{ $val }}</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
    FAQ
============================================================ --}}
<section class="border-t border-[#1f2937] py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-widest mb-3">FAQ</div>
            <h2 class="text-3xl font-bold text-white">Common questions</h2>
        </div>

        <div class="space-y-3" x-data="{ open: null }">
            @foreach([
                [
                    'What is the data source?',
                    'We connect directly to Chainlink\'s RTDS WebSocket (wss://ws-live-data.polymarket.com) for oracle price feeds and Polymarket\'s CLOB WebSocket for order book data. Market metadata and outcomes come from the Gamma API.',
                ],
                [
                    'How far back does the history go?',
                    'We started recording in early 2026. Free tier gets 7 days, Builder gets 90 days, and Pro gets full access to everything we\'ve recorded. We also have session recordings from before launch that are being backfilled.',
                ],
                [
                    'What are "window features"?',
                    'Each Polymarket binary market (e.g. "BTC above $84k in 5 minutes?") is a "window". We pre-compute 40+ ML-ready features per resolved window — oracle distance at close, TWAP, CLOB imbalance, momentum, volatility, and more.',
                ],
                [
                    'Is this affiliated with Polymarket?',
                    'No. This is an independent third-party data service. We are not affiliated with, endorsed by, or associated with Polymarket or UMA Protocol.',
                ],
                [
                    'Can I cancel anytime?',
                    'Yes. Subscriptions are monthly and you can cancel at any time from your billing dashboard. You\'ll retain access until the end of your billing period.',
                ],
            ] as $i => [$q, $a])
            <div class="bg-[#17181c] border border-[#1f2937] rounded-xl overflow-hidden"
                 x-data="{ open: false }">
                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-[#1e2428] transition-colors">
                    <span class="text-[#e5e5e5] font-medium text-sm">{{ $q }}</span>
                    <svg class="w-4 h-4 text-[#697d91] flex-shrink-0 transition-transform" :class="open && 'rotate-45'" viewBox="0 0 16 16" fill="none">
                        <path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
                <div x-show="open" x-collapse class="px-6 pb-4">
                    <p class="text-[#697d91] text-sm leading-relaxed">{{ $a }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================
    CTA
============================================================ --}}
<section class="border-t border-[#1f2937] py-28 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#0093fd]/8 rounded-full blur-[100px]"></div>
    </div>
    <div class="relative max-w-2xl mx-auto px-4 text-center">
        <h2 class="text-4xl font-bold text-white mb-4 leading-tight">
            Start pulling Polymarket<br>data today
        </h2>
        <p class="text-[#697d91] mb-10 text-base">Free tier. No credit card. API key in under a minute.</p>
        <div class="flex flex-wrap gap-3 justify-center">
            <a href="{{ route('register') }}"
               class="inline-flex items-center gap-2 bg-[#0093fd] hover:bg-[#007fd6] text-white font-semibold px-8 py-3.5 rounded-xl transition-all text-sm shadow-lg shadow-[#0093fd]/20">
                Create free account
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a href="{{ route('docs') }}"
               class="inline-flex items-center gap-2 bg-transparent hover:bg-[#17181c] border border-[#2e3841] text-[#8a9ab0] hover:text-[#e5e5e5] font-medium px-6 py-3.5 rounded-xl transition-all text-sm">
                Read the docs
            </a>
        </div>
        <p class="mt-8 text-xs text-[#2e3841]">
            Not affiliated with Polymarket. Independent third-party service. ·
            <a href="{{ route('terms') }}" class="hover:text-[#697d91] transition-colors ml-1">Terms</a> ·
            <a href="{{ route('privacy') }}" class="hover:text-[#697d91] transition-colors ml-1">Privacy</a>
        </p>
    </div>
</section>

@endsection
