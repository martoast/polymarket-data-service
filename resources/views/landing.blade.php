@extends('layouts.app')

@section('title', 'Polymarket Data API — Oracle Ticks, CLOB Snapshots & ML Features for Algo Traders')

@section('head')
<meta name="description" content="REST API for Polymarket historical data. Chainlink oracle ticks, CLOB order book snapshots, 1-minute OHLCV candles, and 40+ pre-computed ML features per market window. Free tier — API key in 60 seconds.">
<meta name="keywords" content="Polymarket API, Polymarket data, Polymarket historical data, prediction market data API, Polymarket CLOB data, Polymarket order book API, Polymarket backtesting, Polymarket oracle data, Polymarket trading bot, Polymarket 1-minute candles, prediction market ML features, Polymarket WebSocket feed, Polymarket Chainlink oracle tick data">
<link rel="canonical" href="{{ url('/') }}">

{{-- Open Graph --}}
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url('/') }}">
<meta property="og:title" content="Polymarket Data API — Historical CLOB, Oracle Ticks & ML Features">
<meta property="og:description" content="Chainlink oracle ticks, full CLOB order book snapshots, and 40+ pre-computed ML features for Polymarket algo traders. Free tier available.">
<meta property="og:site_name" content="Polymarket Data API">
<meta property="og:image" content="{{ url('/og-image.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Polymarket Data API — Historical CLOB, Oracle Ticks & ML Features">
<meta name="twitter:description" content="Chainlink oracle ticks, full CLOB order book snapshots, and 40+ pre-computed ML features for Polymarket algo traders. Free tier available.">
<meta name="twitter:image" content="{{ url('/og-image.png') }}">

