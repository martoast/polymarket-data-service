@extends('layouts.app')

@section('title', 'Polymarket Data API — Oracle Ticks, CLOB Snapshots & ML Features')

@section('head')
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
</style>
@endsection

@section('content')

{{-- ============================================================
     §1  HERO
============================================================ --}}
<section class="relative overflow-hidden dot-grid min-h-[88vh] flex items-center">

    {{-- Ambient glows --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div class="glow-anim absolute top-[-160px] left-1/2 -translate-x-1/2 w-[900px] h-[600px]
                    rounded-full bg-[#0093fd]/[.07] blur-[130px]"></div>
        <div class="absolute top-[60px] left-[8%] w-[380px] h-[380px]
                    rounded-full bg-[#0093fd]/[.04] blur-[90px]"></div>
        <div class="absolute bottom-[-80px] right-[10%] w-[300px] h-[300px]
                    rounded-full bg-[#0093fd]/[.03] blur-[80px]"></div>
        {{-- Vignette overlay --}}
        <div class="absolute inset-0 bg-gradient-to-b from-[#0a0b10]/0 via-transparent to-[#0a0b10]"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-28">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_1.1fr] gap-16 items-center">

            {{-- ── Left: copy ── --}}
            <div class="fade-up">

                {{-- Live badge --}}
                <div class="inline-flex items-center gap-2.5 bg-[#0093fd]/[.08] border border-[#0093fd]/25
                            rounded-full px-4 py-1.5 mb-8">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#0093fd] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#0093fd]"></span>
                    </span>
                    <span class="text-[#0093fd] text-xs font-semibold tracking-wide">Recorder live — BTC · ETH · SOL</span>
                </div>

                {{-- Headline --}}
                <h1 class="text-[3.4rem] sm:text-[4rem] font-extrabold leading-[1.06] tracking-[-0.02em] text-white mb-6">
                    The data layer<br>
                    Polymarket traders<br>
                    <span class="relative inline-block">
                        <span class="text-[#0093fd]">actually need</span>
                        <svg class="absolute -bottom-1 left-0 w-full" height="4" viewBox="0 0 200 4" preserveAspectRatio="none">
                            <path d="M0 2 Q50 0 100 2 Q150 4 200 2" stroke="#0093fd" stroke-width="1.5" fill="none" opacity=".5"/>
                        </svg>
                    </span>
                </h1>

                {{-- Sub --}}
                <p class="text-[#8a9ab0] text-[1.1rem] leading-[1.75] mb-10 max-w-[480px]">
                    Millisecond oracle ticks from Chainlink. Full CLOB order book snapshots. 40+ pre-computed ML features — per resolved window. REST API, available from day one.
                </p>

                {{-- CTAs --}}
                <div class="flex flex-wrap gap-3 mb-9">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-2.5 bg-[#0093fd] hover:bg-[#007fd6] active:bg-[#006bbf]
                              text-white font-semibold px-7 py-3.5 rounded-xl transition-all duration-150
                              shadow-[0_0_24px_rgba(0,147,253,.35)] hover:shadow-[0_0_32px_rgba(0,147,253,.5)] text-sm">
                        Start for free
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none">
                            <path d="M3 7.5h9M8.5 3.5l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <a href="{{ route('docs') }}"
                       class="inline-flex items-center gap-2 border border-[#2e3841] hover:border-[#4a5e6e]
                              text-[#8a9ab0] hover:text-[#e5e5e5] font-medium px-6 py-3.5 rounded-xl
                              transition-all duration-150 text-sm hover:bg-[#17181c]">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <rect x="2" y="1.5" width="10" height="11" rx="1.5" stroke="currentColor" stroke-width="1.2"/>
                            <path d="M4.5 4.5h5M4.5 7h5M4.5 9.5h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>
                        </svg>
                        Read the docs
                    </a>
                </div>

                {{-- Trust chips --}}
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-[#697d91]">
                    @foreach(['No credit card required', 'API key in 60 seconds', 'Any language — REST'] as $chip)
                    <span class="flex items-center gap-1.5">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <circle cx="6" cy="6" r="5" stroke="#26a05e" stroke-width="1"/>
                            <path d="M3.5 6l2 2 3-3" stroke="#26a05e" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $chip }}
                    </span>
                    @if (!$loop->last)<span class="text-[#1f2937]">·</span>@endif
                    @endforeach
                </div>
            </div>

            {{-- ── Right: code preview ── --}}
            <div class="relative" x-data="{ lang: 'python' }">

                {{-- Outer glow --}}
                <div class="absolute -inset-6 rounded-3xl bg-[#0093fd]/[.04] blur-2xl pointer-events-none"></div>

                {{-- Window chrome --}}
                <div class="relative rounded-2xl overflow-hidden border border-[#1f2937]
                            bg-[#0d0e13] shadow-[0_32px_80px_rgba(0,0,0,.6)]">

                    {{-- Title bar --}}
                    <div class="flex items-center justify-between px-5 py-3.5 bg-[#17181c] border-b border-[#1f2937]">
                        <div class="flex items-center gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-[#ff5f57]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#febc2e]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#28c840]"></div>
                        </div>

                        {{-- Language tabs --}}
                        <div class="flex items-center gap-0.5 bg-[#0a0b10] rounded-lg p-0.5">
                            @foreach([['python','Python'],['curl','cURL'],['js','JavaScript']] as [$key,$label])
                            <button @click="lang = '{{ $key }}'"
                                    :class="lang === '{{ $key }}' ? 'bg-[#1e2428] text-[#e5e5e5]' : 'text-[#697d91] hover:text-[#8a9ab0]'"
                                    class="text-xs font-medium px-2.5 py-1 rounded-md transition-all">
                                {{ $label }}
                            </button>
                            @endforeach
                        </div>

                        {{-- Status --}}
                        <div class="flex items-center gap-1.5 text-xs text-[#26a05e] font-mono">
                            <div class="w-1.5 h-1.5 rounded-full bg-[#26a05e] animate-pulse"></div>
                            200 OK
                        </div>
                    </div>

                    {{-- Code body --}}
                    <div class="p-5 min-h-[320px]">

                        {{-- Python --}}
                        <pre x-show="lang==='python'" class="font-mono text-xs leading-7 text-[#697d91] overflow-x-auto"><span class="text-[#697d91]">import</span> <span class="text-[#e5e5e5]">requests</span>

<span class="text-[#e5e5e5]">API_KEY</span> = <span class="text-[#f97316]">"sk_live_••••••••••"</span>

<span class="text-[#e5e5e5]">resp</span> = <span class="text-[#e5e5e5]">requests</span>.<span class="text-[#0093fd]">get</span>(
    <span class="text-[#f97316]">"https://api.polymarketdata.io/api/v1/windows"</span>,
    <span class="text-[#e5e5e5]">headers</span>={<span class="text-[#f97316]">"Authorization"</span>: <span class="text-[#f97316]">f"Bearer {API_KEY}"</span>},
    <span class="text-[#e5e5e5]">params</span>={<span class="text-[#f97316]">"asset"</span>: <span class="text-[#f97316]">"BTC"</span>, <span class="text-[#f97316]">"limit"</span>: <span class="text-[#26a05e]">50</span>}
)

<span class="text-[#697d91]"># 40+ ML features per resolved window</span>
<span class="text-[#e5e5e5]">windows</span> = <span class="text-[#e5e5e5]">resp</span>.<span class="text-[#0093fd]">json</span>()[<span class="text-[#f97316]">"data"</span>]</pre>

                        {{-- cURL --}}
                        <pre x-show="lang==='curl'" x-cloak class="font-mono text-xs leading-7 text-[#697d91] overflow-x-auto"><span class="text-[#e5e5e5]">curl</span> <span class="text-[#f97316]">"https://api.polymarketdata.io/api/v1/windows"</span> \
  <span class="text-[#e5e5e5]">-H</span> <span class="text-[#f97316]">"Authorization: Bearer sk_live_••••••••••"</span> \
  <span class="text-[#e5e5e5]">-G</span> \
  <span class="text-[#e5e5e5]">--data-urlencode</span> <span class="text-[#f97316]">"asset=BTC"</span> \
  <span class="text-[#e5e5e5]">--data-urlencode</span> <span class="text-[#f97316]">"limit=50"</span></pre>

                        {{-- JavaScript --}}
                        <pre x-show="lang==='js'" x-cloak class="font-mono text-xs leading-7 text-[#697d91] overflow-x-auto"><span class="text-[#697d91]">const</span> <span class="text-[#e5e5e5]">res</span> = <span class="text-[#697d91]">await</span> <span class="text-[#0093fd]">fetch</span>(
  <span class="text-[#f97316]">"https://api.polymarketdata.io/api/v1/windows?asset=BTC&limit=50"</span>,
  { <span class="text-[#e5e5e5]">headers</span>: { <span class="text-[#f97316]">"Authorization"</span>: <span class="text-[#f97316]">"Bearer sk_live_••••"</span> } }
)

<span class="text-[#697d91]">const</span> { <span class="text-[#e5e5e5]">data</span> } = <span class="text-[#697d91]">await</span> <span class="text-[#e5e5e5]">res</span>.<span class="text-[#0093fd]">json</span>()</pre>

                        {{-- JSON response (always visible) --}}
                        <div class="mt-4 pt-4 border-t border-[#1f2937] font-mono text-xs leading-6">
                            <div class="text-[#697d91]">{</div>
                            <div class="pl-4"><span class="text-[#8a9ab0]">"data"</span><span class="text-[#697d91]">: [{</span></div>
                            <div class="pl-8 space-y-0.5">
                                <div><span class="text-[#8a9ab0]">"id"</span><span class="text-[#697d91]">:</span> <span class="text-[#f97316]">"btc-updown-5m-1774770300"</span><span class="text-[#697d91]">,</span></div>
                                <div><span class="text-[#8a9ab0]">"break_price_usd"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">84231.50</span><span class="text-[#697d91]">,</span></div>
                                <div><span class="text-[#8a9ab0]">"outcome"</span><span class="text-[#697d91]">:</span> <span class="text-[#0093fd]">"YES"</span><span class="text-[#697d91]">,</span></div>
                                <div><span class="text-[#8a9ab0]">"oracle_dist_bp_at_1m"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">142</span><span class="text-[#697d91]">,</span></div>
                                <div><span class="text-[#8a9ab0]">"yes_bid_open"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">0.48</span><span class="text-[#697d91]">,</span></div>
                                <div><span class="text-[#8a9ab0]">"clob_imbalance_open"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">0.062</span><span class="text-[#697d91]">,</span></div>
                                <div><span class="text-[#8a9ab0]">"twap_1m_usd"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">84198.40</span></div>
                            </div>
                            <div class="pl-4 text-[#697d91]">}],</div>
                            <div class="pl-4"><span class="text-[#8a9ab0]">"meta"</span><span class="text-[#697d91]">: {</span><span class="text-[#8a9ab0]">"total"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">2734</span><span class="text-[#697d91]">}</span></div>
                            <div class="text-[#697d91]">}</div>
                        </div>
                    </div>
                </div>

                {{-- Floating chips --}}
                <div class="absolute -bottom-5 -left-5 bg-[#17181c] border border-[#1f2937] rounded-xl
                            px-4 py-3 shadow-2xl backdrop-blur-sm">
                    <div class="text-[10px] text-[#697d91] uppercase tracking-wider mb-1">Oracle cadence</div>
                    <div class="text-sm font-bold text-white flex items-center gap-1.5">
                        ~3<span class="text-xs font-normal text-[#697d91]">s</span>
                        <span class="text-[10px] text-[#26a05e] font-semibold bg-[#26a05e]/10 border border-[#26a05e]/20 rounded px-1.5 py-0.5">LIVE</span>
                    </div>
                </div>
                <div class="absolute -top-5 -right-5 bg-[#17181c] border border-[#1f2937] rounded-xl
                            px-4 py-3 shadow-2xl backdrop-blur-sm">
                    <div class="text-[10px] text-[#697d91] uppercase tracking-wider mb-1">Resolved windows</div>
                    <div class="text-sm font-bold text-white">2,734+</div>
                </div>
            </div>
        </div>
    </div>
</section>


{{-- ============================================================
     §2  LIVE STATS BAR (animated counters)
============================================================ --}}
<section class="border-y border-[#1f2937] bg-[#17181c]/50 py-0"
         x-data="{
            started: false,
            counters: [
                { label: 'Oracle ticks recorded', sub: 'And counting', target: 188203, val: 0, suffix: '' },
                { label: 'CLOB snapshots', sub: 'Order book depth', target: 3245835, val: 0, suffix: '' },
                { label: 'Resolved windows', sub: 'BTC · ETH · SOL', target: 2734, val: 0, suffix: '' },
                { label: 'Data uptime', sub: 'WebSocket feed', target: 99.9, val: 0, suffix: '%' },
            ],
            start() {
                if (this.started) return;
                this.started = true;
                this.counters.forEach(c => {
                    const steps = 60;
                    const inc = c.target / steps;
                    let i = 0;
                    const t = setInterval(() => {
                        i++;
                        c.val = Math.min(c.target, Math.round(inc * i * 100) / 100);
                        if (i >= steps) clearInterval(t);
                    }, 18);
                });
            }
         }"
         x-intersect="start()">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-[#1f2937]">
            <template x-for="c in counters" :key="c.label">
                <div class="px-8 py-7 text-center">
                    <div class="text-2xl sm:text-3xl font-extrabold text-white counter-num mb-1"
                         x-text="c.val >= 1000000 ? (c.val/1000000).toFixed(1)+'M' : c.val >= 1000 ? (c.val/1000).toFixed(c.val<10000?1:0)+'K' : c.val+c.suffix">
                    </div>
                    <div class="text-sm font-medium text-[#e5e5e5] mb-0.5" x-text="c.label"></div>
                    <div class="text-xs text-[#697d91]" x-text="c.sub"></div>
                </div>
            </template>
        </div>
    </div>
</section>


{{-- ============================================================
     §3  PROBLEM → SOLUTION
============================================================ --}}
<section class="py-28">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-3">The problem</div>
            <h2 class="text-3xl sm:text-4xl font-bold text-white leading-tight">
                Building on Polymarket data is hard.<br>
                <span class="text-[#697d91] font-normal">We did the hard part for you.</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            @foreach([
                [
                    'icon' => '<path d="M6 2H10L14 6V14H2V2H6" stroke="#ff6b6b" stroke-width="1.4" stroke-linejoin="round"/><path d="M10 2v4h4" stroke="#ff6b6b" stroke-width="1.4"/><path d="M8 7v4M8 12h.01" stroke="#ff6b6b" stroke-width="1.4" stroke-linecap="round"/>',
                    'problem' => 'No historical API',
                    'body' => "Polymarket's official API gives you live markets — not ticks, not snapshots, not features. Recording this yourself takes months of infrastructure work.",
                    'solution' => 'We\'ve been recording since 2026.',
                    'color' => '#ff6b6b',
                ],
                [
                    'icon' => '<path d="M2 12L5 5l3 4 2-3 3 6" stroke="#f97316" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="4" r="1.5" stroke="#f97316" stroke-width="1.2"/>',
                    'problem' => 'Raw prices aren\'t enough',
                    'body' => 'A price at a timestamp tells you nothing alone. You need TWAPs, volatility, CLOB imbalance, and momentum — computed across the exact window each market was open.',
                    'solution' => '40+ features. Pre-computed. Per window.',
                    'color' => '#f97316',
                ],
                [
                    'icon' => '<circle cx="8" cy="8" r="6" stroke="#a78bfa" stroke-width="1.4"/><path d="M8 5v3.5l2.5 1.5" stroke="#a78bfa" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>',
                    'problem' => 'Scrapers miss 95% of signals',
                    'body' => 'The Chainlink oracle updates every ~3 seconds. A scraper that runs every minute misses nearly all the price action happening inside each market window.',
                    'solution' => 'Direct WebSocket. ~3s cadence.',
                    'color' => '#a78bfa',
                ],
            ] as $card)
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-7 flex flex-col gap-5 hover:border-[#2e3841] transition-colors">

                {{-- Icon --}}
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: {{ $card['color'] }}15; border: 1px solid {{ $card['color'] }}30;">
                    <svg width="18" height="18" viewBox="0 0 16 16" fill="none">{!! $card['icon'] !!}</svg>
                </div>

                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-widest text-[#697d91] mb-1.5">The problem</div>
                    <h3 class="text-white font-semibold text-base mb-2">{{ $card['problem'] }}</h3>
                    <p class="text-[#697d91] text-sm leading-relaxed">{{ $card['body'] }}</p>
                </div>

                {{-- Solution pill --}}
                <div class="mt-auto pt-4 border-t border-[#1f2937]">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold"
                         style="color: {{ $card['color'] }}">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                            <path d="M2 6l3 3 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        {{ $card['solution'] }}
                    </div>
                </div>
            </div>
            @endforeach

        </div>
    </div>
</section>


{{-- ============================================================
     §4  FEATURE DEEP DIVE (tabbed)
============================================================ --}}
<section class="border-t border-[#1f2937] py-28"
         x-data="{ tab: 'windows' }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-12">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-3">Endpoints</div>
            <h2 class="text-3xl sm:text-4xl font-bold text-white">Everything in one API</h2>
            <p class="text-[#697d91] mt-3 max-w-xl mx-auto">Four endpoints. Every signal that matters for trading Polymarket binary markets.</p>
        </div>

        {{-- Tab bar --}}
        <div class="flex items-center justify-center gap-1 mb-12 bg-[#17181c] border border-[#1f2937]
                    rounded-xl p-1.5 w-fit mx-auto">
            @foreach([
                ['windows', 'Windows'],
                ['oracle',  'Oracle Ticks'],
                ['clob',    'CLOB Snapshots'],
                ['candles', '1m Candles'],
            ] as [$key, $label])
            <button @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'bg-[#0a0b10] text-[#e5e5e5] border border-[#2e3841] shadow-sm'
                        : 'text-[#697d91] hover:text-[#8a9ab0]'"
                    class="text-xs sm:text-sm font-medium px-4 py-2 rounded-lg transition-all duration-150">
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- Tab panels --}}
        @foreach([
            'windows' => [
                'route'  => 'GET /api/v1/windows',
                'title'  => 'Market Windows & ML Features',
                'desc'   => 'The core dataset. Every Polymarket binary market window — open/close timestamps, break price, final outcome, and 40+ pre-computed ML-ready features. This is the training data for prediction models.',
                'use'    => 'Use for: model training · backtesting · feature analysis',
                'fields' => ['id', 'asset', 'open_ts', 'close_ts', 'break_price_usd', 'outcome', 'oracle_dist_bp_at_1m', 'oracle_dist_bp_at_close', 'yes_bid_open', 'yes_ask_open', 'clob_imbalance_open', 'twap_1m_usd', 'vol_1m', 'momentum_3m', '+ 30 more'],
                'json'   => [
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
                'route'  => 'GET /api/v1/oracle-ticks',
                'title'  => 'Oracle Price Ticks',
                'desc'   => 'Raw Chainlink RTDS price recordings at ~3s cadence. Full millisecond-accurate price history for BTC, ETH, and SOL — the ground truth that determines every market outcome.',
                'use'    => 'Use for: price reconstruction · volatility calc · custom features',
                'fields' => ['id', 'asset_id', 'asset', 'price_usd', 'price_bp', 'ts'],
                'json'   => [
                    '"asset": <span class="text-[#f97316]">"BTC"</span>',
                    '"price_usd": <span class="text-[#26a05e]">84231.50</span>',
                    '"price_bp": <span class="text-[#26a05e]">8423150</span>',
                    '"ts": <span class="text-[#26a05e]">1774770300000</span>',
                ],
            ],
            'clob' => [
                'route'  => 'GET /api/v1/clob-snapshots',
                'title'  => 'CLOB Order Book Snapshots',
                'desc'   => 'Full Yes/No bid-ask order book state captured from the Polymarket CLOB WebSocket at every oracle tick. Spread, mid price, and imbalance ratio per market window per tick.',
                'use'    => 'Use for: market microstructure · liquidity analysis · spread modelling',
                'fields' => ['window_id', 'asset_id', 'yes_bid', 'yes_ask', 'no_bid', 'no_ask', 'ts'],
                'json'   => [
                    '"window_id": <span class="text-[#f97316]">"btc-updown-5m-1774770300"</span>',
                    '"yes_bid": <span class="text-[#26a05e]">0.48</span>',
                    '"yes_ask": <span class="text-[#26a05e]">0.50</span>',
                    '"no_bid": <span class="text-[#26a05e]">0.49</span>',
                    '"no_ask": <span class="text-[#26a05e]">0.52</span>',
                    '"ts": <span class="text-[#26a05e]">1774770300000</span>',
                ],
            ],
            'candles' => [
                'route'  => 'GET /api/v1/candles',
                'title'  => '1-Minute OHLCV Candles',
                'desc'   => 'Standard 1-minute candlestick data aggregated directly from oracle ticks. OHLCV per asset per minute — ready to plug into any charting library or technical analysis pipeline.',
                'use'    => 'Use for: technical analysis · chart rendering · momentum signals',
                'fields' => ['asset_id', 'asset', 'open_usd', 'high_usd', 'low_usd', 'close_usd', 'volume', 'ts'],
                'json'   => [
                    '"asset": <span class="text-[#f97316]">"BTC"</span>',
                    '"open_usd": <span class="text-[#26a05e]">84100.00</span>',
                    '"high_usd": <span class="text-[#26a05e]">84298.50</span>',
                    '"low_usd": <span class="text-[#26a05e]">84056.10</span>',
                    '"close_usd": <span class="text-[#26a05e]">84231.50</span>',
                    '"volume": <span class="text-[#26a05e]">0</span>',
                    '"ts": <span class="text-[#26a05e]">1774770300000</span>',
                ],
            ],
        ] as $key => $panel)
        <div x-show="tab === '{{ $key }}'" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-1 translate-y-0">
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
                        <div class="pl-4"><span class="text-[#8a9ab0]">"meta"</span><span class="text-[#697d91]">: {</span><span class="text-[#8a9ab0]">"total"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">2734</span><span class="text-[#697d91]">, </span><span class="text-[#8a9ab0]">"page"</span><span class="text-[#697d91]">:</span> <span class="text-[#26a05e]">1</span><span class="text-[#697d91]">}</span></div>
                        <div class="text-[#697d91]">}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

    </div>
</section>


{{-- ============================================================
     §5  WHY WE BUILT THIS  (founder story / credibility)
============================================================ --}}
<section class="border-t border-[#1f2937] py-28 relative overflow-hidden">
    <div class="absolute right-0 top-0 w-[500px] h-[500px] bg-[#0093fd]/[.025] rounded-full blur-[120px] pointer-events-none"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

            {{-- Left --}}
            <div>
                <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-3">Why we built this</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6 leading-tight">
                    We were tired of<br>building the same<br>data pipeline twice.
                </h2>
                <div class="space-y-4 text-[#697d91] text-base leading-relaxed">
                    <p>
                        Every time we wanted to run a new Polymarket strategy, we started the same way: scrape the Gamma API, try to reconstruct oracle prices, realise the timestamps didn't line up, start over.
                    </p>
                    <p>
                        So we built a proper recorder — connected directly to Chainlink's RTDS WebSocket and the Polymarket CLOB feed — and started capturing everything at source.
                    </p>
                    <p>
                        Now we're making that infrastructure available as an API, so you can spend your time on the interesting part: building strategies that actually work.
                    </p>
                </div>
            </div>

            {{-- Right: data pipeline viz --}}
            <div class="space-y-3">
                @foreach([
                    ['Chainlink RTDS', 'wss://ws-live-data.polymarket.com', '~3s price updates', '#0093fd', 'M3 8h10M9 4l4 4-4 4', true],
                    ['Polymarket CLOB', 'wss://ws-subscriptions-clob.polymarket.com', 'Yes/No bid-ask per tick', '#a78bfa', 'M2 5h12M2 9h12M2 13h8', true],
                    ['Gamma API', 'https://gamma-api.polymarket.com', 'Market discovery every 20s', '#26a05e', 'M8 2a6 6 0 100 12A6 6 0 008 2zM5 8h6M8 5v6', true],
                    ['PostgreSQL + TimescaleDB', 'Your queries', 'REST API — any language', '#f97316', 'M3 5h10a1 1 0 011 1v8a1 1 0 01-1 1H3a1 1 0 01-1-1V6a1 1 0 011-1zM7 5V3h2v2', false],
                ] as [$name, $url, $detail, $color, $icon, $live])
                <div class="flex items-start gap-4 bg-[#17181c] border border-[#1f2937] rounded-xl p-4 hover:border-[#2e3841] transition-colors">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                         style="background: {{ $color }}15; border: 1px solid {{ $color }}25;">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="{{ $icon }}" stroke="{{ $color }}" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2 mb-0.5">
                            <span class="text-sm font-semibold text-[#e5e5e5]">{{ $name }}</span>
                            @if($live)
                            <span class="flex-shrink-0 w-1.5 h-1.5 rounded-full animate-pulse" style="background: {{ $color }}"></span>
                            @endif
                        </div>
                        <div class="text-xs font-mono text-[#697d91] truncate mb-0.5">{{ $url }}</div>
                        <div class="text-xs text-[#697d91]">{{ $detail }}</div>
                    </div>
                </div>
                @if($live)
                <div class="flex justify-start pl-8">
                    <svg width="8" height="12" viewBox="0 0 8 12" fill="none" class="opacity-30">
                        <path d="M4 0v12M0 8l4 4 4-4" stroke="#697d91" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                @endif
                @endforeach
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
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <div class="text-xs font-semibold text-[#0093fd] uppercase tracking-[0.15em] mb-3">FAQ</div>
            <h2 class="text-3xl font-bold text-white">Questions we get asked</h2>
        </div>

        <div class="space-y-2">
            @foreach([
                [
                    'Q' => 'Where does the data come from?',
                    'A' => 'We connect directly to three live sources: Chainlink\'s RTDS WebSocket for oracle price feeds (the same prices Polymarket uses), the Polymarket CLOB WebSocket for order book data, and the Gamma API for market metadata and outcomes. Everything is recorded at the source — no secondary scraping.',
                ],
                [
                    'Q' => 'How far back does history go?',
                    'A' => 'We started live recording in early 2026. We also have session recordings from before launch that are being backfilled into the API. Free gets 7 days, Builder gets 90 days, Pro gets everything we have.',
                ],
                [
                    'Q' => 'What are "window features" exactly?',
                    'A' => 'A "window" is a single Polymarket binary market — e.g. "Will BTC be above $84,000 in 5 minutes?". For each resolved window we pre-compute 40+ ML-ready features: TWAPs at different intervals, oracle distance from break price, CLOB imbalance ratios, volatility, momentum, and more. These are the features you\'d otherwise spend days computing yourself.',
                ],
                [
                    'Q' => 'How accurate are the timestamps?',
                    'A' => 'Oracle ticks carry the timestamp from the Chainlink WebSocket message — millisecond accuracy. CLOB snapshots are timestamped when we receive the oracle tick they were captured alongside. There is a small, constant network latency but no batch-processing delay.',
                ],
                [
                    'Q' => 'Is this affiliated with Polymarket?',
                    'A' => 'No. This is a completely independent data service. We are not affiliated with, endorsed by, or associated with Polymarket, UMA Protocol, or any of their affiliates.',
                ],
                [
                    'Q' => 'Can I cancel anytime?',
                    'A' => 'Yes. Subscriptions are monthly. Cancel anytime from your billing dashboard and you\'ll retain access until the end of your current period. No questions asked.',
                ],
            ] as $faq)
            <div class="bg-[#17181c] border border-[#1f2937] rounded-xl overflow-hidden"
                 x-data="{ open: false }">
                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-6 py-4.5 text-left
                               hover:bg-[#1e2428] transition-colors group">
                    <span class="text-[#e5e5e5] font-medium text-sm pr-4">{{ $faq['Q'] }}</span>
                    <div class="flex-shrink-0 w-5 h-5 rounded-md bg-[#0a0b10] border border-[#2e3841]
                                flex items-center justify-center transition-all group-hover:border-[#697d91]"
                         :class="open ? 'bg-[#0093fd]/10 border-[#0093fd]/30' : ''">
                        <svg class="w-3 h-3 text-[#697d91] transition-transform duration-200"
                             :class="open ? 'rotate-45 text-[#0093fd]' : ''"
                             viewBox="0 0 12 12" fill="none">
                            <path d="M6 2v8M2 6h8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </div>
                </button>
                <div x-show="open" x-collapse>
                    <div class="px-6 pb-5 pt-1">
                        <p class="text-[#697d91] text-sm leading-relaxed">{{ $faq['A'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-8 text-center text-sm text-[#697d91]">
            Still have questions?
            <a href="mailto:hello@polymarketdata.io" class="text-[#0093fd] hover:text-[#007fd6] transition-colors ml-1">Email us →</a>
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
