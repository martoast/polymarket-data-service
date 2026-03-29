@extends('layouts.app')

@section('title', 'Polymarket Data — Oracle API for Algo Traders')

@section('content')

{{-- Hero --}}
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-20">
    <div class="max-w-3xl">
        <div class="inline-flex items-center gap-2 bg-[#0093fd]/10 border border-[#0093fd]/20 rounded-full px-3 py-1.5 text-[#0093fd] text-xs font-medium mb-8">
            <span class="w-1.5 h-1.5 bg-[#0093fd] rounded-full animate-pulse"></span>
            Live oracle recording active
        </div>

        <h1 class="text-5xl sm:text-6xl font-extrabold text-white leading-[1.1] tracking-tight mb-6">
            Polymarket data<br>
            <span class="text-[#0093fd]">for serious traders</span>
        </h1>

        <p class="text-[#697d91] text-xl leading-relaxed mb-10 max-w-2xl font-normal">
            Raw oracle ticks, CLOB snapshots, and pre-computed features for Polymarket binary markets.
            Skip months of data collection — get a training-ready dataset on day one.
        </p>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('register') }}"
               class="inline-flex items-center gap-2 bg-[#0093fd] hover:bg-[#0080e0] text-white font-semibold px-6 py-3 rounded-xl transition-colors text-sm">
                Get API Key
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a href="{{ route('docs') }}"
               class="inline-flex items-center gap-2 bg-[#17181c] hover:bg-[#1e2428] border border-[#1f2937] text-[#e5e5e5] font-medium px-6 py-3 rounded-xl transition-colors text-sm">
                View Docs
            </a>
        </div>

        <p class="mt-8 text-xs text-[#2e3841]">
            Not affiliated with, endorsed by, or associated with Polymarket. Independent third-party data service.
            <a href="{{ route('terms') }}" class="hover:text-[#697d91] transition-colors ml-1">Terms</a> ·
            <a href="{{ route('privacy') }}" class="hover:text-[#697d91] transition-colors ml-1">Privacy</a>
        </p>
    </div>

    {{-- Code preview --}}
    <div class="mt-16 rounded-2xl overflow-hidden border border-[#1f2937] max-w-2xl">
        <div class="flex items-center gap-2 px-5 py-3.5 bg-[#17181c] border-b border-[#1f2937]">
            <div class="w-3 h-3 rounded-full bg-[#2e3841]"></div>
            <div class="w-3 h-3 rounded-full bg-[#2e3841]"></div>
            <div class="w-3 h-3 rounded-full bg-[#2e3841]"></div>
            <span class="ml-3 text-xs font-mono text-[#697d91]">bash</span>
        </div>
        <div class="p-5 bg-[#0a0b10] font-mono text-sm leading-7">
            <div class="text-[#697d91]">$ curl https://api.polymarket-data.com/api/v1/windows \</div>
            <div class="text-[#697d91] pl-4">-H <span class="text-[#0093fd]">"Authorization: Bearer sk_live_••••"</span></div>
            <div class="mt-3 text-[#2e3841]">{</div>
            <div class="text-[#e5e5e5] pl-4"><span class="text-[#697d91]">"data":</span> [{</div>
            <div class="text-[#e5e5e5] pl-8"><span class="text-[#697d91]">"id":</span> <span class="text-[#0093fd]">"btc-updown-5m-1710000000"</span>,</div>
            <div class="text-[#e5e5e5] pl-8"><span class="text-[#697d91]">"oracle_dist_bp_at_1m":</span> <span class="text-[#26a05e]">142</span>,</div>
            <div class="text-[#e5e5e5] pl-8"><span class="text-[#697d91]">"outcome":</span> <span class="text-[#0093fd]">"YES"</span></div>
            <div class="text-[#e5e5e5] pl-4">}]</div>
            <div class="text-[#2e3841]">}</div>
        </div>
    </div>
</section>

{{-- Stats bar --}}
<section class="border-y border-[#1f2937] py-8 bg-[#17181c]/50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-3xl font-bold text-white mb-1">~3s</div>
                <div class="text-sm text-[#697d91]">Oracle tick cadence</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-white mb-1">40+</div>
                <div class="text-sm text-[#697d91]">Pre-computed features</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-white mb-1">BTC/ETH/SOL</div>
                <div class="text-sm text-[#697d91]">Tracked assets</div>
            </div>
            <div>
                <div class="text-3xl font-bold text-white mb-1">100%</div>
                <div class="text-sm text-[#697d91]">Gamma-verified outcomes</div>
            </div>
        </div>
    </div>
</section>

