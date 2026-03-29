@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-10">
        <div>
            <h1 class="text-2xl font-bold text-white">Dashboard</h1>
            <p class="text-[#697d91] text-sm mt-1">Welcome back, {{ $user->name }}</p>
        </div>

        {{-- Tier badge --}}
        @if ($user->is_admin)
            <span class="bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold px-3 py-1.5 rounded-full uppercase tracking-wider">Admin</span>
        @elseif ($user->tier === 'pro')
            <span class="bg-purple-500/10 border border-purple-500/30 text-purple-300 text-xs font-semibold px-3 py-1.5 rounded-full uppercase tracking-wider">Pro</span>
        @elseif ($user->tier === 'builder')
            <span class="bg-[#0093fd]/10 border border-[#0093fd]/30 text-[#0093fd] text-xs font-semibold px-3 py-1.5 rounded-full uppercase tracking-wider">Builder</span>
        @else
            <span class="bg-[#1e2428] border border-[#2e3841] text-[#697d91] text-xs font-semibold px-3 py-1.5 rounded-full uppercase tracking-wider">Free</span>
        @endif
    </div>

    {{-- API Key --}}
    <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-6 mb-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-[#e5e5e5]">API Key</h2>
            <form method="POST" action="{{ route('dashboard.regenerate') }}">
                @csrf
                <button
                    type="submit"
                    onclick="return confirm('This will invalidate your current API key. Continue?')"
                    class="text-xs font-medium text-[#697d91] border border-[#2e3841] px-3 py-1.5 rounded-lg hover:border-red-500/40 hover:text-red-400 transition-colors"
                >
                    Regenerate
                </button>
            </form>
        </div>

        @if (session('success'))
            <div class="bg-[#26a05e]/10 border border-[#26a05e]/20 text-[#26a05e] text-xs rounded-xl p-3 mb-3">
                {{ session('success') }}
            </div>
        @endif

        <div x-data="{ copied: false }" class="bg-[#0a0b10] border border-[#1f2937] rounded-xl p-3.5 flex items-center gap-3">
            <code class="text-[#0093fd] text-xs font-mono break-all flex-1 leading-relaxed">{{ $user->api_key }}</code>
            <button
                @click="navigator.clipboard.writeText('{{ $user->api_key }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="flex-shrink-0 text-xs font-medium px-3 py-1.5 rounded-lg border transition-colors"
                :class="copied ? 'bg-[#0093fd]/10 border-[#0093fd]/40 text-[#0093fd]' : 'bg-[#1e2428] border-[#2e3841] text-[#697d91] hover:text-[#e5e5e5] hover:border-[#697d91]'"
            >
                <span x-text="copied ? 'Copied!' : 'Copy'"></span>
            </button>
        </div>
    </div>

    {{-- Plan stats --}}
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-5">
            <div class="text-xs text-[#697d91] mb-2">Daily requests</div>
            <div class="text-2xl font-bold text-white">{{ number_format($tierInfo['daily_limit']) }}</div>
            <div class="text-xs text-[#697d91] mt-1">per day</div>
        </div>
        <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-5">
            <div class="text-xs text-[#697d91] mb-2">History access</div>
            <div class="text-2xl font-bold text-white">
                @if ($tierInfo['limit_days'] === null) ∞ @else {{ $tierInfo['limit_days'] }} @endif
            </div>
            <div class="text-xs text-[#697d91] mt-1">
                @if ($tierInfo['limit_days'] === null) unlimited @else days @endif
            </div>
        </div>
    </div>

    @if ($user->isFreeTier())
        <div class="bg-[#0093fd]/5 border border-[#0093fd]/20 rounded-2xl p-4 mb-4 flex items-center justify-between">
            <div>
                <div class="text-sm font-medium text-[#e5e5e5]">Unlock more data</div>
                <div class="text-xs text-[#697d91] mt-0.5">Upgrade to Builder for 10,000 req/day and 90 days history</div>
            </div>
            <a href="{{ route('billing') }}" class="flex-shrink-0 bg-[#0093fd] hover:bg-[#0080e0] text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors">
                Upgrade
            </a>
        </div>
    @elseif ($user->is_admin)
        <div class="bg-amber-500/5 border border-amber-500/20 rounded-2xl p-4 mb-4 flex items-center gap-3">
            <div class="w-2 h-2 rounded-full bg-amber-400 flex-shrink-0"></div>
            <div class="text-xs text-[#697d91]">Admin account — unlimited access, all data, no rate limits displayed below are defaults.</div>
        </div>
    @endif

    {{-- Quick start --}}
    <div
        x-data="{
            apiKey: '{{ $user->api_key }}',
            loading: false,
            response: null,
            status: null,
            async send() {
                this.loading = true;
                this.response = null;
                try {
                    const res = await fetch('{{ config('app.url') }}/api/v1/windows?limit=3', {
                        headers: { 'Authorization': 'Bearer ' + this.apiKey, 'Accept': 'application/json' }
                    });
                    this.status = res.status;
                    const json = await res.json();
                    this.response = JSON.stringify(json, null, 2);
                } catch (e) {
                    this.status = 0;
                    this.response = 'Network error: ' + e.message;
                }
                this.loading = false;
            }
        }"
        class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-6 mb-4"
    >
        <h2 class="text-sm font-semibold text-[#e5e5e5] mb-4">Quick start</h2>

        <div class="flex gap-2 mb-4">
            <input
                x-model="apiKey"
                type="text"
                placeholder="Paste your API key…"
                class="flex-1 bg-[#0a0b10] border border-[#1f2937] rounded-xl px-3.5 py-2 text-xs font-mono text-[#0093fd] placeholder-[#2e3841] focus:outline-none focus:border-[#0093fd]/40 transition-colors"
            />
            <button
                @click="send()"
                :disabled="loading || !apiKey"
                class="flex-shrink-0 bg-[#0093fd] hover:bg-[#0080e0] disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold px-4 py-2 rounded-xl transition-colors"
            >
                <span x-show="!loading">Send</span>
                <span x-show="loading">…</span>
            </button>
        </div>

        <div class="bg-[#0a0b10] rounded-xl overflow-hidden border border-[#1f2937]">
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-[#1f2937]">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono text-[#697d91]">GET /api/v1/windows?limit=3</span>
                </div>
                <span
                    x-show="status !== null"
                    class="text-xs font-mono px-2 py-0.5 rounded"
                    :class="status === 200 ? 'bg-[#26a05e]/10 text-[#26a05e]' : 'bg-red-500/10 text-red-400'"
                    x-text="status"
                ></span>
            </div>
            <pre
                class="p-4 text-xs font-mono text-[#697d91] whitespace-pre-wrap leading-6 max-h-64 overflow-y-auto"
                x-text="response || 'Hit Send to try the API with your key.'"
            ></pre>
        </div>

        <div class="mt-3">
            <a href="{{ route('docs') }}" class="text-xs text-[#697d91] hover:text-[#e5e5e5] transition-colors">
                View full API documentation →
            </a>
        </div>
    </div>

</div>
@endsection
