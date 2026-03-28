@extends('layouts.app')

@section('title', 'polymarket-data — Polymarket Oracle API')

@section('content')

{{-- Hero --}}
<section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-20">
    <div class="max-w-3xl">
        <div class="inline-flex items-center gap-2 bg-[#22c55e]/10 border border-[#22c55e]/20 rounded-full px-3 py-1 text-[#22c55e] text-xs font-mono mb-8">
            <span class="w-1.5 h-1.5 bg-[#22c55e] rounded-full animate-pulse"></span>
            live oracle recording
        </div>
        <h1 class="text-4xl sm:text-5xl font-bold font-mono text-white leading-tight mb-6">
            Polymarket<br>
            <span class="text-[#22c55e]">Oracle Data</span> API
        </h1>
        <p class="text-gray-400 text-lg font-mono leading-relaxed mb-10 max-w-2xl">
            Raw oracle ticks, CLOB snapshots, and pre-computed features for binary market research.
            Built for algo traders who need clean, timestamped market data.
        </p>
        <div class="flex flex-wrap gap-4">
            <a href="{{ route('register') }}"
               class="bg-[#22c55e] text-black font-mono font-bold px-6 py-3 rounded hover:bg-green-400 transition-colors text-sm">
                Get API Key &rarr;
            </a>
            <a href="{{ route('docs') }}"
               class="border border-gray-700 text-gray-300 font-mono px-6 py-3 rounded hover:border-gray-500 hover:text-white transition-colors text-sm">
                View Docs
            </a>
        </div>
    </div>

    {{-- Terminal preview --}}
    <div class="mt-16 bg-gray-900/50 border border-gray-800 rounded-lg overflow-hidden max-w-2xl">
        <div class="flex items-center gap-2 px-4 py-3 bg-gray-900 border-b border-gray-800">
            <div class="w-3 h-3 rounded-full bg-red-500/60"></div>
            <div class="w-3 h-3 rounded-full bg-yellow-500/60"></div>
            <div class="w-3 h-3 rounded-full bg-green-500/60"></div>
            <span class="ml-2 text-xs font-mono text-gray-500">terminal</span>
        </div>
        <div class="p-4 font-mono text-sm leading-relaxed">
            <div class="text-gray-500">$ curl https://api.polymarket-data.com/api/v1/windows \</div>
            <div class="text-gray-500 pl-4">-H "Authorization: Bearer sk_live_••••••••"</div>
            <div class="mt-3 text-[#22c55e]">{</div>
            <div class="text-gray-300 pl-4">"data": [{"market_id": "0x...", "window_start": "2024-01-15T00:00:00Z",</div>
            <div class="text-gray-300 pl-4">&nbsp;"yes_twap_1h": 0.6823, "oracle_vol_4h": 0.0041, ...}],</div>
            <div class="text-gray-300 pl-4">"meta": {"total": 1240, "page": 1}</div>
            <div class="text-[#22c55e]">}</div>
        </div>
    </div>
</section>

