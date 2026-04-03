@extends('layouts.app')

@section('title', 'Roadmap — Polymarket Data API')

@section('head')
<meta name="description" content="The Polymarket Data API roadmap. Live market coverage: crypto (BTC, ETH, SOL) and weather (10 cities). Coming next: Sports, Politics, Economics, and every Polymarket category.">
<link rel="canonical" href="{{ url('/roadmap') }}">
<style>
@keyframes flow-down {
    0%   { top: 0%;   opacity: 0; }
    15%  { opacity: 1; }
    85%  { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}
.flow-packet   { animation: flow-down 1.8s ease-in-out infinite; }
.flow-packet-2 { animation: flow-down 1.8s ease-in-out 0.6s infinite; }

.dot-grid {
    background-image: radial-gradient(circle, #1f2937 1px, transparent 1px);
    background-size: 24px 24px;
}
</style>
@endsection

@section('content')

{{-- ── Hero ─────────────────────────────────────────────────────────────────── --}}
<section class="relative overflow-hidden border-b border-[#1f2937] py-20 sm:py-28"
    x-data="{ vis: false }" x-intersect.once="vis = true">

    <div class="absolute inset-0 dot-grid opacity-[.35] pointer-events-none"></div>
    <div class="absolute left-1/2 top-0 -translate-x-1/2 w-[600px] h-[300px] bg-[#0093fd]/[.05] rounded-full blur-[100px] pointer-events-none"></div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="inline-flex items-center gap-2 bg-[#0093fd]/10 border border-[#0093fd]/20 rounded-full
                    px-4 py-1.5 text-xs font-semibold text-[#0093fd] mb-8
                    transition-all duration-700"
             :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <span class="relative flex h-1.5 w-1.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#0093fd] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-[#0093fd]"></span>
            </span>
            Live now · 2 categories
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white mb-5 leading-tight tracking-tight
                   transition-all duration-700 delay-100"
            :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            Every Polymarket<br>market. <span class="text-[#0093fd]">One API.</span>
        </h1>

        <p class="text-[#697d91] text-lg leading-relaxed max-w-2xl mx-auto mb-10
                  transition-all duration-700 delay-200"
           :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            We started with crypto price markets and daily weather temperature markets.
            We're expanding until every Polymarket category has a clean, real-time data feed
            with ML-ready features.
        </p>

        <div class="flex flex-wrap gap-3 justify-center transition-all duration-700 delay-300"
             :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <a href="{{ route('register') }}"
               class="inline-flex items-center gap-2 bg-[#0093fd] hover:bg-[#007fd6] text-white
                      font-semibold px-6 py-3 rounded-xl transition-all text-sm
                      shadow-[0_0_24px_rgba(0,147,253,.35)]">
                Get started free
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M3 7h8M8 3.5l3.5 3.5L8 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <a href="{{ route('docs') }}"
               class="inline-flex items-center gap-2 border border-[#2e3841] hover:border-[#4a5e6e]
                      text-[#8a9ab0] hover:text-white font-medium px-6 py-3 rounded-xl
                      transition-all text-sm hover:bg-[#17181c]">
                API docs
            </a>
        </div>
    </div>
</section>


{{-- ── Current coverage — LIVE ─────────────────────────────────────────────── --}}
<section class="py-20 border-b border-[#1f2937]"
    x-data="{ vis: false }" x-intersect.once="vis = true">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex items-center gap-3 mb-10 transition-all duration-700"
             :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <div class="flex items-center gap-2">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#26a05e] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-[#26a05e]"></span>
                </span>
                <span class="text-xs font-bold uppercase tracking-[0.15em] text-[#26a05e]">Live now</span>
            </div>
            <div class="h-px flex-1 bg-[#1f2937]"></div>
        </div>

        {{-- Crypto --}}
        <div class="mb-14 transition-all duration-700 delay-100"
             :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-xl bg-[#0093fd]/15 border border-[#0093fd]/25 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#0093fd]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01M12 16v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">Crypto Markets</h2>
                    <p class="text-xs text-[#697d91] mt-0.5">BTC, ETH, SOL Up/Down binary markets · Chainlink oracle prices · millisecond precision</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach([
                    ['BTC', 'Bitcoin', '0x F403...88b', 'eth/btc-usd', '#f7931a'],
                    ['ETH', 'Ethereum', '0x5f4e...419', 'eth/eth-usd', '#627eea'],
                    ['SOL', 'Solana', '0x4ffC...507', 'eth/sol-usd', '#9945ff'],
                ] as [$sym, $name, $addr, $feed, $col])
                <div class="bg-[#17181c] border border-[#0093fd]/20 rounded-2xl p-5 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 rounded-full blur-[50px] pointer-events-none"
                         style="background:{{ $col }}08"></div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs text-white"
                                 style="background:{{ $col }}22; border:1px solid {{ $col }}33">
                                {{ $sym }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-white">{{ $name }}</div>
                                <div class="text-[10px] text-[#697d91] font-mono">{{ $feed }}</div>
                            </div>
                        </div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-[#26a05e] border border-[#26a05e]/20 rounded-full px-2 py-0.5 bg-[#26a05e]/5">Live</div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([
                            ['Oracle feed', 'Chainlink RTDS'],
                            ['Durations', '5m · 15m'],
                            ['Market data', 'CLOB snapshots'],
                            ['Features', '40+ ML features'],
                        ] as [$k, $v])
                        <div class="bg-[#0a0b10] rounded-lg px-2.5 py-2">
                            <div class="text-[9px] text-[#2e3841] uppercase tracking-wider mb-0.5">{{ $k }}</div>
                            <div class="text-[10px] text-[#8a9ab0] font-mono leading-tight">{{ $v }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Weather --}}
        <div class="transition-all duration-700 delay-200"
             :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">

            <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-xl bg-[#26a05e]/15 border border-[#26a05e]/25 flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#26a05e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">Weather Markets</h2>
                    <p class="text-xs text-[#697d91] mt-0.5">Daily highest temperature markets · Open-Meteo · polled every 5 min · °C and °F</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                @php
                $stations = [
                    ['RJTT', 'Tokyo',        'JP', 'Asia/Tokyo',          'celsius',    35.55,  139.78],
                    ['EGLL', 'London',        'GB', 'Europe/London',       'celsius',    51.48,  -0.46],
                    ['LFPG', 'Paris',         'FR', 'Europe/Paris',        'celsius',    49.01,   2.55],
                    ['WSSS', 'Singapore',     'SG', 'Asia/Singapore',      'celsius',     1.36, 103.99],
                    ['RKSI', 'Seoul',         'KR', 'Asia/Seoul',          'celsius',    37.47, 126.45],
                    ['ZBAA', 'Beijing',       'CN', 'Asia/Shanghai',       'celsius',    40.08, 116.59],
                    ['KORD', 'Chicago',       'US', 'America/Chicago',     'fahrenheit', 41.97, -87.91],
                    ['KJFK', 'New York',      'US', 'America/New_York',    'fahrenheit', 40.64, -73.78],
                    ['KLAX', 'Los Angeles',   'US', 'America/Los_Angeles', 'fahrenheit', 33.94,-118.41],
                    ['KMIA', 'Miami',         'US', 'America/New_York',    'fahrenheit', 25.80, -80.29],
                ];
                @endphp
                @foreach($stations as [$icao, $city, $country, $tz, $unit, $lat, $lon])
                <div class="bg-[#17181c] border border-[#26a05e]/15 rounded-xl p-4 hover:border-[#26a05e]/30 transition-colors">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="font-mono text-xs font-bold text-[#26a05e]">{{ $icao }}</span>
                        <span class="text-[9px] text-[#2e3841] font-mono">{{ $country }}</span>
                    </div>
                    <div class="text-sm font-semibold text-white mb-1">{{ $city }}</div>
                    <div class="text-[10px] text-[#697d91] mb-2.5">{{ $tz }}</div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[9px] font-mono px-1.5 py-0.5 rounded"
                              style="background:#26a05e15; color:#26a05e; border:1px solid #26a05e22">
                            {{ $unit === 'fahrenheit' ? '°F' : '°C' }}
                        </span>
                        <span class="text-[9px] text-[#2e3841] font-mono">{{ $lat }}, {{ $lon }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Data sources note --}}
            <div class="mt-6 bg-[#17181c] border border-[#1f2937] rounded-xl p-5 flex flex-col sm:flex-row gap-4 sm:gap-8">
                @foreach([
                    ['Temperature source', 'Open-Meteo API (free, no key) — WMO-standard station data'],
                    ['Poll frequency',     'Every 5 minutes per station — running daily max tracked in memory'],
                    ['Market resolution',  'Sensor-first: compare running max to bracket · Gamma fallback if no data'],
                    ['Units',              'US cities (Chicago, NYC, LA, Miami) → °F · International → °C'],
                ] as [$k, $v])
                <div class="flex-1">
                    <div class="text-[9px] font-bold uppercase tracking-wider text-[#697d91] mb-1">{{ $k }}</div>
                    <div class="text-xs text-[#8a9ab0] leading-relaxed">{{ $v }}</div>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</section>


{{-- ── Coming next ─────────────────────────────────────────────────────────── --}}
<section class="py-20 border-b border-[#1f2937]"
    x-data="{ vis: false }" x-intersect.once="vis = true">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex items-center gap-3 mb-10 transition-all duration-700"
             :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            <span class="text-xs font-bold uppercase tracking-[0.15em] text-[#697d91]">Coming next</span>
            <div class="h-px flex-1 bg-[#1f2937]"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @php
            $upcoming = [
                [
                    'label'  => 'Sports',
                    'color'  => '#f97316',
                    'icon'   => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
                    'desc'   => 'Game results and player prop markets across NFL, NBA, MLB, soccer, tennis, and esports.',
                    'signals'=> ['Match outcome feeds', 'Player stat APIs', 'Live score streams', 'Odds comparison'],
                    'phase'  => 'Planned',
                ],
                [
                    'label'  => 'Politics',
                    'color'  => '#a78bfa',
                    'icon'   => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
                    'desc'   => 'Elections, approval ratings, policy outcomes, and geopolitical events worldwide.',
                    'signals'=> ['Polling data feeds', 'Approval index APIs', 'News sentiment', 'Prediction aggregators'],
                    'phase'  => 'Planned',
                ],
                [
                    'label'  => 'Economics',
                    'color'  => '#fbbf24',
                    'icon'   => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    'desc'   => 'Macro indicators: CPI, Fed rate decisions, unemployment figures, GDP prints, and more.',
                    'signals'=> ['BLS / Fed release feeds', 'Macro indicator APIs', 'Central bank calendars', 'FRED data'],
                    'phase'  => 'Research',
                ],
                [
                    'label'  => 'Pop Culture',
                    'color'  => '#ec4899',
                    'icon'   => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                    'desc'   => 'Award shows, entertainment, viral events, and social prediction markets.',
                    'signals'=> ['Entertainment APIs', 'Social trending feeds', 'Betting market data', 'Award databases'],
                    'phase'  => 'Research',
                ],
            ];
            @endphp

            @foreach($upcoming as $i => $cat)
            <div class="bg-[#17181c]/60 border border-[#1f2937] rounded-2xl p-6 transition-all duration-500 relative overflow-hidden group hover:border-[#2e3841]"
                 :style="vis ? 'opacity:1;transform:translateY(0);transition-delay:{{ $i * 80 }}ms' : 'opacity:0;transform:translateY(20px)'">

                <div class="absolute top-0 right-0 w-32 h-32 rounded-full blur-[60px] pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity"
                     style="background:{{ $cat['color'] }}08"></div>

                <div class="flex items-start justify-between mb-5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         style="background:{{ $cat['color'] }}12; border:1px solid {{ $cat['color'] }}25">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="{{ $cat['color'] }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $cat['icon'] }}"/>
                        </svg>
                    </div>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-[#2e3841] border border-[#1f2937] rounded-full px-2 py-0.5">
                        {{ $cat['phase'] }}
                    </span>
                </div>

                <h3 class="text-base font-bold text-white mb-2">{{ $cat['label'] }}</h3>
                <p class="text-xs text-[#697d91] leading-relaxed mb-5">{{ $cat['desc'] }}</p>

                <div class="space-y-1.5">
                    @foreach($cat['signals'] as $sig)
                    <div class="flex items-center gap-2">
                        <div class="w-1 h-1 rounded-full flex-shrink-0" style="background:{{ $cat['color'] }}; opacity:0.4"></div>
                        <span class="text-[11px] text-[#2e3841]">{{ $sig }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

    </div>
</section>


{{-- ── Architecture / how expansion works ─────────────────────────────────── --}}
<section class="py-20 border-b border-[#1f2937]"
    x-data="{ vis: false }" x-intersect.once="vis = true">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14 transition-all duration-700"
             :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-3">Architecture</div>
            <h2 class="text-3xl font-bold text-white mb-3">Built to expand.</h2>
            <p class="text-[#697d91] max-w-xl mx-auto">Every new category follows the same pattern. A data source feeds a recorder. The recorder feeds the API. You get clean JSON with zero config changes.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @foreach([
                [
                    'step'  => '01',
                    'color' => '#0093fd',
                    'title' => 'Tap the source',
                    'desc'  => 'Each category has a canonical data source — Chainlink RTDS for crypto, Open-Meteo for weather, event APIs for sports. We wire the recorder directly to the source, never a secondary scrape.',
                    'icon'  => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                ],
                [
                    'step'  => '02',
                    'color' => '#26a05e',
                    'title' => 'Run the recorder',
                    'desc'  => 'A long-running ReactPHP process polls or subscribes to the source, writes timestamped rows to TimescaleDB, and updates market state in real time. Designed for sub-second latency.',
                    'icon'  => 'M13 10V3L4 14h7v7l9-11h-7z',
                ],
                [
                    'step'  => '03',
                    'color' => '#a78bfa',
                    'title' => 'Serve the API',
                    'desc'  => 'New category endpoints appear under the same `/api/v1/` base. Same auth, same pagination, same JSON schema. Your existing integration just gains a new query parameter.',
                    'icon'  => 'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                ],
            ] as $i => $step)
            <div class="rounded-2xl border border-[#1f2937] bg-[#17181c] p-6 transition-all duration-500"
                 :style="vis ? 'opacity:1;transform:translateY(0);transition-delay:{{ $i * 100 }}ms' : 'opacity:0;transform:translateY(20px)'">

                <div class="flex items-center justify-between mb-5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                         style="background:{{ $step['color'] }}15; border:1px solid {{ $step['color'] }}30">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="{{ $step['color'] }}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $step['icon'] }}"/>
                        </svg>
                    </div>
                    <span class="font-mono text-xs font-bold" style="color:{{ $step['color'] }}">{{ $step['step'] }}</span>
                </div>

                <h3 class="text-sm font-bold text-white mb-2">{{ $step['title'] }}</h3>
                <p class="text-xs text-[#697d91] leading-relaxed">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>

    </div>
</section>


{{-- ── CTA ──────────────────────────────────────────────────────────────────── --}}
<section class="py-24 relative overflow-hidden">

    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#0093fd]/[.06] rounded-full blur-[100px]"></div>
    </div>
    <div class="absolute inset-0 dot-grid opacity-[.3] pointer-events-none"></div>

    <div class="relative max-w-2xl mx-auto px-4 text-center">

        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4 leading-tight">
            Start with what's live.
        </h2>
        <p class="text-[#8a9ab0] text-base mb-10 leading-relaxed">
            Crypto and weather markets are live today. Free tier, no credit card. Every new category ships to the same endpoint you're already using.
        </p>

        <div class="flex flex-wrap gap-4 justify-center">
            <a href="{{ route('register') }}"
               class="inline-flex items-center gap-2.5 bg-[#0093fd] hover:bg-[#007fd6] text-white
                      font-semibold px-8 py-4 rounded-xl transition-all text-sm
                      shadow-[0_0_32px_rgba(0,147,253,.4)] hover:shadow-[0_0_48px_rgba(0,147,253,.55)]">
                Create free account
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                    <path d="M3 7h8M8 3.5l3.5 3.5L8 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <a href="{{ route('docs') }}"
               class="inline-flex items-center gap-2 border border-[#2e3841] hover:border-[#4a5e6e]
                      text-[#8a9ab0] hover:text-[#e5e5e5] font-medium px-6 py-4 rounded-xl
                      transition-all text-sm hover:bg-[#17181c]">
                Read the docs
            </a>
        </div>

        <p class="text-xs text-[#2e3841] mt-8">
            Not affiliated with Polymarket. Independent third-party data service. ·
            <a href="{{ route('terms') }}" class="hover:text-[#697d91] transition-colors ml-1">Terms</a> ·
            <a href="{{ route('privacy') }}" class="hover:text-[#697d91] transition-colors ml-1">Privacy</a>
        </p>
    </div>
</section>

@endsection