{{-- Data sources --}}
<section class="py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12">
            <div class="text-xs font-medium text-[#0093fd] uppercase tracking-widest mb-3">Data Sources</div>
            <h2 class="text-3xl font-bold text-white">Everything you need to build</h2>
            <p class="text-[#697d91] mt-3 max-w-xl">Clean, timestamped market data from Polymarket binary markets — ready to plug into any model.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-6 hover:border-[#0093fd]/30 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-[#0093fd]/10 border border-[#0093fd]/20 flex items-center justify-center mb-4 group-hover:bg-[#0093fd]/20 transition-colors">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M9 2v14M2 9h14M4.5 4.5l9 9M13.5 4.5l-9 9" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                <div class="font-mono text-xs text-[#0093fd] mb-2">/oracle/ticks</div>
                <h3 class="text-white font-semibold text-base mb-2">Oracle Ticks</h3>
                <p class="text-[#697d91] text-sm leading-relaxed">
                    Chainlink oracle price recordings every ~3 seconds. Raw timestamps and prices for every tracked asset.
                </p>
                <div class="mt-4 text-xs text-[#2e3841] font-mono">Chainlink · ~3s cadence</div>
            </div>

            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-6 hover:border-[#0093fd]/30 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-[#0093fd]/10 border border-[#0093fd]/20 flex items-center justify-center mb-4 group-hover:bg-[#0093fd]/20 transition-colors">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><rect x="2" y="5" width="14" height="8" rx="2" stroke="#0093fd" stroke-width="1.5"/><path d="M6 5V4a1 1 0 011-1h4a1 1 0 011 1v1" stroke="#0093fd" stroke-width="1.5"/></svg>
                </div>
                <div class="font-mono text-xs text-[#0093fd] mb-2">/clob/snapshots</div>
                <h3 class="text-white font-semibold text-base mb-2">CLOB Snapshots</h3>
                <p class="text-[#697d91] text-sm leading-relaxed">
                    Yes/No bid-ask spreads captured at every oracle tick. Full order book depth for each binary market.
                </p>
                <div class="mt-4 text-xs text-[#2e3841] font-mono">Synced to oracle · bid/ask/spread</div>
            </div>

            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-6 hover:border-[#0093fd]/30 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-[#0093fd]/10 border border-[#0093fd]/20 flex items-center justify-center mb-4 group-hover:bg-[#0093fd]/20 transition-colors">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M3 12l4-4 3 3 5-6" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="font-mono text-xs text-[#0093fd] mb-2">/windows</div>
                <h3 class="text-white font-semibold text-base mb-2">Window Features</h3>
                <p class="text-[#697d91] text-sm leading-relaxed">
                    40+ pre-computed features per resolved market window. TWAPs, volatility, momentum, and spread metrics.
                </p>
                <div class="mt-4 text-xs text-[#2e3841] font-mono">40+ features · per resolved market</div>
            </div>

        </div>
    </div>
</section>

{{-- Pricing --}}
<section class="border-t border-[#1f2937] py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12">
            <div class="text-xs font-medium text-[#0093fd] uppercase tracking-widest mb-3">Pricing</div>
            <h2 class="text-3xl font-bold text-white">Pay for what you use</h2>
            <p class="text-[#697d91] mt-3">No hidden fees. Cancel anytime.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- Free --}}
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-6">
                <div class="text-sm font-medium text-[#697d91] mb-4">Free</div>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-white">$0</span>
                    <span class="text-[#697d91] text-sm ml-1">/ month</span>
                </div>
                <ul class="space-y-3 text-sm text-[#697d91] mb-8">
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        100 requests / day
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        7 days history
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        All endpoints
                    </li>
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center bg-[#1e2428] hover:bg-[#242b32] border border-[#2e3841] text-[#e5e5e5] font-medium px-4 py-2.5 rounded-xl transition-colors text-sm">
                    Get started
                </a>
            </div>

            {{-- Builder — highlighted --}}
            <div class="bg-[#17181c] border border-[#0093fd]/40 rounded-2xl p-6 relative">
                <div class="absolute -top-3 left-5 bg-[#0093fd] text-white text-xs font-semibold px-3 py-1 rounded-full">
                    Most popular
                </div>
                <div class="text-sm font-medium text-[#0093fd] mb-4">Builder</div>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-white">$29</span>
                    <span class="text-[#697d91] text-sm ml-1">/ month</span>
                </div>
                <ul class="space-y-3 text-sm text-[#697d91] mb-8">
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        10,000 requests / day
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        90 days history
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        All endpoints
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Priority support
                    </li>
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center bg-[#0093fd] hover:bg-[#0080e0] text-white font-semibold px-4 py-2.5 rounded-xl transition-colors text-sm">
                    Start building
                </a>
            </div>

            {{-- Pro --}}
            <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-6">
                <div class="text-sm font-medium text-[#697d91] mb-4">Pro</div>
                <div class="mb-6">
                    <span class="text-4xl font-bold text-white">$99</span>
                    <span class="text-[#697d91] text-sm ml-1">/ month</span>
                </div>
                <ul class="space-y-3 text-sm text-[#697d91] mb-8">
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        100,000 requests / day
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Full history (unlimited)
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        CSV / SQLite export
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Backtest endpoint
                    </li>
                    <li class="flex items-center gap-2.5">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l3.5 3.5L13 4.5" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Dedicated support
                    </li>
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center bg-[#1e2428] hover:bg-[#242b32] border border-[#2e3841] text-[#e5e5e5] font-medium px-4 py-2.5 rounded-xl transition-colors text-sm">
                    Go pro
                </a>
            </div>

        </div>
    </div>
</section>

{{-- CTA --}}
<section class="border-t border-[#1f2937] py-24">
    <div class="max-w-2xl mx-auto px-4 text-center">
        <h2 class="text-4xl font-bold text-white mb-4">Start with free data today</h2>
        <p class="text-[#697d91] mb-8">No credit card required. Get your API key in seconds.</p>
        <a href="{{ route('register') }}"
           class="inline-flex items-center gap-2 bg-[#0093fd] hover:bg-[#0080e0] text-white font-semibold px-8 py-3.5 rounded-xl transition-colors text-sm">
            Create free account
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>
</section>

@endsection