{{-- Features --}}
<section class="border-t border-gray-800 py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-12">Data Sources</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <div class="bg-gray-900/40 border border-gray-800 rounded-lg p-6 hover:border-[#22c55e]/30 transition-colors">
                <div class="text-[#22c55e] font-mono text-lg mb-3">/oracle-ticks</div>
                <h3 class="text-white font-mono font-semibold mb-2">Oracle Ticks</h3>
                <p class="text-gray-400 text-sm font-mono leading-relaxed">
                    Chainlink oracle price recordings every ~3 seconds. Raw timestamps, prices, and round IDs for every tracked asset.
                </p>
                <div class="mt-4 text-xs font-mono text-gray-600">~3s cadence &bull; Chainlink</div>
            </div>

            <div class="bg-gray-900/40 border border-gray-800 rounded-lg p-6 hover:border-[#22c55e]/30 transition-colors">
                <div class="text-[#22c55e] font-mono text-lg mb-3">/clob-snapshots</div>
                <h3 class="text-white font-mono font-semibold mb-2">CLOB Snapshots</h3>
                <p class="text-gray-400 text-sm font-mono leading-relaxed">
                    Yes/No bid-ask spreads captured at every oracle tick. Full order book depth snapshots for each binary market.
                </p>
                <div class="mt-4 text-xs font-mono text-gray-600">synced to oracle &bull; bid/ask/spread</div>
            </div>

            <div class="bg-gray-900/40 border border-gray-800 rounded-lg p-6 hover:border-[#22c55e]/30 transition-colors">
                <div class="text-[#22c55e] font-mono text-lg mb-3">/windows</div>
                <h3 class="text-white font-mono font-semibold mb-2">Window Features</h3>
                <p class="text-gray-400 text-sm font-mono leading-relaxed">
                    40+ pre-computed features per resolved market window. TWAPs, volatility, volume, momentum, and spread metrics.
                </p>
                <div class="mt-4 text-xs font-mono text-gray-600">40+ features &bull; per resolved market</div>
            </div>

        </div>
    </div>
</section>

{{-- Pricing --}}
<section class="border-t border-gray-800 py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-2">Pricing</h2>
        <p class="text-gray-500 font-mono text-sm mb-12">Pay for what you use. No hidden fees.</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            {{-- Free --}}
            <div class="bg-gray-900/40 border border-gray-800 rounded-lg p-6">
                <div class="text-xs font-mono text-gray-500 uppercase tracking-widest mb-4">Free</div>
                <div class="text-3xl font-mono font-bold text-white mb-1">$0</div>
                <div class="text-xs font-mono text-gray-500 mb-6">forever</div>
                <ul class="space-y-2 text-sm font-mono text-gray-400 mb-8">
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> 100 req/day</li>
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> 7 days history</li>
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> All endpoints</li>
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center border border-gray-700 text-gray-300 font-mono text-sm px-4 py-2 rounded hover:border-gray-500 transition-colors">
                    Get started
                </a>
            </div>

            {{-- Builder --}}
            <div class="bg-gray-900/40 border border-[#22c55e]/40 rounded-lg p-6 relative">
                <div class="absolute -top-3 left-6 bg-[#22c55e] text-black text-xs font-mono font-bold px-3 py-0.5 rounded-full">
                    popular
                </div>
                <div class="text-xs font-mono text-[#22c55e] uppercase tracking-widest mb-4">Builder</div>
                <div class="text-3xl font-mono font-bold text-white mb-1">$29</div>
                <div class="text-xs font-mono text-gray-500 mb-6">per month</div>
                <ul class="space-y-2 text-sm font-mono text-gray-400 mb-8">
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> 10,000 req/day</li>
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> 90 days history</li>
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> All endpoints</li>
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> Priority support</li>
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center bg-[#22c55e] text-black font-mono font-bold text-sm px-4 py-2 rounded hover:bg-green-400 transition-colors">
                    Start building
                </a>
            </div>

            {{-- Pro --}}
            <div class="bg-gray-900/40 border border-gray-800 rounded-lg p-6">
                <div class="text-xs font-mono text-gray-500 uppercase tracking-widest mb-4">Pro</div>
                <div class="text-3xl font-mono font-bold text-white mb-1">$99</div>
                <div class="text-xs font-mono text-gray-500 mb-6">per month</div>
                <ul class="space-y-2 text-sm font-mono text-gray-400 mb-8">
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> 100,000 req/day</li>
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> Full history</li>
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> CSV export</li>
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> Backtest endpoint</li>
                    <li class="flex items-center gap-2"><span class="text-[#22c55e]">&bull;</span> Dedicated support</li>
                </ul>
                <a href="{{ route('register') }}"
                   class="block text-center border border-gray-700 text-gray-300 font-mono text-sm px-4 py-2 rounded hover:border-gray-500 transition-colors">
                    Go pro
                </a>
            </div>

        </div>
    </div>
</section>

@endsection
