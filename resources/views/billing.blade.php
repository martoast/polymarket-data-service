@extends('layouts.app')

@section('title', 'Billing')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Header --}}
    <div class="mb-10">
        <h1 class="text-2xl font-mono font-bold text-white">Billing</h1>
        <p class="text-gray-500 font-mono text-sm mt-1">Manage your plan and subscription</p>
    </div>

    {{-- Checkout feedback --}}
    @if (request('checkout') === 'success')
        <div class="bg-green-900/30 border border-[#22c55e]/30 text-green-300 text-sm font-mono rounded p-4 mb-8">
            Subscription activated successfully. Your tier has been updated.
        </div>
    @elseif (request('checkout') === 'cancelled')
        <div class="bg-gray-900/50 border border-gray-700 text-gray-400 text-sm font-mono rounded p-4 mb-8">
            Checkout cancelled. No changes were made.
        </div>
    @endif

    {{-- Current Plan --}}
    <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-6 mb-8">
        <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-4">Current Plan</h2>
        <div class="flex items-center gap-4">
            @if ($user->tier === 'pro')
                <span class="bg-purple-500/20 border border-purple-500/40 text-purple-300 text-xs font-mono font-bold px-3 py-1 rounded-full uppercase tracking-widest">Pro</span>
                <span class="text-gray-400 font-mono text-sm">100,000 req/day &bull; Full history &bull; CSV export &bull; Backtest</span>
            @elseif ($user->tier === 'builder')
                <span class="bg-blue-500/20 border border-blue-500/40 text-blue-300 text-xs font-mono font-bold px-3 py-1 rounded-full uppercase tracking-widest">Builder</span>
                <span class="text-gray-400 font-mono text-sm">10,000 req/day &bull; 90 days history</span>
            @else
                <span class="bg-gray-700/50 border border-gray-600 text-gray-300 text-xs font-mono font-bold px-3 py-1 rounded-full uppercase tracking-widest">Free</span>
                <span class="text-gray-400 font-mono text-sm">100 req/day &bull; 7 days history</span>
            @endif
        </div>

        @if ($subscription && $subscription->stripe_status)
            <div class="mt-4 pt-4 border-t border-gray-800 text-xs font-mono text-gray-500">
                Subscription status: <span class="text-gray-300">{{ $subscription->stripe_status }}</span>
            </div>
        @endif
    </div>

    {{-- Plans --}}
    <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-6">Plans</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">

        {{-- Free --}}
        <div class="bg-gray-900/40 border {{ $user->isFreeTier() ? 'border-[#22c55e]/30' : 'border-gray-800' }} rounded-lg p-5">
            @if ($user->isFreeTier())
                <div class="text-xs font-mono text-[#22c55e] bg-[#22c55e]/10 border border-[#22c55e]/20 rounded-full px-2 py-0.5 inline-block mb-3">current plan</div>
            @endif
            <div class="text-sm font-mono text-gray-400 uppercase tracking-widest mb-2">Free</div>
            <div class="text-2xl font-mono font-bold text-white mb-4">$0<span class="text-sm text-gray-500 font-normal">/mo</span></div>
            <ul class="space-y-1.5 text-xs font-mono text-gray-400 mb-5">
                <li><span class="text-[#22c55e]">&bull;</span> 100 req/day</li>
                <li><span class="text-[#22c55e]">&bull;</span> 7 days history</li>
                <li><span class="text-[#22c55e]">&bull;</span> All endpoints</li>
            </ul>
            @if ($user->isFreeTier())
                <div class="text-xs font-mono text-gray-600 text-center py-2">Active</div>
            @else
                <a href="{{ route('billing.portal') }}" class="block text-center text-xs font-mono text-gray-500 border border-gray-700 px-3 py-2 rounded hover:border-gray-500 transition-colors">
                    Manage
                </a>
            @endif
        </div>

        {{-- Builder --}}
        <div class="bg-gray-900/40 border {{ $user->isBuilderTier() ? 'border-[#22c55e]/30' : 'border-gray-800' }} rounded-lg p-5">
            @if ($user->isBuilderTier())
                <div class="text-xs font-mono text-[#22c55e] bg-[#22c55e]/10 border border-[#22c55e]/20 rounded-full px-2 py-0.5 inline-block mb-3">current plan</div>
            @endif
            <div class="text-sm font-mono text-blue-400 uppercase tracking-widest mb-2">Builder</div>
            <div class="text-2xl font-mono font-bold text-white mb-4">$29<span class="text-sm text-gray-500 font-normal">/mo</span></div>
            <ul class="space-y-1.5 text-xs font-mono text-gray-400 mb-5">
                <li><span class="text-[#22c55e]">&bull;</span> 10,000 req/day</li>
                <li><span class="text-[#22c55e]">&bull;</span> 90 days history</li>
                <li><span class="text-[#22c55e]">&bull;</span> Priority support</li>
            </ul>
            @if ($user->isBuilderTier())
                <div class="text-xs font-mono text-gray-600 text-center py-2">Active</div>
            @elseif ($user->isFreeTier())
                <form method="POST" action="{{ route('billing.checkout') }}">
                    @csrf
                    <input type="hidden" name="plan" value="builder" />
                    <button type="submit" class="w-full bg-[#22c55e] text-black font-mono font-bold text-xs px-3 py-2 rounded hover:bg-green-400 transition-colors">
                        Upgrade
                    </button>
                </form>
            @else
                <a href="{{ route('billing.portal') }}" class="block text-center text-xs font-mono text-gray-500 border border-gray-700 px-3 py-2 rounded hover:border-gray-500 transition-colors">
                    Manage
                </a>
            @endif
        </div>

        {{-- Pro --}}
        <div class="bg-gray-900/40 border {{ $user->isProTier() ? 'border-[#22c55e]/30' : 'border-gray-800' }} rounded-lg p-5">
            @if ($user->isProTier())
                <div class="text-xs font-mono text-[#22c55e] bg-[#22c55e]/10 border border-[#22c55e]/20 rounded-full px-2 py-0.5 inline-block mb-3">current plan</div>
            @endif
            <div class="text-sm font-mono text-purple-400 uppercase tracking-widest mb-2">Pro</div>
            <div class="text-2xl font-mono font-bold text-white mb-4">$99<span class="text-sm text-gray-500 font-normal">/mo</span></div>
            <ul class="space-y-1.5 text-xs font-mono text-gray-400 mb-5">
                <li><span class="text-[#22c55e]">&bull;</span> 100,000 req/day</li>
                <li><span class="text-[#22c55e]">&bull;</span> Full history</li>
                <li><span class="text-[#22c55e]">&bull;</span> CSV export</li>
                <li><span class="text-[#22c55e]">&bull;</span> Backtest endpoint</li>
            </ul>
            @if ($user->isProTier())
                <div class="text-xs font-mono text-gray-600 text-center py-2">Active</div>
            @elseif (! $user->isProTier())
                <form method="POST" action="{{ route('billing.checkout') }}">
                    @csrf
                    <input type="hidden" name="plan" value="pro" />
                    <button type="submit" class="w-full border border-purple-500/40 text-purple-300 font-mono font-bold text-xs px-3 py-2 rounded hover:bg-purple-500/10 transition-colors">
                        Upgrade
                    </button>
                </form>
            @endif
        </div>

    </div>

    {{-- Subscription Management --}}
    @if ($subscription)
        <div class="bg-gray-900/50 border border-gray-800 rounded-lg p-6">
            <h2 class="text-sm font-mono text-[#22c55e] uppercase tracking-widest mb-4">Subscription Management</h2>
            <p class="text-gray-400 font-mono text-sm mb-4">
                Manage payment methods, view invoices, or cancel your subscription via the Stripe portal.
            </p>
            <a href="{{ route('billing.portal') }}"
               class="inline-flex items-center gap-2 border border-gray-700 text-gray-300 font-mono text-sm px-4 py-2 rounded hover:border-gray-500 hover:text-white transition-colors">
                Manage Subscription &rarr;
            </a>
        </div>
    @endif

</div>
@endsection
