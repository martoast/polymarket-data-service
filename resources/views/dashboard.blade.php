@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Page header --}}
    <div class="mb-10">
        <h1 class="text-2xl font-mono font-bold text-white">Dashboard</h1>
        <p class="text-gray-500 font-mono text-sm mt-1">Welcome back, {{ $user->name }}</p>
    </div>

    {{-- API Key Section --}}
    <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-6 mb-6">
        <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-4">API Key</h2>

        @if ($apiToken)
            {{-- Show full token right after login/register --}}
            <div x-data="{ copied: false }" class="space-y-3">
                <div class="bg-[#0f0f0f] border border-[#22c55e]/30 rounded p-3 flex items-center justify-between gap-3">
                    <code class="text-[#22c55e] text-xs font-mono break-all flex-1">{{ $apiToken }}</code>
                    <button
                        @click="navigator.clipboard.writeText('{{ $apiToken }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="flex-shrink-0 border border-gray-700 text-xs font-mono px-3 py-1.5 rounded hover:border-[#22c55e] transition-colors"
                        :class="copied ? 'text-[#22c55e] border-[#22c55e]' : 'text-gray-400'"
                    >
                        <span x-text="copied ? 'copied!' : 'copy'"></span>
                    </button>
                </div>
                <div class="flex items-start gap-2 text-xs font-mono text-yellow-400/80 bg-yellow-900/10 border border-yellow-500/20 rounded p-3">
                    <span class="mt-0.5">!</span>
                    <span>Save this key — it won't be shown again. Store it securely.</span>
                </div>
            </div>
        @else
            {{-- Masked placeholder with regenerate option --}}
            <div class="bg-[#0f0f0f] border border-gray-800 rounded p-3 flex items-center justify-between gap-3 mb-3">
                <code class="text-gray-500 text-xs font-mono">sk_live_••••••••••••••••••••••••••••••••••••</code>
                <span class="text-xs font-mono text-gray-600">hidden</span>
            </div>
            <form method="POST" action="{{ route('dashboard.regenerate') }}">
                @csrf
                <button
                    type="submit"
                    onclick="return confirm('This will invalidate your current API key. Continue?')"
                    class="text-xs font-mono text-gray-400 border border-gray-700 px-3 py-1.5 rounded hover:border-red-500/50 hover:text-red-400 transition-colors"
                >
                    Regenerate Key
                </button>
            </form>
        @endif
    </div>

    {{-- Plan Info --}}
    <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest">Your Plan</h2>
            <a href="{{ route('billing') }}" class="text-xs font-mono text-gray-500 hover:text-gray-300 transition-colors">manage &rarr;</a>
        </div>

        <div class="flex items-center gap-3 mb-5">
            @if ($user->tier === 'pro')
                <span class="bg-purple-500/20 border border-purple-500/40 text-purple-300 text-xs font-mono font-bold px-3 py-1 rounded-full uppercase tracking-widest">Pro</span>
            @elseif ($user->tier === 'builder')
                <span class="bg-blue-500/20 border border-blue-500/40 text-blue-300 text-xs font-mono font-bold px-3 py-1 rounded-full uppercase tracking-widest">Builder</span>
            @else
                <span class="bg-gray-700/50 border border-gray-600 text-gray-300 text-xs font-mono font-bold px-3 py-1 rounded-full uppercase tracking-widest">Free</span>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="bg-[#0f0f0f] rounded p-3">
                <div class="text-xs font-mono text-gray-500 mb-1">Daily requests</div>
                <div class="text-white font-mono font-semibold text-sm">{{ number_format($tierInfo['daily_limit']) }}</div>
            </div>
            <div class="bg-[#0f0f0f] rounded p-3">
                <div class="text-xs font-mono text-gray-500 mb-1">History access</div>
                <div class="text-white font-mono font-semibold text-sm">
                    @if ($tierInfo['limit_days'] === null)
                        Unlimited
                    @else
                        {{ $tierInfo['limit_days'] }} days
                    @endif
                </div>
            </div>
        </div>

        @if ($user->isFreeTier())
            <div class="mt-4 pt-4 border-t border-gray-800">
                <a href="{{ route('billing') }}" class="inline-flex items-center gap-2 text-xs font-mono text-[#22c55e] hover:text-green-400 transition-colors">
                    Upgrade for more requests &amp; history &rarr;
                </a>
            </div>
        @endif
    </div>

    {{-- Quick Start --}}
    <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-6 mb-6">
        <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-4">Quick Start</h2>
        <div class="bg-[#0f0f0f] border border-gray-800 rounded overflow-hidden">
            <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-900/70 border-b border-gray-800">
                <span class="text-xs font-mono text-gray-500">curl</span>
            </div>
            <div class="p-4 text-xs font-mono leading-relaxed">
                <div class="text-gray-400">curl {{ config('app.url') }}/api/v1/windows \</div>
                <div class="text-gray-400 pl-4">-H <span class="text-[#22c55e]">"Authorization: Bearer YOUR_API_KEY"</span></div>
            </div>
        </div>
        <div class="mt-3">
            <a href="{{ route('docs') }}" class="text-xs font-mono text-gray-500 hover:text-gray-300 transition-colors">
                View full API documentation &rarr;
            </a>
        </div>
    </div>

    {{-- Data Status --}}
    <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-6">
        <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-4">Data Status</h2>
        @if ($lastOracleTs)
            @php
                $ts = \Carbon\Carbon::parse($lastOracleTs);
                $diffMinutes = $ts->diffInMinutes(now());
                $isStale = $diffMinutes > 10;
            @endphp
            <div class="flex items-center gap-3 mb-2">
                <div class="flex items-center gap-2 text-sm font-mono">
                    <span class="w-2 h-2 rounded-full {{ $isStale ? 'bg-yellow-500' : 'bg-[#22c55e] animate-pulse' }}"></span>
                    <span class="{{ $isStale ? 'text-yellow-400' : 'text-[#22c55e]' }}">
                        Recording: {{ $isStale ? 'Stale (>'.$diffMinutes.'min)' : 'Live' }}
                    </span>
                </div>
            </div>
            <div class="text-xs font-mono text-gray-500">
                Last oracle tick: {{ $ts->format('Y-m-d H:i:s') }} UTC
                ({{ $ts->diffForHumans() }})
            </div>
        @else
            <div class="flex items-center gap-2 text-sm font-mono text-gray-500">
                <span class="w-2 h-2 rounded-full bg-gray-600"></span>
                No oracle data yet
            </div>
        @endif
    </div>

</div>
@endsection
