@extends('layouts.app')

@section('title', 'Verify Email')

@section('content')
<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm text-center">

        <div class="w-16 h-16 rounded-2xl bg-[#0093fd]/10 border border-[#0093fd]/20 flex items-center justify-center mx-auto mb-6">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                <path d="M4 8l10 7 10-7" stroke="#0093fd" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="4" y="6" width="20" height="16" rx="2" stroke="#0093fd" stroke-width="1.5"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-white mb-2">Check your inbox</h1>
        <p class="text-[#697d91] text-sm mb-2">
            We sent a verification link to
        </p>
        <p class="text-[#e5e5e5] font-medium text-sm mb-8">{{ auth()->user()->email }}</p>

        @if (session('status') === 'Verification link sent!')
            <div class="bg-[#0093fd]/10 border border-[#0093fd]/20 text-[#0093fd] rounded-xl px-4 py-3 mb-6 text-sm">
                A new verification link has been sent.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                class="w-full bg-[#0093fd] hover:bg-[#0080e0] text-white font-semibold py-2.5 rounded-xl transition-colors text-sm">
                Resend verification email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-sm text-[#697d91] hover:text-[#e5e5e5] transition-colors">
                Sign out
            </button>
        </form>

    </div>
</div>
@endsection