<style>
/* ── Dot-grid background ── */
.dot-grid {
    background-image: radial-gradient(circle, #1f2937 1px, transparent 1px);
    background-size: 28px 28px;
}

/* ── Animated glow pulse ── */
@keyframes glow-pulse {
    0%, 100% { opacity: .55; transform: scale(1); }
    50%       { opacity: .8;  transform: scale(1.06); }
}
.glow-anim { animation: glow-pulse 8s ease-in-out infinite; }

/* ── Marquee ── */
@keyframes marquee {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}
.marquee-inner { animation: marquee 28s linear infinite; }
.marquee-inner:hover { animation-play-state: paused; }

/* ── Counter number ── */
.counter-num { font-variant-numeric: tabular-nums; }

/* ── Code window ── */
.code-line { line-height: 1.75; }

/* ── Gradient border for Builder card ── */
.builder-card {
    background: linear-gradient(#17181c, #17181c) padding-box,
                linear-gradient(135deg, #0093fd 0%, #00c4fd 100%) border-box;
    border: 1px solid transparent;
}

/* ── Feature tab underline ── */
.tab-active { border-bottom: 2px solid #0093fd; color: #e5e5e5; }
.tab-inactive { border-bottom: 2px solid transparent; color: #697d91; }

/* ── Smooth appear ── */
@keyframes fade-up {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-up { animation: fade-up .6s ease both; }

/* ── Typewriter ── */
.tw-line { overflow: hidden; white-space: nowrap; width: 0; display: block; }
.bento-vis .tw-l1 { animation: tw-type 0.25s steps(8, end) 0.7s both; }
.bento-vis .tw-l2 { animation: tw-type 0.95s steps(43, end) 1.05s both; }
.bento-vis .tw-l3 { animation: tw-type 1.2s steps(57, end) 2.1s both; }
.tw-j { opacity: 0; }
.bento-vis .tw-j1 { animation: tw-fade 0.3s ease 3.5s both; }
.bento-vis .tw-j2 { animation: tw-fade 0.3s ease 3.65s both; }
.bento-vis .tw-j3 { animation: tw-fade 0.3s ease 3.8s both; }
.bento-vis .tw-j4 { animation: tw-fade 0.3s ease 3.95s both; }
.bento-vis .tw-j5 { animation: tw-fade 0.3s ease 4.1s both; }
.bento-vis .tw-j6 { animation: tw-fade 0.3s ease 4.25s both; }
@keyframes ping { 0%{transform:scale(1);opacity:.6} 100%{transform:scale(2.8);opacity:0} }
@keyframes tw-type { to { width: 100%; } }
@keyframes tw-fade { to { opacity: 1; } }
@keyframes cursor-blink { 0%,49%{opacity:1}50%,100%{opacity:0} }
.cursor-blink { animation: cursor-blink 1s step-end infinite; }

/* ── Bento card entrance ── */
.bento-card {
    opacity: 0;
    transform: translateY(28px);
    transition: opacity .65s ease, transform .65s ease;
}
.bento-vis .bento-card { opacity: 1; transform: translateY(0); }
.bento-vis .bento-card:nth-child(1) { transition-delay: 0ms; }
.bento-vis .bento-card:nth-child(2) { transition-delay: 120ms; }
.bento-vis .bento-card:nth-child(3) { transition-delay: 220ms; }
.bento-vis .bento-card:nth-child(4) { transition-delay: 340ms; }

/* ── Sparkline draw ── */
.sparkline-path {
    stroke-dasharray: 420;
    stroke-dashoffset: 420;
    transition: stroke-dashoffset 1.8s ease-out 0.5s;
}
.spark-vis .sparkline-path { stroke-dashoffset: 0; }

/* ── CLOB bar fill ── */
.clob-bar { width: 0 !important; transition: width 1s ease-out; }
.clob-vis .clob-bar-1 { width: 78% !important; transition-delay: 0.5s; }
.clob-vis .clob-bar-2 { width: 64% !important; transition-delay: 0.65s; }
.clob-vis .clob-bar-3 { width: 70% !important; transition-delay: 0.8s; }
.clob-vis .clob-bar-4 { width: 85% !important; transition-delay: 0.95s; }

/* ── Wave animation ── */
@keyframes wave-scroll {
    from { transform: translateX(0); }
    to   { transform: translateX(-50%); }
}
.wave-1 { animation: wave-scroll 22s linear infinite; }
.wave-2 { animation: wave-scroll 32s linear infinite reverse; }
.wave-3 { animation: wave-scroll 16s linear infinite; }
</style>
@endsection

@section('content')

{{-- ============================================================
     §1  HERO
============================================================ --}}
<section class="relative overflow-hidden dot-grid min-h-screen flex items-center">

    {{-- Ambient glows --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="glow-anim absolute top-[-200px] left-1/2 -translate-x-1/2 w-[1000px] h-[600px] rounded-full bg-[#0093fd]/[.06] blur-[140px]"></div>
        <div class="absolute top-[10%] left-[5%] w-[400px] h-[400px] rounded-full bg-[#0093fd]/[.03] blur-[100px]"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-[#0a0b10]"></div>
    </div>

    {{-- Animated wave lines — centered vertically, cuts through hero --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden flex items-center" aria-hidden="true">
        <div class="wave-1 absolute" style="width:200%; left:0; top:50%; margin-top:-60px;">
            <svg viewBox="0 0 2880 120" preserveAspectRatio="none" style="width:100%; height:120px; display:block;">
                <path d="M0,60 C180,20 360,100 540,60 S900,20 1080,60 S1440,100 1620,60 S1980,20 2160,60 S2520,100 2700,60 S2880,20 2880,60"
                      fill="none" stroke="#0093fd" stroke-width="1.5" opacity="0.18"/>
            </svg>
        </div>
        <div class="wave-2 absolute" style="width:200%; left:0; top:50%; margin-top:-40px;">
            <svg viewBox="0 0 2880 120" preserveAspectRatio="none" style="width:100%; height:120px; display:block;">
                <path d="M0,80 C240,40 480,110 720,70 S1080,30 1320,70 S1680,110 1920,70 S2280,30 2520,70 S2760,110 2880,80"
                      fill="none" stroke="#0093fd" stroke-width="1" opacity="0.10"/>
            </svg>
        </div>
        <div class="wave-3 absolute" style="width:200%; left:0; top:50%; margin-top:-20px;">
            <svg viewBox="0 0 2880 120" preserveAspectRatio="none" style="width:100%; height:120px; display:block;">
                <path d="M0,45 C120,85 360,15 600,55 S960,95 1200,55 S1560,15 1800,55 S2160,95 2400,55 S2700,15 2880,45"
                      fill="none" stroke="#0093fd" stroke-width="0.75" opacity="0.07"/>
            </svg>
        </div>
    </div>

    <div class="relative w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-0 lg:min-h-screen lg:flex lg:items-center">
        <div class="grid grid-cols-1 lg:grid-cols-[52%_48%] gap-10 lg:gap-16 items-center w-full">

            {{-- ── Left: copy ── --}}
            <div class="fade-up">

                {{-- Live badge --}}
                <div class="inline-flex items-center gap-2 bg-[#0093fd]/[.08] border border-[#0093fd]/25 rounded-full px-3.5 py-1.5 mb-8">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#0093fd] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#0093fd]"></span>
                    </span>
                    <span class="text-[#0093fd] text-xs font-semibold tracking-wide">Crypto Up/Down &nbsp;·&nbsp; Production-ready API</span>
                </div>

                {{-- Headline --}}
                <h1 class="font-extrabold leading-[1.04] tracking-[-0.03em] text-white mb-6"
                    style="font-size: clamp(2.6rem, 6vw, 4.2rem);">
                    The data layer<br>
                    every Polymarket<br>
                    <span class="text-[#0093fd]">bot needs.</span>
                </h1>

                {{-- Sub --}}
                <p class="text-[#8a9ab0] text-lg leading-relaxed mb-10 max-w-[460px]">
                    Oracle prices, order book depth, and 40+ ML-ready features — live captured and served via REST. Free to start, no card.
                </p>

                {{-- CTAs --}}
                <div class="flex flex-wrap gap-3 mb-8">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-2 bg-[#0093fd] hover:bg-[#007fd6]
                              text-white font-bold px-7 py-3.5 rounded-xl transition-all duration-150 text-sm
                              shadow-[0_0_28px_rgba(0,147,253,.4)] hover:shadow-[0_0_40px_rgba(0,147,253,.55)]">
                        Get API Key Free
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M2.5 7h9M8 3.5l3.5 3.5L8 10.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <a href="{{ route('docs') }}"
                       class="inline-flex items-center gap-2 border border-[#2e3841] hover:border-[#4a5e6e]
                              text-[#8a9ab0] hover:text-[#e5e5e5] font-medium px-6 py-3.5 rounded-xl
                              transition-all duration-150 text-sm hover:bg-[#17181c]">
                        Read the Docs
                    </a>
                </div>

                {{-- Trust chips --}}
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-[#697d91]">
                    @foreach(['No credit card required', '5 min integration', 'Any language'] as $chip)
                    <span class="flex items-center gap-1.5">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <circle cx="6" cy="6" r="5" stroke="#26a05e" stroke-width="1"/>
                            <path d="M3.5 6l2 2 3-3" stroke="#26a05e" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $chip }}
                    </span>
                    @endforeach
                </div>
            </div>

            {{-- ── Right: product data card ── --}}
            <div class="hidden lg:block fade-up relative" style="animation-delay:.15s">

                {{-- Glow behind card --}}
                <div class="absolute -inset-8 bg-[#0093fd]/[.05] rounded-3xl blur-3xl pointer-events-none"></div>

                {{-- Card --}}
                <div class="relative rounded-2xl border border-[#1f2937] bg-[#0d0e13] shadow-[0_40px_100px_rgba(0,0,0,.7)] overflow-hidden">

                    {{-- Card header --}}
                    <div class="flex items-center justify-between px-5 py-4 bg-[#17181c] border-b border-[#1f2937]">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#f7931a]/15 border border-[#f7931a]/20 flex items-center justify-center flex-shrink-0">
                                <span class="text-[#f7931a] font-bold text-sm">₿</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-white text-sm font-bold">BTC Up/Down</span>
                                    <span class="text-[10px] font-semibold text-[#697d91] bg-[#1e2428] border border-[#2e3841] px-1.5 py-0.5 rounded">5m</span>
                                </div>
                                <div class="text-[10px] text-[#697d91] font-mono mt-0.5">01:30:00 — 01:35:00 UTC</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold text-[#26a05e]">
                            <span class="w-1.5 h-1.5 rounded-full bg-[#26a05e] animate-pulse inline-block"></span>
                            LIVE
                        </div>
                    </div>

                    {{-- Oracle reference price --}}
                    <div class="flex items-center justify-between px-5 py-3 border-b border-[#1f2937]">
                        <span class="text-[10px] font-semibold text-[#697d91] uppercase tracking-widest">Oracle Reference</span>
                        <div class="text-right">
                            <span class="text-white font-bold text-sm font-mono">$84,231.50</span>
                            <span class="text-[#26a05e] text-xs font-semibold ml-2">+0.42%</span>
                        </div>
                    </div>

                    {{-- UP / DOWN sparklines --}}
                    <div class="grid grid-cols-2 border-b border-[#1f2937]">

                        {{-- YES --}}
                        <div class="p-4 border-r border-[#1f2937]">
                            <div class="flex items-center gap-1.5 mb-2">
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 8l3-5 3 3" stroke="#26a05e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span class="text-[10px] font-bold text-[#26a05e] uppercase tracking-wider">YES</span>
                                <span class="ml-auto text-[#26a05e] font-bold text-lg font-mono">0.58</span>
                            </div>
                            <svg viewBox="0 0 120 44" class="w-full" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="yFill" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#26a05e" stop-opacity="0.25"/>
                                        <stop offset="100%" stop-color="#26a05e" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                                <path d="M0,38 L14,34 L28,30 L40,26 L54,20 L66,22 L78,15 L92,11 L106,8 L120,6 L120,44 L0,44 Z" fill="url(#yFill)"/>
                                <path d="M0,38 L14,34 L28,30 L40,26 L54,20 L66,22 L78,15 L92,11 L106,8 L120,6" fill="none" stroke="#26a05e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="flex justify-between mt-1">
                                <span class="text-[9px] text-[#697d91]">H: 0.61</span>
                                <span class="text-[9px] text-[#697d91]">L: 0.44</span>
                            </div>
                        </div>

                        {{-- NO --}}
                        <div class="p-4">
                            <div class="flex items-center gap-1.5 mb-2">
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 2l3 5 3-3" stroke="#ff6b6b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span class="text-[10px] font-bold text-[#ff6b6b] uppercase tracking-wider">NO</span>
                                <span class="ml-auto text-[#ff6b6b] font-bold text-lg font-mono">0.42</span>
                            </div>
                            <svg viewBox="0 0 120 44" class="w-full" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="nFill" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#ff6b6b" stop-opacity="0.2"/>
                                        <stop offset="100%" stop-color="#ff6b6b" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                                <path d="M0,8 L14,12 L28,16 L40,20 L54,26 L66,24 L78,30 L92,34 L106,36 L120,38 L120,44 L0,44 Z" fill="url(#nFill)"/>
                                <path d="M0,8 L14,12 L28,16 L40,20 L54,26 L66,24 L78,30 L92,34 L106,36 L120,38" fill="none" stroke="#ff6b6b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="flex justify-between mt-1">
                                <span class="text-[9px] text-[#697d91]">H: 0.56</span>
                                <span class="text-[9px] text-[#697d91]">L: 0.39</span>
                            </div>
                        </div>
                    </div>

                    {{-- Order book --}}
                    <div class="px-5 py-3 border-b border-[#1f2937]">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[10px] font-bold text-[#697d91] uppercase tracking-widest">Order Book — YES Token</span>
                            <span class="text-[10px] font-semibold text-[#0093fd]">0.01 spread</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-[10px] font-mono">
                            <div>
                                <div class="text-[#26a05e] font-bold mb-1.5 uppercase tracking-wider">Bids</div>
                                @foreach([[2450,'0.57',85],[3800,'0.56',70],[1900,'0.55',55]] as [$size,$price,$w])
                                <div class="flex items-center gap-2 mb-1.5 relative">
                                    <div class="absolute inset-y-0 left-0 rounded-sm" style="width:{{ $w }}%; background:#26a05e18;"></div>
                                    <span class="relative text-[#697d91] w-10">{{ number_format($size) }}</span>
                                    <span class="relative text-[#26a05e] ml-auto">{{ $price }}</span>
                                </div>
                                @endforeach
                            </div>
                            <div>
                                <div class="text-[#ff6b6b] font-bold mb-1.5 uppercase tracking-wider text-right">Asks</div>
                                @foreach([[1800,'0.58',80],[2900,'0.59',68],[3100,'0.60',52]] as [$size,$price,$w])
                                <div class="flex items-center gap-2 mb-1.5 relative">
                                    <div class="absolute inset-y-0 right-0 rounded-sm" style="width:{{ $w }}%; background:#ff6b6b18;"></div>
                                    <span class="relative text-[#ff6b6b]">{{ $price }}</span>
                                    <span class="relative text-[#697d91] w-10 text-right ml-auto">{{ number_format($size) }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Footer stats --}}
                    <div class="flex items-center justify-between px-5 py-3 text-[10px] text-[#697d91]">
                        <span class="flex items-center gap-1.5">
                            <svg width="9" height="9" viewBox="0 0 9 9" fill="none"><circle cx="4.5" cy="4.5" r="4" stroke="#0093fd" stroke-width="0.8"/><path d="M4.5 2.5v2l1.2 1" stroke="#0093fd" stroke-width="0.8" stroke-linecap="round"/></svg>
                            Oracle ticks <span class="text-[#e5e5e5] font-semibold ml-0.5">188K+</span>
                        </span>
                        <span>⚡ Features <span class="text-[#e5e5e5] font-semibold">40+</span></span>
                        <span class="flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-[#26a05e] animate-pulse inline-block"></span>
                            Real-time
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>


{{-- ============================================================
     §2  LIVE STATS BAR (animated counters)
============================================================ --}}
<section class="border-y border-[#1f2937] hidden lg:block">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-2 lg:grid-cols-4">
            @foreach([
                [
                    'icon' => '<path d="M8 2v4l3 2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.4"/>',
                    'label' => 'Millisecond Oracle Ticks',
                    'sub'   => 'BTC · ETH · SOL · every Chainlink update',
                ],
                [
                    'icon' => '<path d="M2 10l3-5 3 3 2-4 3 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>',
                    'label' => '40+ ML Features',
                    'sub'   => 'Pre-computed per market window',
                ],
                [
                    'icon' => '<rect x="2" y="4" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.4"/><path d="M5 8h6M5 11h3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>',
                    'label' => 'Full CLOB Order Book',
                    'sub'   => 'Yes/No depth at every oracle tick',
                ],
                [
                    'icon' => '<path d="M2 8h2.5M9.5 8H12M8 2v2.5M8 9.5V12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><circle cx="8" cy="8" r="2.5" stroke="currentColor" stroke-width="1.4"/><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.2" stroke-dasharray="2 2"/>',
                    'label' => 'Simple REST API',
                    'sub'   => 'Any language · API key in 60s',
                ],
            ] as $i => $item)
            <div class="flex items-center gap-4 px-8 py-7
                        border-r border-[#1f2937] last:border-r-0
                        border-b lg:border-b-0
                        {{ $i === 1 ? 'border-r-0 lg:border-r' : '' }}">
                <div class="w-9 h-9 rounded-xl bg-[#0093fd]/10 border border-[#0093fd]/15 flex items-center justify-center flex-shrink-0 text-[#0093fd]">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">{!! $item['icon'] !!}</svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-white leading-snug">{{ $item['label'] }}</div>
                    <div class="text-xs text-[#697d91] mt-0.5">{{ $item['sub'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>


{{-- ============================================================
     §3  VALUE PROPOSITION — BENTO GRID
============================================================ --}}
<section class="py-32 relative overflow-hidden"
    x-data="{ vis: false, spark: false, clob: false }"
    x-intersect.once="vis = true; setTimeout(() => spark = true, 300); setTimeout(() => clob = true, 400)">

    {{-- Ambient glow --}}
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[900px] h-[500px] bg-[#0093fd]/[.03] rounded-full blur-[130px]"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Headline --}}
        <div class="text-center mb-12">
            <h2 class="text-4xl sm:text-5xl font-extrabold text-white leading-[1.1] tracking-tight">
                Your bot needs months of data.
            </h2>
            <p class="text-4xl sm:text-5xl font-extrabold leading-[1.1] tracking-tight mt-1.5"
               style="background: linear-gradient(90deg, #0093fd 0%, #60b8ff 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                We got you covered.
            </p>
        </div>

        {{-- Bento grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4" :class="vis ? 'bento-vis' : ''">

            {{-- ── Card 1: Code preview (col-span-2) ─────────────────────── --}}
            <div class="bento-card md:col-span-2 relative rounded-2xl border border-[#1f2937] bg-[#17181c] overflow-hidden hover:border-[#0093fd]/25 transition-colors duration-300 flex flex-col">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-[#0093fd]/50 to-transparent"></div>

                {{-- Code block --}}
                <div class="p-6 flex-1">
                    <div class="bg-[#0d0e13] rounded-xl border border-[#1f2937] overflow-hidden h-full">
                        {{-- Mac window chrome --}}
                        <div class="flex items-center gap-3 px-4 py-3 border-b border-[#1f2937]">
                            <div class="flex gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-full bg-[#ff5f57]"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-[#febc2e]"></div>
                                <div class="w-2.5 h-2.5 rounded-full bg-[#28c840]"></div>
                            </div>
                            <span class="text-[11px] text-[#697d91] font-mono">~/strategy</span>
                        </div>
                        {{-- Shell with typewriter --}}
                        <div class="p-5 font-mono text-xs leading-7">
                            <span class="tw-line tw-l1 text-[#697d91]">$ curl \</span>
                            <span class="tw-line tw-l2 pl-4 text-[#697d91]">-H <span class="text-[#26a05e]">"Authorization: Bearer sk-••••••••"</span> \</span>
                            <span class="tw-line tw-l3 pl-4 text-[#0093fd]">"https://api.polymarketdata.io/v1/oracle-ticks?asset=BTC"<span class="cursor-blink text-[#697d91]">█</span></span>
                            <div class="mt-3 space-y-0.5">
                                <div class="tw-j tw-j1 text-[#697d91]">{</div>
                                <div class="tw-j tw-j2 pl-4 text-[#697d91]">"data": [{</div>
                                <div class="tw-j tw-j3 pl-8"><span class="text-[#8a9ab0]">"asset"</span><span class="text-[#697d91]">: </span><span class="text-[#f97316]">"BTC"</span><span class="text-[#697d91]">,</span></div>
                                <div class="tw-j tw-j4 pl-8"><span class="text-[#8a9ab0]">"price_usd"</span><span class="text-[#697d91]">: </span><span class="text-[#26a05e]">84231.50</span><span class="text-[#697d91]">,</span></div>
                                <div class="tw-j tw-j5 pl-8"><span class="text-[#8a9ab0]">"features"</span><span class="text-[#697d91]">: { </span><span class="text-[#8a9ab0]">twap_1m</span><span class="text-[#697d91]">: </span><span class="text-[#26a05e]">84198.22</span><span class="text-[#697d91]">, ... }</span></div>
                                <div class="tw-j tw-j6 pl-4 text-[#697d91]">}]}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card footer --}}
                <div class="px-6 pb-6 flex items-center justify-between">
                    <div>
                        <div class="text-base font-bold text-white">API key in 60 seconds</div>
                        <div class="text-sm text-[#697d91] mt-0.5">Any language. No setup required. Free to start.</div>
                    </div>
                    <a href="{{ route('register') }}"
                       class="flex-shrink-0 ml-6 inline-flex items-center gap-1.5 text-xs font-semibold text-[#0093fd] hover:text-white border border-[#0093fd]/30 hover:border-[#0093fd] rounded-lg px-3 py-1.5 transition-colors">
                        Get started
                        <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none">
                            <path d="M2.5 6h7M6.5 3l3 3-3 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- ── Card 2: Oracle ticks — live price chart ─────────────── --}}
            <div class="bento-card relative rounded-2xl border border-[#1f2937] bg-[#17181c] overflow-hidden hover:border-[#0093fd]/25 transition-colors duration-300 flex flex-col"
                 :class="spark ? 'spark-vis' : ''"
                 x-data="{
                     asset: 0,
                     assets: [
                         { name:'BTC', price:'$84,231', chg:'+0.42%', col:'#0093fd', ey:3,  path:'M0 68 C6 62 10 74 16 66 S22 50 28 54 S34 42 40 46 S46 34 52 37 S58 50 64 43 S70 30 76 33 S82 44 88 36 S94 22 100 26 S106 38 112 30 S118 18 124 21 S130 30 136 22 S142 10 148 14 S154 22 160 15 S166 6 172 8 S176 4 180 3' },
                         { name:'ETH', price:'$3,182',  chg:'+1.1%',  col:'#818cf8', ey:14, path:'M0 72 C6 68 12 60 18 63 S24 74 30 67 S36 55 42 58 S48 66 54 59 S60 46 66 50 S72 60 78 52 S84 40 90 44 S96 54 102 46 S108 34 114 38 S120 48 126 40 S132 28 138 32 S144 42 150 34 S156 20 162 24 S168 32 174 26 S178 18 180 14' },
                         { name:'SOL', price:'$142.80', chg:'+2.3%',  col:'#34d399', ey:8,  path:'M0 75 C8 70 14 78 20 71 S26 58 32 62 S38 72 44 64 S50 52 56 56 S62 66 68 57 S74 44 80 48 S86 58 92 49 S98 36 104 40 S110 52 116 43 S122 30 128 34 S134 44 140 35 S146 22 152 27 S158 36 164 28 S170 14 176 18 S179 10 180 8' },
                     ]
                 }"
                 x-init="setInterval(() => asset = (asset+1)%3, 3000)">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-[#0093fd]/30 to-transparent"></div>

                {{-- Asset tabs --}}
                <div class="flex gap-1 px-4 pt-4">
                    <template x-for="(a,i) in assets" :key="i">
                        <button @click="asset=i"
                            class="text-[10px] font-bold px-2.5 py-1 rounded-md transition-all"
                            :style="asset===i ? 'background:'+a.col+'22; color:'+a.col+'; border:1px solid '+a.col+'44' : ''"
                            :class="asset===i ? '' : 'text-[#2e3841] border border-transparent hover:text-[#697d91]'"
                            x-text="a.name"></button>
                    </template>
                </div>

                {{-- Price display --}}
                <div class="px-4 pt-3 pb-1 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-white font-mono" x-text="assets[asset].price"></span>
                    <span class="text-sm font-semibold text-[#26a05e]" x-text="assets[asset].chg"></span>
                </div>

                {{-- Chart --}}
                <div class="flex-1 px-3 pb-2" style="min-height:110px">
                    <svg viewBox="0 0 180 90" class="w-full h-full" preserveAspectRatio="none" style="display:block;min-height:110px">
                        <line x1="0" y1="30" x2="180" y2="30" stroke="#1f2937" stroke-width="0.5"/>
                        <line x1="0" y1="60" x2="180" y2="60" stroke="#1f2937" stroke-width="0.5"/>
                        {{-- Fill area --}}
                        <path :d="assets[asset].path + ' L180 90 L0 90 Z'"
                              :fill="assets[asset].col" opacity="0.10"/>
                        {{-- Line --}}
                        <path :d="assets[asset].path"
                              fill="none"
                              :stroke="assets[asset].col"
                              stroke-width="2"
                              stroke-linecap="round"
                              class="sparkline-path"/>
                        {{-- Live dot --}}
                        <circle cx="180" :cy="assets[asset].ey" r="3.5" :fill="assets[asset].col"/>
                        <circle cx="180" :cy="assets[asset].ey" r="7" :fill="assets[asset].col" opacity="0.2" style="animation: ping 1.5s ease-out infinite"/>
                    </svg>
                </div>

                <div class="px-4 pb-5">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#0093fd] animate-pulse inline-block"></span>
                        <span class="text-[10px] font-bold text-[#0093fd] uppercase tracking-widest">Live oracle feed</span>
                    </div>
                    <div class="text-sm text-[#697d91]">Every Chainlink tick · Millisecond timestamps</div>
                </div>
            </div>

            {{-- ── Card 3: ML feature spotlight ─────────────────────────── --}}
            <div class="bento-card relative rounded-2xl border border-[#1f2937] bg-[#17181c] overflow-hidden hover:border-[#0093fd]/25 transition-colors duration-300"
                 x-data="{
                     hi: 0,
                     tags: [
                         {n:'twap_1m',    v:'84198.22'},
                         {n:'vol_3m',     v:'312.45'},
                         {n:'momentum',   v:'0.0041'},
                         {n:'clob_imbal', v:'0.62'},
                         {n:'oracle_dist',v:'33.1 bp'},
                         {n:'yes_bid',    v:'0.58'},
                         {n:'spread',     v:'0.018'},
                         {n:'velocity',   v:'+0.0012'},
                         {n:'twap_5m',    v:'84177.80'},
                         {n:'vol_1m',     v:'89.3'},
                         {n:'yes_ask',    v:'0.60'},
                         {n:'market_age', v:'183s'},
                     ]
                 }"
                 x-init="setInterval(() => hi = (hi+1) % 12, 950)">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-[#0093fd]/30 to-transparent"></div>

                <div class="p-5 flex flex-col h-full">
                    {{-- Tag grid --}}
                    <div class="flex flex-wrap gap-1.5 mb-4 flex-1">
                        <template x-for="(t,i) in tags" :key="i">
                            <span class="text-[10px] font-mono px-2 py-1 rounded-md border transition-all duration-300 cursor-default"
                                  :class="hi===i
                                      ? 'border-[#0093fd]/60 text-[#0093fd] bg-[#0093fd]/10 shadow-[0_0_12px_#0093fd33]'
                                      : 'border-[#1f2937] text-[#697d91] bg-[#0a0b10]'"
                                  x-text="t.n"></span>
                        </template>
                        <span class="text-[10px] font-mono bg-[#0093fd]/5 border border-[#0093fd]/20 text-[#0093fd] px-2 py-1 rounded-md">+ 30 more</span>
                    </div>

                    {{-- Live computed value --}}
                    <div class="bg-[#0d0e13] border border-[#1f2937] rounded-xl px-3 py-2.5 flex items-center justify-between mb-4">
                        <span class="text-[10px] text-[#697d91] font-mono" x-text="tags[hi].n + ':'"></span>
                        <span class="text-sm font-bold font-mono text-[#0093fd]" x-text="tags[hi].v"></span>
                    </div>

                    <div class="text-base font-bold text-white">40+ ML features</div>
                    <div class="text-sm text-[#697d91] mt-0.5">Pre-computed per window. Ready to train.</div>
                </div>
            </div>

            {{-- ── Card 4: CLOB order book — live animated ──────────────── --}}
            <div class="bento-card md:col-span-2 relative rounded-2xl border border-[#1f2937] bg-[#17181c] overflow-hidden hover:border-[#26a05e]/20 transition-colors duration-300"
                 x-data="{
                     flash: -1,
                     rows: [
                         { s:'Yes Ask', pct:74, p:0.52, vol:3800, c:'#ff4d4d' },
                         { s:'Yes Bid', pct:61, p:0.50, vol:2900, c:'#26a05e' },
                         { s:'No Bid',  pct:68, p:0.49, vol:1900, c:'#26a05e' },
                         { s:'No Ask',  pct:82, p:0.51, vol:3100, c:'#ff4d4d' },
                     ]
                 }"
                 x-init="setInterval(() => {
                     let i = Math.floor(Math.random()*4);
                     rows[i] = {...rows[i],
                         pct: Math.round(Math.max(12, Math.min(94, rows[i].pct + (Math.random()*14-7)))),
                         vol: Math.round(Math.max(500, rows[i].vol + (Math.random()*400-200)))
                     };
                     flash = i;
                     setTimeout(() => flash = -1, 350);
                 }, 1300)">
                <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-[#26a05e]/30 to-transparent"></div>

                <div class="p-6 flex flex-col sm:flex-row gap-8 items-center">

                    {{-- Live order book --}}
                    <div class="w-full sm:w-[55%] flex-shrink-0">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[10px] font-semibold text-[#697d91] uppercase tracking-widest">Order book — BTC ↑$84k</span>
                            <span class="flex items-center gap-1.5 text-[10px] text-[#26a05e] font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#26a05e] animate-pulse inline-block"></span>
                                LIVE
                            </span>
                        </div>

                        {{-- Header row --}}
                        <div class="flex items-center gap-2 mb-2 text-[9px] text-[#2e3841] font-mono uppercase tracking-wider">
                            <span class="w-16 text-right flex-shrink-0">Side</span>
                            <span class="flex-1 pl-1">Depth</span>
                            <span class="w-10 text-right flex-shrink-0">Price</span>
                            <span class="w-12 text-right flex-shrink-0">Vol</span>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(r,i) in rows" :key="i">
                                <div class="flex items-center gap-2 rounded-lg px-1 py-0.5 transition-all duration-300"
                                     :class="flash===i ? (r.c==='#26a05e' ? 'bg-[#26a05e]/10' : 'bg-[#ff4d4d]/10') : ''">
                                    <span class="w-16 text-[10px] font-mono text-right flex-shrink-0 transition-colors"
                                          :style="flash===i ? 'color:'+r.c : ''"
                                          :class="flash===i ? '' : 'text-[#697d91]'"
                                          x-text="r.s"></span>
                                    <div class="flex-1 h-6 bg-[#0a0b10] rounded overflow-hidden">
                                        <div class="h-full rounded transition-all duration-500 ease-out"
                                             :style="'width:'+r.pct+'%; background:'+r.c+'22; border-right:2px solid '+r.c+'80'"></div>
                                    </div>
                                    <span class="w-10 text-[10px] font-mono text-right flex-shrink-0 tabular-nums"
                                          :style="'color:'+r.c"
                                          x-text="r.p.toFixed(2)"></span>
                                    <span class="w-12 text-[10px] font-mono text-right flex-shrink-0 text-[#697d91] tabular-nums"
                                          x-text="r.vol.toLocaleString()"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Text --}}
                    <div>
                        <div class="text-base font-bold text-white mb-2">Full CLOB order book</div>
                        <p class="text-sm text-[#697d91] leading-relaxed">
                            Yes/No bid-ask depth captured at every oracle tick.
                            Spread, mid price, and imbalance ratio —
                            the microstructure signal most traders never see.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach(['Bid/ask depth','Imbalance ratio','Mid price','Spread'] as $chip)
                            <span class="text-[10px] text-[#697d91] border border-[#1f2937] rounded-full px-2.5 py-1">{{ $chip }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- CTA --}}
        <div class="text-center mt-16 pt-12 border-t border-[#1f2937]">
            <h3 class="text-2xl sm:text-3xl font-bold text-white mb-2">Ready to see the data yourself?</h3>
            <p class="text-[#697d91] text-sm mb-8 max-w-lg mx-auto">
                Free tier · No credit card · Works with Cursor, Claude Code, OpenClaw, Codex, v0, and any tool you build with
            </p>
            <a href="{{ route('register') }}"
               class="inline-flex items-center gap-2 bg-[#0093fd] hover:bg-[#0080e0] text-white font-semibold px-8 py-3 rounded-xl transition-colors text-sm">
                Start Free
                <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none">
                    <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>

    </div>
</section>


{{-- ============================================================
     §3b  HOW IT WORKS — animated steps
============================================================ --}}
<section class="border-t border-[#1f2937] py-24"
    x-data="{
        step: 1,
        progress: 0,
        started: false,
        timer: null,
        start() {
            if (this.started) return;
            this.started = true;
            this.tick();
        },
        tick() {
            this.timer = setInterval(() => {
                this.progress += 1.5;
                if (this.progress >= 100) {
                    this.progress = 0;
                    this.step = this.step >= 3 ? 1 : this.step + 1;
                }
            }, 45);
        },
        setStep(n) {
            this.step = n;
            this.progress = 0;
        }
    }"
    x-intersect.once="start()">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        <div class="text-center mb-16">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-3">Get started in minutes</div>
            <h2 class="text-3xl sm:text-4xl font-bold text-white">Three steps to live data.</h2>
            <p class="text-[#697d91] mt-3">No setup headaches. API key in 60 seconds.</p>
        </div>

        {{-- Desktop: timeline left + visual right | Mobile: stacked --}}
        <div class="flex flex-col lg:flex-row gap-12 lg:gap-20 items-start">

            {{-- LEFT: Steps timeline --}}
            <div class="w-full lg:w-[44%] flex flex-col gap-0">

                @foreach ([
                    ['n'=>1, 'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'label'=>'Create free account', 'sub'=>'Sign up with your email — no card required. Your API key is provisioned instantly.'],
                    ['n'=>2, 'icon'=>'M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label'=>'Copy your API key', 'sub'=>'One key. Works with curl, Python, JS — anything that speaks HTTP. Paste it in and you\'re live.'],
                    ['n'=>3, 'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'label'=>'Start pulling data', 'sub'=>'Hit any endpoint. Oracle ticks, CLOB snapshots, 40+ ML features — all timestamped, clean JSON, ready to plug into your strategy.'],
                ] as $s)
                <div class="flex gap-5 cursor-pointer group" @click="setStep({{ $s['n'] }})">

                    {{-- Icon + connector line column --}}
                    <div class="flex flex-col items-center">
                        {{-- Step circle --}}
                        <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 transition-all duration-300 border-2"
                             :class="step === {{ $s['n'] }} ? 'bg-[#0093fd] border-[#0093fd] shadow-[0_0_16px_#0093fd44]' : (step > {{ $s['n'] }} ? 'bg-[#0093fd]/20 border-[#0093fd]/40' : 'bg-[#17181c] border-[#2e3841] group-hover:border-[#0093fd]/40')">
                            <svg class="w-4 h-4 transition-colors duration-300"
                                 :class="step >= {{ $s['n'] }} ? 'text-white' : 'text-[#697d91]'"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $s['icon'] }}"/>
                            </svg>
                        </div>

                        {{-- Connector line (only between steps) --}}
                        @if ($s['n'] < 3)
                        <div class="w-px flex-1 my-1 min-h-[64px] relative overflow-hidden bg-[#1f2937]">
                            <div class="absolute top-0 left-0 w-full bg-[#0093fd] transition-all duration-300 ease-out rounded-full"
                                 :style="step > {{ $s['n'] }} ? 'height:100%' : (step === {{ $s['n'] }} ? 'height:' + progress + '%' : 'height:0%')"></div>
                        </div>
                        @endif
                    </div>

                    {{-- Step content --}}
                    <div class="pb-10 pt-1.5 flex-1">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="text-[10px] font-bold font-mono tracking-widest transition-colors duration-300"
                                  :class="step >= {{ $s['n'] }} ? 'text-[#0093fd]' : 'text-[#2e3841]'">0{{ $s['n'] }}</span>
                            <h3 class="text-sm font-bold transition-colors duration-300"
                                :class="step === {{ $s['n'] }} ? 'text-white' : 'text-[#697d91]'">{{ $s['label'] }}</h3>
                        </div>
                        <p class="text-[13px] leading-relaxed transition-colors duration-300"
                           :class="step === {{ $s['n'] }} ? 'text-[#8a9ab0]' : 'text-[#2e3841]'">{{ $s['sub'] }}</p>

                        {{-- Progress bar (only on active step) --}}
                        <div class="mt-3 h-0.5 bg-[#1f2937] rounded-full overflow-hidden transition-opacity duration-300"
                             :class="step === {{ $s['n'] }} ? 'opacity-100' : 'opacity-0'">
                            <div class="h-full bg-[#0093fd] rounded-full transition-none"
                                 :style="step === {{ $s['n'] }} ? 'width:' + progress + '%' : 'width:0%'"></div>
                        </div>
                    </div>
                </div>
                @endforeach

            </div>

            {{-- RIGHT: Visual panel — fixed height so page never shifts --}}
            <div class="w-full lg:w-[56%] lg:sticky lg:top-24">
              <div class="relative" style="min-height: 340px;">

                {{-- Step 1 — Account / API key card --}}
                <div x-show="step === 1" class="absolute inset-0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
                        <div class="px-5 py-3 border-b border-[#1f2937] flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-[#ff5f56]"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-[#febc2e]"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-[#28c840]"></div>
                            <span class="ml-2 text-xs text-[#697d91] font-mono">sign up</span>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <div class="text-xs text-[#697d91] mb-1.5">Email</div>
                                <div class="bg-[#0d0e13] border border-[#2e3841] rounded-lg px-3 py-2.5 text-sm text-[#8a9ab0] font-mono">you@example.com</div>
                            </div>
                            <div>
                                <div class="text-xs text-[#697d91] mb-1.5">Password</div>
                                <div class="bg-[#0d0e13] border border-[#2e3841] rounded-lg px-3 py-2.5 text-sm text-[#8a9ab0] font-mono tracking-widest">••••••••••</div>
                            </div>
                            <div class="bg-[#0093fd] rounded-lg py-2.5 text-center text-sm font-semibold text-white">Create free account →</div>
                            <div class="flex items-center gap-2 pt-2">
                                <div class="w-2 h-2 rounded-full bg-[#26a05e] animate-pulse"></div>
                                <span class="text-xs text-[#697d91]">API key provisioned instantly — no card required</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2 — API key + curl --}}
                <div x-show="step === 2" class="absolute inset-0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
                        <div class="px-5 py-3 border-b border-[#1f2937] flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-[#ff5f56]"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-[#febc2e]"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-[#28c840]"></div>
                            <span class="ml-2 text-xs text-[#697d91] font-mono">dashboard — API key</span>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <div class="text-xs text-[#697d91] mb-1.5 uppercase tracking-wider">Your API Key</div>
                                <div class="bg-[#0d0e13] border border-[#0093fd]/30 rounded-lg px-3 py-2.5 flex items-center justify-between gap-3">
                                    <span class="text-sm text-[#0093fd] font-mono tracking-tight">pmd_live_sk_a7f3b9c2e1d845...</span>
                                    <span class="text-xs text-[#697d91] flex-shrink-0 border border-[#2e3841] rounded px-1.5 py-0.5">Copy</span>
                                </div>
                            </div>
                            <div class="bg-[#0d0e13] border border-[#1f2937] rounded-lg p-4">
                                <div class="text-xs text-[#697d91] mb-2 font-mono">Quick test</div>
                                <pre class="text-xs font-mono leading-relaxed text-[#e5e5e5] overflow-x-auto whitespace-pre-wrap"><span class="text-[#697d91]">$</span> curl https://polymarketdata.io/api/v1/windows \
  <span class="text-[#697d91]">-H</span> <span class="text-[#26a05e]">"Authorization: Bearer pmd_live_sk_a7f3..."</span></pre>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3 — JSON response --}}
                <div x-show="step === 3" class="absolute inset-0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-3" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
                        <div class="px-5 py-3 border-b border-[#1f2937] flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-[#ff5f56]"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-[#febc2e]"></div>
                            <div class="w-2.5 h-2.5 rounded-full bg-[#28c840]"></div>
                            <span class="ml-2 text-xs text-[#697d91] font-mono">response.json</span>
                            <span class="ml-auto text-xs text-[#26a05e] font-mono">200 OK</span>
                        </div>
                        <div class="p-5">
                            <pre class="text-xs font-mono leading-relaxed text-[#e5e5e5] overflow-x-auto"><span class="text-[#697d91]">{</span>
  <span class="text-[#0093fd]">"data"</span><span class="text-[#697d91]">:</span> <span class="text-[#697d91]">[{</span>
    <span class="text-[#0093fd]">"asset"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">"BTC"</span><span class="text-[#697d91]">,</span>
    <span class="text-[#0093fd]">"direction"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">"UP"</span><span class="text-[#697d91]">,</span>
    <span class="text-[#0093fd]">"break_price_usd"</span><span class="text-[#697d91]">:</span> <span class="text-[#f0a500]">84231.50</span><span class="text-[#697d91]">,</span>
    <span class="text-[#0093fd]">"outcome"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">"YES"</span><span class="text-[#697d91]">,</span>
    <span class="text-[#0093fd]">"features"</span><span class="text-[#697d91]">:</span> <span class="text-[#697d91]">{</span>
      <span class="text-[#0093fd]">"twap_1m"</span><span class="text-[#697d91]">:</span> <span class="text-[#f0a500]">84198.22</span><span class="text-[#697d91]">,</span>
      <span class="text-[#0093fd]">"vol_3m"</span><span class="text-[#697d91]">:</span> <span class="text-[#f0a500]">312.45</span><span class="text-[#697d91]">,</span>
      <span class="text-[#0093fd]">"clob_imbalance"</span><span class="text-[#697d91]">:</span> <span class="text-[#f0a500]">0.62</span><span class="text-[#697d91]">,</span>
      <span class="text-[#0093fd]">"momentum"</span><span class="text-[#697d91]">:</span> <span class="text-[#f0a500]">0.0041</span>
    <span class="text-[#697d91]">}</span>
  <span class="text-[#697d91]">}]</span><span class="text-[#697d91]">,</span>
  <span class="text-[#0093fd]">"meta"</span><span class="text-[#697d91]">:</span> <span class="text-[#697d91]">{</span> <span class="text-[#0093fd]">"total"</span><span class="text-[#697d91]">:</span> <span class="text-[#f0a500]">2734</span> <span class="text-[#697d91]">}</span>
<span class="text-[#697d91]">}</span></pre>
                        </div>
                    </div>
                </div>

              </div>{{-- /relative --}}
            </div>
        </div>
    </div>
</section>


{{-- ============================================================
     §4  FEATURE DEEP DIVE (tabbed)
============================================================ --}}
<section class="border-t border-[#1f2937] py-28"
    x-data="{
        tab: 'windows',
        tabs: ['windows','oracle','clob','candles'],
        vis: false,
        pinned: false,
        progress: 0,
        _timer: null,
        startCycle() {
            this._timer = setInterval(() => {
                if (this.pinned) { clearInterval(this._timer); return; }
                this.progress += 1.8;
                if (this.progress >= 100) {
                    this.progress = 0;
                    let i = this.tabs.indexOf(this.tab);
                    this.tab = this.tabs[(i + 1) % this.tabs.length];
                }
            }, 60);
        },
        pick(t) {
            this.tab = t;
            this.pinned = true;
            this.progress = 0;
            clearInterval(this._timer);
        }
    }"
    x-intersect.once="vis = true; startCycle()">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="text-center mb-12 transition-all duration-700"
             :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-3">Endpoints</div>
            <h2 class="text-3xl sm:text-4xl font-bold text-white">Everything in one API</h2>
            <p class="text-[#697d91] mt-3 max-w-xl mx-auto">Four endpoints. Every signal that matters for trading Polymarket binary markets.</p>
        </div>

        {{-- Tab bar --}}
        <div class="flex items-center justify-center gap-1 mb-12 bg-[#17181c] border border-[#1f2937]
                    rounded-xl p-1.5 w-fit mx-auto transition-all duration-700 delay-150"
             :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            @foreach([
                ['windows', 'Windows'],
                ['oracle',  'Oracle Ticks'],
                ['clob',    'CLOB Snapshots'],
                ['candles', '1m Candles'],
            ] as [$key, $label])
            <button @click="pick('{{ $key }}')"
                    class="relative text-xs sm:text-sm font-medium px-4 py-2 rounded-lg transition-all duration-200 overflow-hidden"
                    :class="tab === '{{ $key }}'
                        ? 'bg-[#0a0b10] text-[#e5e5e5] border border-[#2e3841] shadow-sm'
                        : 'text-[#697d91] hover:text-[#8a9ab0]'">
                {{ $label }}
                {{-- Progress bar at bottom of active tab --}}
                <span x-show="tab === '{{ $key }}' && !pinned"
                      class="absolute bottom-0 left-0 h-0.5 bg-[#0093fd] rounded-full transition-none"
                      :style="tab === '{{ $key }}' ? 'width:' + progress + '%' : 'width:0'"></span>
            </button>
            @endforeach
        </div>

        {{-- Tab panels — absolute positioned so container height never changes --}}
        <div class="relative min-h-[520px] lg:min-h-[420px]">
        @foreach([
            'windows' => [
                'route'      => 'GET /api/v1/windows',
                'title'      => 'Market Windows & ML Features',
                'desc'       => 'The core dataset. Every Polymarket binary market window — open/close timestamps, break price, final outcome, and 40+ pre-computed ML-ready features. This is the training data for prediction models.',
                'use'        => 'Use for: model training · backtesting · feature analysis',
                'fields'     => ['id', 'asset', 'open_ts', 'close_ts', 'break_price_usd', 'outcome', 'oracle_dist_bp_at_1m', 'oracle_dist_bp_at_close', 'yes_bid_open', 'yes_ask_open', 'clob_imbalance_open', 'twap_1m_usd', 'vol_1m', 'momentum_3m', '+ 30 more'],
                'meta_total' => '2734',
                'json'       => [
                    '"id": <span class="text-[#f97316]">"btc-updown-5m-1774770300"</span>',
                    '"asset": <span class="text-[#f97316]">"BTC"</span>',
                    '"break_price_usd": <span class="text-[#26a05e]">84231.50</span>',
                    '"outcome": <span class="text-[#0093fd]">"YES"</span>',
                    '"oracle_dist_bp_at_1m": <span class="text-[#26a05e]">142</span>',
                    '"clob_imbalance_open": <span class="text-[#26a05e]">0.062</span>',
                    '"twap_1m_usd": <span class="text-[#26a05e]">84198.40</span>',
                    '"yes_bid_open": <span class="text-[#26a05e]">0.48</span>',
                ],
            ],
            'oracle' => [
                'route'      => 'GET /api/v1/oracle-ticks',
                'title'      => 'Oracle Price Ticks',
                'desc'       => 'Every Chainlink RTDS price update captured in real-time via direct WebSocket. Millisecond-accurate timestamps for BTC, ETH, and SOL — the exact same prices Polymarket uses to settle every market.',
                'use'        => 'Use for: price reconstruction · volatility calc · custom features',
                'fields'     => ['id', 'asset_id', 'asset', 'price_usd', 'price_bp', 'ts'],
                'meta_total' => '188203',
                'json'       => [
                    '"asset": <span class="text-[#f97316]">"BTC"</span>',
                    '"price_usd": <span class="text-[#26a05e]">84231.50</span>',
                    '"price_bp": <span class="text-[#26a05e]">8423150</span>',
                    '"ts": <span class="text-[#26a05e]">1774770301842</span>',
                ],
            ],
            'clob' => [
                'route'      => 'GET /api/v1/clob-snapshots',
                'title'      => 'CLOB Order Book Snapshots',
                'desc'       => 'Full Yes/No bid-ask order book state captured from the Polymarket CLOB WebSocket at every oracle tick. Spread, mid price, and imbalance ratio per market window per tick.',
                'use'        => 'Use for: market microstructure · liquidity analysis · spread modelling',
                'fields'     => ['window_id', 'asset_id', 'yes_bid', 'yes_ask', 'no_bid', 'no_ask', 'ts'],
                'meta_total' => '3245835',
                'json'       => [
                    '"window_id": <span class="text-[#f97316]">"btc-updown-5m-1774770300"</span>',
                    '"yes_bid": <span class="text-[#26a05e]">0.48</span>',
                    '"yes_ask": <span class="text-[#26a05e]">0.50</span>',
                    '"no_bid": <span class="text-[#26a05e]">0.49</span>',
                    '"no_ask": <span class="text-[#26a05e]">0.52</span>',
                    '"ts": <span class="text-[#26a05e]">1774770301842</span>',
                ],
            ],
            'candles' => [
                'route'      => 'GET /api/v1/candles',
                'title'      => '1-Minute OHLCV Candles',
                'desc'       => 'Standard 1-minute candlestick data aggregated directly from oracle ticks. OHLCV per asset per minute — ready to plug into any charting library or technical analysis pipeline.',
                'use'        => 'Use for: technical analysis · chart rendering · momentum signals',
                'fields'     => ['asset_id', 'asset', 'open_usd', 'high_usd', 'low_usd', 'close_usd', 'volume', 'ts'],
                'meta_total' => '31420',
                'json'       => [
                    '"asset": <span class="text-[#f97316]">"BTC"</span>',
                    '"open_usd": <span class="text-[#26a05e]">84100.00</span>',
                    '"high_usd": <span class="text-[#26a05e]">84298.50</span>',
                    '"low_usd": <span class="text-[#26a05e]">84056.10</span>',
                    '"close_usd": <span class="text-[#26a05e]">84231.50</span>',
                    '"volume": <span class="text-[#26a05e]">17</span>',
                    '"ts": <span class="text-[#26a05e]">1774770300000</span>',
                ],
            ],
        ] as $key => $panel)
        <div x-show="tab === '{{ $key }}'" x-cloak
             class="absolute inset-0"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

                {{-- Left: description --}}
                <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-8">
                    <div class="font-mono text-xs text-[#0093fd] mb-2">{{ $panel['route'] }}</div>
                    <h3 class="text-xl font-bold text-white mb-3">{{ $panel['title'] }}</h3>
                    <p class="text-[#697d91] text-sm leading-relaxed mb-6">{{ $panel['desc'] }}</p>

                    <div class="text-xs font-semibold text-[#697d91] uppercase tracking-widest mb-3">Response fields</div>
                    <div class="flex flex-wrap gap-2 mb-6">
                        @foreach($panel['fields'] as $f)
                        <span class="text-xs font-mono bg-[#0a0b10] border border-[#1f2937] text-[#8a9ab0] px-2.5 py-1 rounded-lg">{{ $f }}</span>
                        @endforeach
                    </div>

                    <div class="text-xs text-[#26a05e] bg-[#26a05e]/5 border border-[#26a05e]/15 rounded-lg px-3.5 py-2.5 inline-block">
                        {{ $panel['use'] }}
                    </div>
                </div>

                {{-- Right: JSON preview --}}
                <div class="rounded-2xl overflow-hidden border border-[#1f2937] bg-[#0d0e13]">
                    <div class="flex items-center justify-between px-5 py-3 bg-[#17181c] border-b border-[#1f2937]">
                        <span class="text-xs font-mono text-[#697d91]">Response (200 OK)</span>
                        <div class="flex items-center gap-1.5 text-xs text-[#26a05e]">
                            <div class="w-1.5 h-1.5 rounded-full bg-[#26a05e]"></div>
                            application/json
                        </div>
                    </div>
                    <div class="p-5 font-mono text-xs leading-6">
                        <div class="text-[#697d91]">{</div>
                        <div class="pl-4 text-[#697d91]">"data": [{</div>
                        @foreach($panel['json'] as $line)
                        <div class="pl-8 text-[#697d91]">{!! $line !!},</div>
                        @endforeach
                        <div class="pl-4 text-[#697d91]">}],</div>
                        <div class="pl-4"><span class="text-[#8a9ab0]">"meta"</span><span class="text-[#697d91]">: {</span><span class="text-[#8a9ab0]">"total"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">{{ $panel['meta_total'] }}</span><span class="text-[#697d91]">, </span><span class="text-[#8a9ab0]">"page"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">1</span><span class="text-[#697d91]">}</span></div>
                        <div class="text-[#697d91]">}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        </div>{{-- /relative wrapper --}}

    </div>
</section>


{{-- ============================================================
     §4b  USE CASES
============================================================ --}}
<section class="border-t border-[#1f2937] py-24"
    x-data="{
        active: 0,
        vis: false,
        pinned: false,
        progress: 0,
        _timer: null,
        startCycle() {
            this._timer = setInterval(() => {
                if (this.pinned) { clearInterval(this._timer); return; }
                this.progress += 1.6;
                if (this.progress >= 100) {
                    this.progress = 0;
                    this.active = (this.active + 1) % 4;
                }
            }, 60);
        },
        pick(i) {
            this.active = i;
            this.pinned = true;
            this.progress = 0;
            clearInterval(this._timer);
        },
        cases: [
            {
                icon: 'M3 3v16a2 2 0 002 2h16M19 9l-5 5-4-4-3 3',
                label: 'Algo Traders',
                sub: 'Validate before you risk capital',
                accent: '#0093fd',
                title: 'Algo Traders',
                desc: 'Backtest entry and exit signals against real Chainlink oracle ticks and CLOB snapshots. Replay months of BTC, ETH, and SOL Up/Down markets — measure your edge before risking a single dollar.',
                metrics: [
                    { label: 'Strategy', val: 'Mean Reversion', green: false },
                    { label: 'Timeframe', val: '5m BTC Up/Down', green: false },
                    { label: 'Windows', val: '2,734', green: false },
                    { label: 'Win Rate', val: '68.3%', green: true },
                    { label: 'Sharpe', val: '2.14', green: true },
                    { label: 'Max DD', val: '-6.2%', green: false },
                ]
            },
            {
                icon: 'M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18',
                label: 'Bot Builders',
                sub: 'Real data, any language, ship fast',
                accent: '#0093fd',
                title: 'Bot Builders',
                desc: 'Your bot needs oracle prices and CLOB depth on every tick. One API key, four endpoints, clean JSON. Works with Python, JavaScript, curl — anything with HTTP. No setup headaches, API key in 60 seconds.',
                metrics: [
                    { label: 'Endpoint', val: '/oracle-ticks', green: false },
                    { label: 'Latency', val: 'Real-time', green: true },
                    { label: 'Auth', val: 'Bearer token', green: false },
                    { label: 'Format', val: 'JSON', green: false },
                    { label: 'SDKs', val: 'Any HTTP client', green: false },
                    { label: 'Free tier', val: '100 req/day', green: true },
                ]
            },
            {
                icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                label: 'ML Researchers',
                sub: 'Real labels, real financial stakes',
                accent: '#0093fd',
                title: 'ML Researchers',
                desc: '188K+ labeled binary outcomes from Polymarket\'s 5-minute Up/Down markets. 40+ pre-computed features per window — TWAPs, volatility, momentum, CLOB imbalance. Real financial stakes, millisecond timestamps. Novel dataset for prediction research.',
                metrics: [
                    { label: 'Records', val: '188,203', green: false },
                    { label: 'Label type', val: 'Binary YES/NO', green: false },
                    { label: 'Features', val: '40+', green: true },
                    { label: 'Assets', val: 'BTC · ETH · SOL', green: false },
                    { label: 'Timestamps', val: 'Millisecond', green: true },
                    { label: 'Source', val: 'Chainlink RTDS', green: false },
                ]
            },
            {
                icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                label: 'App Developers',
                sub: 'Ship prediction market products fast',
                accent: '#0093fd',
                title: 'App Developers',
                desc: 'Build Polymarket dashboards, analytics tools, or trading apps on top of a clean REST API. Four endpoints. Consistent JSON schema. Free tier to prototype, Pro when you\'re ready to ship to users.',
                metrics: [
                    { label: 'Endpoints', val: '4 total', green: false },
                    { label: 'Auth', val: 'API key header', green: false },
                    { label: 'Schema', val: 'Stable JSON', green: true },
                    { label: 'Pagination', val: 'Cursor-based', green: false },
                    { label: 'Free tier', val: 'Forever', green: true },
                    { label: 'Uptime', val: '99.9% SLA', green: false },
                ]
            },
        ]
    }"
    x-intersect.once="vis = true; startCycle()">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="text-center mb-12 transition-all duration-700"
             :class="vis ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-6'">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-3">Use cases</div>
            <h2 class="text-3xl sm:text-4xl font-bold text-white">Built for builders who move fast.</h2>
            <p class="text-[#697d91] mt-3">From first-time prediction market traders to production quant systems.</p>
        </div>

        {{-- 5-col grid: 2 left nav + 3 right detail --}}
        <div class="grid gap-4 lg:grid-cols-5 lg:gap-6 items-start">

            {{-- LEFT: use case nav cards --}}
            <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-1 gap-2">
                <template x-for="(c, i) in cases" :key="i">
                    <button @click="pick(i)"
                        class="w-full text-left rounded-xl border p-3 sm:p-4 transition-all duration-300 relative overflow-hidden"
                        :style="vis ? 'opacity:1;transform:translateX(0);transition-delay:' + (i*80) + 'ms' : 'opacity:0;transform:translateX(-16px)'"
                        :class="active === i
                            ? 'bg-[#17181c] border-[#0093fd]/40 shadow-[0_0_20px_#0093fd0a]'
                            : 'bg-[#17181c]/40 border-transparent hover:bg-[#17181c]/70 hover:border-[#1f2937]'">

                        <div class="flex items-center gap-3">
                            {{-- Icon --}}
                            <div class="flex w-9 h-9 flex-shrink-0 items-center justify-center rounded-lg transition-colors"
                                 :class="active === i ? 'bg-[#0093fd]/10' : 'bg-[#1f2937]'">
                                <svg class="w-4 h-4 transition-colors" :class="active === i ? 'text-[#0093fd]' : 'text-[#697d91]'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path :d="c.icon"/>
                                </svg>
                            </div>

                            {{-- Text --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold transition-colors" :class="active === i ? 'text-white' : 'text-[#8a9ab0]'" x-text="c.label"></span>
                                    <svg class="w-3.5 h-3.5 transition-all" :class="active === i ? 'text-[#0093fd] rotate-90' : 'text-[#2e3841] rotate-0'"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/>
                                    </svg>
                                </div>
                                <p class="text-[11px] mt-0.5 hidden sm:block transition-colors"
                                   :class="active === i ? 'text-[#697d91]' : 'text-[#2e3841]'"
                                   x-text="c.sub"></p>
                            </div>
                        </div>

                        {{-- Progress bar --}}
                        <span x-show="active === i && !pinned"
                              class="absolute bottom-0 left-0 h-0.5 bg-[#0093fd] rounded-full transition-none"
                              :style="'width:' + (active === i ? progress : 0) + '%'"></span>
                    </button>
                </template>
            </div>

            {{-- RIGHT: detail panel --}}
            <div class="lg:col-span-3 transition-all duration-700 delay-300"
                 :class="vis ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-6'">
                <div class="rounded-2xl border border-[#1f2937] bg-[#17181c] min-h-[360px] relative overflow-hidden">
                    <div class="absolute top-0 inset-x-0 h-px bg-gradient-to-r from-transparent via-[#0093fd]/40 to-transparent"></div>

                    <template x-for="(c, i) in cases" :key="i">
                        <div x-show="active === i"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 translate-x-4"
                             x-transition:enter-end="opacity-100 translate-x-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="absolute inset-0 flex flex-col p-5 sm:p-7">

                            {{-- Title --}}
                            <div class="flex items-center gap-2.5 mb-3">
                                <div class="w-7 h-7 flex items-center justify-center rounded-lg bg-[#0093fd]/10">
                                    <svg class="w-3.5 h-3.5 text-[#0093fd]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path :d="c.icon"/>
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-white" x-text="c.title"></h3>
                            </div>

                            {{-- Description --}}
                            <p class="text-sm text-[#697d91] leading-relaxed mb-6" x-text="c.desc"></p>

                            {{-- Sample output metrics --}}
                            <div class="mt-auto">
                                <div class="flex items-center gap-1.5 mb-3">
                                    <div class="w-1.5 h-1.5 rounded-full bg-[#0093fd]"></div>
                                    <span class="text-[10px] font-semibold uppercase tracking-wider text-[#697d91]">Sample output</span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <template x-for="(m, j) in c.metrics" :key="j">
                                        <div class="rounded-lg border bg-[#0d0e13] px-3 py-2.5 transition-all duration-300"
                                             :style="'transition-delay:' + (j * 60) + 'ms'"
                                             :class="m.green ? 'border-[#0093fd]/25' : 'border-[#1f2937]'">
                                            <div class="text-[9px] text-[#697d91] uppercase tracking-wider" x-text="m.label"></div>
                                            <div class="text-sm font-bold font-mono mt-0.5 transition-colors"
                                                 :class="m.green ? 'text-[#0093fd]' : 'text-white'"
                                                 x-text="m.val"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>
</section>


{{-- ============================================================
     §5  WHY WE BUILT THIS
============================================================ --}}
<style>
@keyframes flow-down {
    0%   { top: 0%;   opacity: 0; }
    15%  { opacity: 1; }
    85%  { opacity: 1; }
    100% { top: 100%; opacity: 0; }
}
.flow-packet { animation: flow-down 1.8s ease-in-out infinite; }
.flow-packet-2 { animation: flow-down 1.8s ease-in-out 0.6s infinite; }
.flow-packet-3 { animation: flow-down 1.8s ease-in-out 1.2s infinite; }
</style>
<section class="border-t border-[#1f2937] py-24 relative overflow-hidden"
    x-data="{ vis: false }"
    x-intersect.once="vis = true">

    <div class="absolute right-0 top-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-[#0093fd]/[.025] rounded-full blur-[130px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

            {{-- Left: simplified copy --}}
            <div class="transition-all duration-700" :class="vis ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-8'">
                <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-4">Why we built this</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-white leading-tight mb-6">
                    Stop rebuilding<br>the same pipeline.
                </h2>
                <p class="text-[#697d91] text-base leading-relaxed mb-8">
                    Every strategy starts the same way — scrape, reconstruct, misalign, start over. We wired directly into the sources and built the recorder ourselves.
                </p>

                <div class="space-y-3 mb-8">
                    @foreach([
                        ['Oracle prices with millisecond timestamps', '#0093fd'],
                        ['Full CLOB order book at every oracle tick', '#a78bfa'],
                        ['40+ ML features, pre-computed per window', '#26a05e'],
                    ] as [$point, $col])
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0" style="background:{{ $col }}18; border:1px solid {{ $col }}40">
                            <svg class="w-2.5 h-2.5" viewBox="0 0 10 10" fill="none">
                                <path d="M2 5l2.5 2.5L8 3" stroke="{{ $col }}" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <span class="text-sm text-[#8a9ab0]">{{ $point }}</span>
                    </div>
                    @endforeach
                </div>

                <p class="text-sm text-[#697d91] border-l-2 border-[#0093fd]/40 pl-4 italic">
                    We built the recorder. Now it's your API.
                </p>
            </div>

            {{-- Right: animated data pipeline --}}
            <div class="transition-all duration-700 delay-200" :class="vis ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'">
                @php
                $pipeline = [
                    ['Chainlink RTDS',        'wss://ws-live-data.polymarket.com',          'Real-time oracle prices',      '#0093fd', 'M5 3h6M3 7h10M5 11h6', true],
                    ['Polymarket CLOB',       'wss://ws-subscriptions-clob.polymarket.com', 'Yes/No order book per tick',   '#a78bfa', 'M2 4h12M2 8h12M2 12h8',true],
                    ['Gamma API',             'https://gamma-api.polymarket.com',            'Market discovery every 20s',   '#26a05e', 'M8 2a6 6 0 100 12A6 6 0 008 2zm0 4v4m0 0l-2-2m2 2l2-2', true],
                    ['REST API',              'api.polymarketdata.io/v1/*',                  'Your language. Your queries.', '#f97316', 'M2 6h12v8H2zM6 6V4h4v2',false],
                ];
                @endphp

                <div class="space-y-0">
                    @foreach($pipeline as $i => [$name, $url, $detail, $color, $icon, $live])
                    <div class="transition-all duration-500"
                         :style="vis ? 'opacity:1;transform:translateY(0);transition-delay:{{ $i * 100 }}ms' : 'opacity:0;transform:translateY(16px)'">

                        <div class="flex items-start gap-4 bg-[#17181c] border rounded-xl p-4 transition-all duration-300 hover:shadow-[0_0_20px_rgba(0,0,0,.4)]"
                             style="border-color: {{ $color }}22"
                             onmouseenter="this.style.borderColor='{{ $color }}55'"
                             onmouseleave="this.style.borderColor='{{ $color }}22'">

                            {{-- Icon --}}
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                                 style="background:{{ $color }}12; border:1px solid {{ $color }}30">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                                    <path d="{{ $icon }}" stroke="{{ $color }}" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-0.5">
                                    <span class="text-sm font-semibold text-white">{{ $name }}</span>
                                    @if($live)
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background:{{ $color }}"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2" style="background:{{ $color }}"></span>
                                        </span>
                                        <span class="text-[9px] font-bold uppercase tracking-wider" style="color:{{ $color }}">Live</span>
                                    </div>
                                    @else
                                    <span class="text-[9px] font-bold uppercase tracking-wider text-[#697d91]">Output</span>
                                    @endif
                                </div>
                                <div class="text-[11px] font-mono text-[#697d91] truncate mb-0.5">{{ $url }}</div>
                                <div class="text-xs text-[#697d91]">{{ $detail }}</div>
                            </div>
                        </div>

                        {{-- Animated connector between cards --}}
                        @if($i < 3)
                        <div class="flex justify-center my-1">
                            <div class="relative w-px h-8 bg-[#1f2937] overflow-hidden rounded-full">
                                <div class="flow-packet absolute w-px rounded-full" style="height:40%;background:{{ $color }};left:0"></div>
                                <div class="flow-packet-2 absolute w-px rounded-full" style="height:40%;background:{{ $color }};left:0"></div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</section>


{{-- ============================================================
     §6  TECH STACK MARQUEE
============================================================ --}}
<section class="border-y border-[#1f2937] py-5 bg-[#17181c]/30 overflow-hidden">
    <div class="flex items-center gap-4 mb-1.5">
        <span class="text-xs text-[#2e3841] font-medium whitespace-nowrap pl-6">Works with your stack →</span>
    </div>
    <div class="relative overflow-hidden">
        <div class="marquee-inner flex items-center gap-10 whitespace-nowrap w-max">
            @php
            $techs = ['Python', 'pandas', 'NumPy', 'scikit-learn', 'PyTorch', 'Jupyter', 'Node.js', 'TypeScript', 'Go', 'Rust', 'R', 'Julia', 'Ruby', 'Java', 'C#', 'PHP'];
            $double = array_merge($techs, $techs);
            @endphp
            @foreach($double as $tech)
            <span class="text-sm font-medium text-[#2e3841] hover:text-[#697d91] transition-colors cursor-default">{{ $tech }}</span>
            @endforeach
        </div>
    </div>
</section>


{{-- ============================================================
     §7  PRICING
============================================================ --}}
<section class="py-28" id="pricing">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-3">Pricing</div>
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3">Simple. Transparent. No surprises.</h2>
            <p class="text-[#697d91] max-w-md mx-auto">Start free. Scale when you need it. No platform fees, no seat limits, no hidden charges.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto mb-12">

            {{-- Free --}}
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-7 flex flex-col">
                <div class="mb-6">
                    <div class="text-[10px] font-bold text-[#697d91] uppercase tracking-[0.15em] mb-4">Free</div>
                    <div class="flex items-baseline gap-1 mb-2">
                        <span class="text-5xl font-extrabold text-white tracking-tight">$0</span>
                        <span class="text-[#697d91] text-sm">/mo</span>
                    </div>
                    <p class="text-xs text-[#697d91]">For exploration and prototyping</p>
                </div>
                <ul class="space-y-3 text-sm mb-8 flex-1">
                    @foreach([
                        ['100 requests / day', true],
                        ['7 days history', true],
                        ['All 4 endpoints', true],
                        ['REST API access', true],
                        ['CSV / SQLite export', false],
                        ['Backtest endpoint', false],
                    ] as [$f, $included])
                    <li class="flex items-center gap-2.5 {{ $included ? 'text-[#8a9ab0]' : 'text-[#2e3841]' }}">
                        @if($included)
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" class="flex-shrink-0 text-[#0093fd]">
                            <path d="M2.5 7l3.5 3.5L11.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        @else
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" class="flex-shrink-0">
                            <path d="M4 7h6" stroke="#2e3841" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        @endif
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center border border-[#2e3841] hover:border-[#4a5e6e] hover:bg-[#17181c]
                          text-[#8a9ab0] hover:text-[#e5e5e5] font-medium px-4 py-2.5 rounded-xl
                          transition-all text-sm">
                    Get started free
                </a>
            </div>

            {{-- Builder --}}
            <div class="builder-card rounded-2xl p-7 flex flex-col relative">
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 z-10">
                    <span class="bg-[#0093fd] text-white text-[10px] font-bold px-3.5 py-1 rounded-full
                                 uppercase tracking-[0.1em] shadow-lg shadow-[#0093fd]/30">Most popular</span>
                </div>
                <div class="mb-6">
                    <div class="text-[10px] font-bold text-[#0093fd] uppercase tracking-[0.15em] mb-4">Builder</div>
                    <div class="flex items-baseline gap-1 mb-2">
                        <span class="text-5xl font-extrabold text-white tracking-tight">$29</span>
                        <span class="text-[#697d91] text-sm">/mo</span>
                    </div>
                    <p class="text-xs text-[#697d91]">For active strategy development</p>
                </div>
                <ul class="space-y-3 text-sm mb-8 flex-1">
                    @foreach([
                        '10,000 requests / day',
                        '90 days history',
                        'All 4 endpoints',
                        'REST API access',
                        'Priority email support',
                    ] as $f)
                    <li class="flex items-center gap-2.5 text-[#8a9ab0]">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" class="flex-shrink-0 text-[#0093fd]">
                            <path d="M2.5 7l3.5 3.5L11.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center bg-[#0093fd] hover:bg-[#007fd6] text-white font-semibold
                          px-4 py-2.5 rounded-xl transition-all text-sm
                          shadow-lg shadow-[#0093fd]/25">
                    Start building
                </a>
            </div>

            {{-- Pro --}}
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-7 flex flex-col">
                <div class="mb-6">
                    <div class="text-[10px] font-bold text-[#697d91] uppercase tracking-[0.15em] mb-4">Pro</div>
                    <div class="flex items-baseline gap-1 mb-2">
                        <span class="text-5xl font-extrabold text-white tracking-tight">$99</span>
                        <span class="text-[#697d91] text-sm">/mo</span>
                    </div>
                    <p class="text-xs text-[#697d91]">For production trading systems</p>
                </div>
                <ul class="space-y-3 text-sm mb-8 flex-1">
                    @foreach([
                        '100,000 requests / day',
                        'Full history (unlimited)',
                        'All 4 endpoints',
                        'REST API access',
                        'CSV / SQLite export',
                        'Backtest endpoint',
                        'Dedicated support',
                    ] as $f)
                    <li class="flex items-center gap-2.5 text-[#8a9ab0]">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" class="flex-shrink-0 text-[#0093fd]">
                            <path d="M2.5 7l3.5 3.5L11.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $f }}
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center border border-[#2e3841] hover:border-[#4a5e6e] hover:bg-[#17181c]
                          text-[#8a9ab0] hover:text-[#e5e5e5] font-medium px-4 py-2.5 rounded-xl
                          transition-all text-sm">
                    Go pro
                </a>
            </div>
        </div>

        {{-- Comparison table --}}
        <div class="max-w-5xl mx-auto bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-[#1f2937]">
                <span class="text-xs font-semibold text-[#697d91] uppercase tracking-widest">Full feature comparison</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[#1f2937]">
                            <th class="text-left px-6 py-3.5 text-[#697d91] font-medium text-xs uppercase tracking-wider">Feature</th>
                            <th class="text-center px-6 py-3.5 text-[#697d91] font-medium text-xs uppercase tracking-wider">Free</th>
                            <th class="text-center px-6 py-3.5 text-[#0093fd] font-semibold text-xs uppercase tracking-wider">Builder</th>
                            <th class="text-center px-6 py-3.5 text-[#697d91] font-medium text-xs uppercase tracking-wider">Pro</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['Daily requests',      '100',        '10,000',    '100,000'],
                            ['History depth',       '7 days',     '90 days',   'Unlimited'],
                            ['Windows endpoint',    true,         true,        true],
                            ['Oracle ticks',        true,         true,        true],
                            ['CLOB snapshots',      true,         true,        true],
                            ['1m candles',          true,         true,        true],
                            ['CSV / SQLite export', false,        false,       true],
                            ['Backtest endpoint',   false,        false,       true],
                            ['Support',             'Community',  'Priority email', 'Dedicated'],
                        ] as $i => [$feat, $free, $builder, $pro])
                        <tr class="{{ $i % 2 === 0 ? '' : 'bg-[#0a0b10]/40' }} hover:bg-[#0093fd]/[.03] transition-colors border-b border-[#1f2937]/50 last:border-b-0">
                            <td class="px-6 py-3.5 text-[#8a9ab0] font-medium">{{ $feat }}</td>
                            @foreach([$free, $builder, $pro] as $val)
                            <td class="text-center px-6 py-3.5">
                                @if($val === true)
                                    <svg class="w-4 h-4 text-[#26a05e] mx-auto" viewBox="0 0 16 16" fill="none">
                                        <path d="M3 8l3.5 3.5L13 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @elseif($val === false)
                                    <span class="text-[#2e3841] text-lg leading-none">—</span>
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
     §8  FAQ
============================================================ --}}
<section class="border-t border-[#1f2937] py-28">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Section header --}}
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-6 mb-14">
            <div>
                <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-3">FAQ</div>
                <h2 class="text-3xl font-bold text-white">Common questions</h2>
            </div>
            <a href="mailto:hello@polymarketdata.io"
               class="flex-shrink-0 inline-flex items-center gap-2 text-sm text-[#697d91] hover:text-[#e5e5e5] border border-[#1f2937] hover:border-[#2e3841] rounded-xl px-4 py-2.5 transition-colors group">
                <svg class="w-4 h-4" viewBox="0 0 16 16" fill="none">
                    <path d="M2 4.5A1.5 1.5 0 013.5 3h9A1.5 1.5 0 0114 4.5v7a1.5 1.5 0 01-1.5 1.5h-9A1.5 1.5 0 012 11.5v-7z" stroke="currentColor" stroke-width="1.2"/>
                    <path d="M2 5l6 4.5L14 5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
                </svg>
                Ask us anything
                <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" viewBox="0 0 14 14" fill="none">
                    <path d="M3 7h8M8 4l3 3-3 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>

        {{-- FAQ items --}}
        <div class="divide-y divide-[#1f2937]">
            @foreach([
                [
                    'n' => '01',
                    'Q' => 'Where does the data come from?',
                    'A' => 'We connect directly to three live sources: Chainlink\'s RTDS WebSocket for oracle price feeds (the same prices Polymarket uses to settle markets), the Polymarket CLOB WebSocket for order book data, and the Gamma API for market metadata and outcomes. Everything is timestamped at the socket layer — no secondary scraping.',
                ],
                [
                    'n' => '02',
                    'Q' => 'How far back does history go?',
                    'A' => 'We started live recording in early 2026 and are actively backfilling session recordings from before launch. Free tier gets 7 days. Builder gets 90 days. Pro gets the full archive.',
                ],
                [
                    'n' => '03',
                    'Q' => 'What are "window features" exactly?',
                    'A' => 'A window is one Polymarket binary market — e.g. "Will BTC be above $84,000 in 5 minutes?". For each resolved window we pre-compute 40+ ML-ready features: TWAPs at multiple intervals, oracle distance from break price, CLOB imbalance, volatility, momentum, and more. Features you\'d otherwise spend days computing yourself.',
                ],
                [
                    'n' => '04',
                    'Q' => 'How accurate are the timestamps?',
                    'A' => 'Oracle ticks carry the timestamp from the Chainlink WebSocket message — millisecond precision. CLOB snapshots are timestamped when received alongside the oracle tick. There is a small constant network latency, but no batch-processing delay.',
                ],
                [
                    'n' => '05',
                    'Q' => 'Is this affiliated with Polymarket?',
                    'A' => 'No. This is a completely independent data service. We are not affiliated with, endorsed by, or associated with Polymarket, UMA Protocol, or any of their affiliates.',
                ],
                [
                    'n' => '06',
                    'Q' => 'Can I cancel anytime?',
                    'A' => 'Yes. All subscriptions are billed monthly. Cancel from your billing dashboard and you keep access through the end of the current period. No questions, no friction.',
                ],
            ] as $faq)
            <div x-data="{ open: false }" class="group">
                <button @click="open = !open"
                        class="w-full flex items-start gap-5 py-6 text-left transition-colors">

                    {{-- Number --}}
                    <span class="flex-shrink-0 font-mono text-xs font-semibold mt-0.5 transition-colors"
                          :class="open ? 'text-[#0093fd]' : 'text-[#2e3841] group-hover:text-[#697d91]'">
                        {{ $faq['n'] }}
                    </span>

                    {{-- Question --}}
                    <span class="flex-1 text-[#e5e5e5] font-medium text-base leading-snug group-hover:text-white transition-colors pr-4">
                        {{ $faq['Q'] }}
                    </span>

                    {{-- Chevron --}}
                    <div class="flex-shrink-0 mt-0.5 w-7 h-7 rounded-lg border flex items-center justify-center transition-all duration-200"
                         :class="open
                            ? 'bg-[#0093fd]/10 border-[#0093fd]/30 text-[#0093fd]'
                            : 'bg-[#17181c] border-[#2e3841] text-[#697d91] group-hover:border-[#697d91]'">
                        <svg class="w-3.5 h-3.5 transition-transform duration-300"
                             :class="open ? 'rotate-180' : ''"
                             viewBox="0 0 14 14" fill="none">
                            <path d="M3 5l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </button>

                <div x-show="open" x-collapse>
                    <div class="pb-6 pl-10 pr-12">
                        <p class="text-[#8a9ab0] text-sm leading-7">{{ $faq['A'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</section>


{{-- ============================================================
     §9  CTA
============================================================ --}}
<section class="border-t border-[#1f2937] relative overflow-hidden py-32">

    {{-- Ambient glow --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute bottom-[-100px] left-1/2 -translate-x-1/2 w-[700px] h-[400px]
                    bg-[#0093fd]/[.07] rounded-full blur-[120px]"></div>
    </div>
    <div class="absolute inset-0 dot-grid opacity-[.4] pointer-events-none"></div>

    <div class="relative max-w-3xl mx-auto px-4 text-center">

        <div class="inline-flex items-center gap-2 bg-[#26a05e]/10 border border-[#26a05e]/20 rounded-full
                    px-4 py-1.5 text-xs font-semibold text-[#26a05e] mb-8">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Free tier — no credit card needed
        </div>

        <h2 class="text-4xl sm:text-5xl font-extrabold text-white mb-5 leading-tight tracking-tight">
            Start pulling real<br>Polymarket data today
        </h2>
        <p class="text-[#8a9ab0] text-lg mb-12 max-w-lg mx-auto leading-relaxed">
            API key in 60 seconds. 100 requests a day, free forever. Upgrade only when your strategy is ready for production.
        </p>

        <div class="flex flex-wrap gap-4 justify-center mb-10">
            <a href="{{ route('register') }}"
               class="inline-flex items-center gap-2.5 bg-[#0093fd] hover:bg-[#007fd6] text-white
                      font-semibold px-8 py-4 rounded-xl transition-all text-sm
                      shadow-[0_0_32px_rgba(0,147,253,.4)] hover:shadow-[0_0_48px_rgba(0,147,253,.55)]">
                Create free account
                <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
                    <path d="M3 7.5h9M8.5 3.5l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <a href="{{ route('docs') }}"
               class="inline-flex items-center gap-2 border border-[#2e3841] hover:border-[#4a5e6e]
                      text-[#8a9ab0] hover:text-[#e5e5e5] font-medium px-6 py-4 rounded-xl
                      transition-all text-sm hover:bg-[#17181c]">
                View API docs
            </a>
        </div>

        <p class="text-xs text-[#2e3841]">
            Not affiliated with Polymarket. Independent third-party data service. ·
            <a href="{{ route('terms') }}" class="hover:text-[#697d91] transition-colors ml-1">Terms</a> ·
            <a href="{{ route('privacy') }}" class="hover:text-[#697d91] transition-colors ml-1">Privacy</a>
        </p>
    </div>
</section>

@endsection
