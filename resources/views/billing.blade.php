@extends('layouts.app')

@section('title', 'Billing')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <div class="mb-10">
        <h1 class="text-2xl font-bold text-white">Billing</h1>
        <p class="text-[#697d91] text-sm mt-1">Manage your plan and subscription</p>
    </div>

    @if (request('checkout') === 'success')
        <div class="bg-[#26a05e]/10 border border-[#26a05e]/20 text-[#26a05e] text-sm rounded-xl p-4 mb-8">
            Subscription activated. Your plan has been updated.
        </div>
    @elseif (request('checkout') === 'cancelled')
        <div class="bg-[#17181c] border border-[#1f2937] text-[#697d91] text-sm rounded-xl p-4 mb-8">
            Checkout cancelled. No changes were made.
        </div>
    @endif

    {{-- Current plan --}}
    <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-6 mb-8">
        <h2 class="text-sm font-semibold text-[#e5e5e5] mb-4">Current plan</h2>
        <div class="flex items-center gap-4">
            @if ($user->tier === 'pro')
                <span class="bg-purple-500/10 border border-purple-500/30 text-purple-300 text-xs font-semibold px-3 py-1.5 rounded-full uppercase tracking-wider">Pro</span>
                <span class="text-[#697d91] text-sm">100,000 req/day · Full history · CSV export · Backtest</span>
            @elseif ($user->tier === 'builder')
                <span class="bg-[#0093fd]/10 border border-[#0093fd]/30 text-[#0093fd] text-xs font-semibold px-3 py-1.5 rounded-full uppercase tracking-wider">Builder</span>
                <span class="text-[#697d91] text-sm">10,000 req/day · 90 days history</span>
            @else
                <span class="bg-[#1e2428] border border-[#2e3841] text-[#697d91] text-xs font-semibold px-3 py-1.5 rounded-full uppercase tracking-wider">Free</span>
                <span class="text-[#697d91] text-sm">100 req/day · 7 days history</span>
            @endif
        </div>
        @if ($subscription && $subscription->stripe_status)
            <div class="mt-4 pt-4 border-t border-[#1f2937] text-xs text-[#697d91]">
                Subscription status: <span class="text-[#e5e5e5]">{{ $subscription->stripe_status }}</span>
            </div>
        @endif
    </div>

    {{-- Plans --}}
    <h2 class="text-sm font-semibold text-[#e5e5e5] mb-5">Plans</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

        {{-- Free --}}
        <div class="bg-[#17181c] border {{ $user->isFreeTier() ? 'border-[#0093fd]/30' : 'border-[#1f2937]' }} rounded-2xl p-5">
            @if ($user->isFreeTier())
                <div class="text-xs font-semibold text-[#0093fd] bg-[#0093fd]/10 border border-[#0093fd]/20 rounded-full px-2.5 py-0.5 inline-block mb-3">Current plan</div>
            @endif
            <div class="text-sm font-medium text-[#697d91] mb-3">Free</div>
            <div class="mb-5"><span class="text-2xl font-bold text-white">$0</span><span class="text-[#697d91] text-sm ml-1">/mo</span></div>
            <ul class="space-y-2 text-xs text-[#697d91] mb-5">
                <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3L11.5 3.5" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>100 req/day</li>
                <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3L11.5 3.5" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>7 days history</li>
                <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3L11.5 3.5" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>All endpoints</li>
            </ul>
            @if ($user->isFreeTier())
                <div class="text-xs text-center text-[#697d91] py-2">Active</div>
            @else
                <a href="{{ route('billing.portal') }}" class="block text-center text-xs font-medium text-[#697d91] bg-[#1e2428] border border-[#2e3841] px-3 py-2 rounded-lg hover:border-[#697d91] transition-colors">Manage</a>
            @endif
        </div>

        {{-- Builder --}}
        <div class="bg-[#17181c] border {{ $user->isBuilderTier() ? 'border-[#0093fd]/40' : 'border-[#0093fd]/20' }} rounded-2xl p-5 relative">
            @if (! $user->isBuilderTier())
                <div class="absolute -top-3 left-5 bg-[#0093fd] text-white text-xs font-semibold px-3 py-0.5 rounded-full">Popular</div>
            @endif
            @if ($user->isBuilderTier())
                <div class="text-xs font-semibold text-[#0093fd] bg-[#0093fd]/10 border border-[#0093fd]/20 rounded-full px-2.5 py-0.5 inline-block mb-3">Current plan</div>
            @endif
            <div class="text-sm font-medium text-[#0093fd] mb-3">Builder</div>
            <div class="mb-5"><span class="text-2xl font-bold text-white">$29</span><span class="text-[#697d91] text-sm ml-1">/mo</span></div>
            <ul class="space-y-2 text-xs text-[#697d91] mb-5">
                <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3L11.5 3.5" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>10,000 req/day</li>
                <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3L11.5 3.5" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>90 days history</li>
                <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3L11.5 3.5" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>Priority support</li>
            </ul>
            @if ($user->isBuilderTier())
                <div class="text-xs text-center text-[#697d91] py-2">Active</div>
            @elseif ($user->isFreeTier())
                <form method="POST" action="{{ route('billing.checkout') }}">
                    @csrf <input type="hidden" name="plan" value="builder" />
                    <button type="submit" class="w-full bg-[#0093fd] hover:bg-[#0080e0] text-white font-semibold text-xs px-3 py-2.5 rounded-lg transition-colors">Upgrade</button>
                </form>
            @else
                <a href="{{ route('billing.portal') }}" class="block text-center text-xs font-medium text-[#697d91] bg-[#1e2428] border border-[#2e3841] px-3 py-2 rounded-lg hover:border-[#697d91] transition-colors">Manage</a>
            @endif
        </div>

        {{-- Pro --}}
        <div class="bg-[#17181c] border {{ $user->isProTier() ? 'border-purple-500/30' : 'border-[#1f2937]' }} rounded-2xl p-5">
            @if ($user->isProTier())
                <div class="text-xs font-semibold text-purple-300 bg-purple-500/10 border border-purple-500/20 rounded-full px-2.5 py-0.5 inline-block mb-3">Current plan</div>
            @endif
            <div class="text-sm font-medium text-[#697d91] mb-3">Pro</div>
            <div class="mb-5"><span class="text-2xl font-bold text-white">$99</span><span class="text-[#697d91] text-sm ml-1">/mo</span></div>
            <ul class="space-y-2 text-xs text-[#697d91] mb-5">
                <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3L11.5 3.5" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>100,000 req/day</li>
                <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3L11.5 3.5" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>Full history</li>
                <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3L11.5 3.5" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>CSV / SQLite export</li>
                <li class="flex items-center gap-2"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3 3L11.5 3.5" stroke="#0093fd" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>Backtest endpoint</li>
            </ul>
            @if ($user->isProTier())
                <div class="text-xs text-center text-[#697d91] py-2">Active</div>
            @else
                <form method="POST" action="{{ route('billing.checkout') }}">
                    @csrf <input type="hidden" name="plan" value="pro" />
                    <button type="submit" class="w-full bg-[#1e2428] hover:bg-[#242b32] border border-purple-500/30 hover:border-purple-500/50 text-purple-300 font-semibold text-xs px-3 py-2.5 rounded-lg transition-colors">Upgrade</button>
                </form>
            @endif
        </div>

    </div>

    {{-- Stripe portal --}}
    @if ($subscription)
        <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-6">
            <h2 class="text-sm font-semibold text-[#e5e5e5] mb-2">Subscription management</h2>
            <p class="text-[#697d91] text-sm mb-4">Update payment method, view invoices, or cancel via the Stripe portal.</p>
            <a href="{{ route('billing.portal') }}"
               class="inline-flex items-center gap-2 bg-[#1e2428] hover:bg-[#242b32] border border-[#2e3841] text-[#e5e5e5] font-medium text-sm px-4 py-2.5 rounded-xl transition-colors">
                Manage subscription →
            </a>
        </div>
    @endif

</div>
@endsection
