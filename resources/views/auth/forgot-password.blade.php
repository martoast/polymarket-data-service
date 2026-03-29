@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-white mb-1">Reset your password</h1>
            <p class="text-[#697d91] text-sm">Enter your email and we'll send you a reset link.</p>
        </div>

        @if (session('status'))
            <div class="bg-[#26a05e]/10 border border-[#26a05e]/20 rounded-xl p-4 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-4 h-4 text-[#26a05e] flex-shrink-0 mt-0.5" viewBox="0 0 16 16" fill="none">
                        <path d="M3 8l3.5 3.5 6.5-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="text-[#26a05e] text-sm">{{ session('status') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6">
                @foreach ($errors->all() as $error)
                    <div class="text-red-400 text-sm">{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-[#e5e5e5] mb-1.5">Email address</label>
                <input
                    type="email" id="email" name="email"
                    value="{{ old('email') }}" required autocomplete="email"
                    placeholder="you@example.com"
                    class="w-full bg-[#1e2428] border border-[#2e3841] rounded-xl px-4 py-2.5 text-sm text-[#e5e5e5] placeholder-[#697d91] focus:outline-none focus:border-[#0093fd] focus:ring-1 focus:ring-[#0093fd]/30 transition-colors"
                />
            </div>

            <button type="submit"
                class="w-full bg-[#0093fd] hover:bg-[#0080e0] text-white font-semibold py-2.5 rounded-xl transition-colors text-sm">
                Send reset link
            </button>
        </form>

        <p class="text-center text-sm text-[#697d91] mt-6">
            Remembered it?
            <a href="{{ route('login') }}" class="text-[#0093fd] hover:text-[#0080e0] font-medium transition-colors">Sign in</a>
        </p>

    </div>
</div>
@endsection
