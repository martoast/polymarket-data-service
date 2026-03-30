@extends('layouts.app')

@section('title', 'Create account')

@section('content')
<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-white mb-1">Create your account</h1>
            <p class="text-[#697d91] text-sm">Get your free API key instantly</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6">
                @foreach ($errors->all() as $error)
                    <div class="text-red-400 text-sm">{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <a href="{{ route('auth.google') }}"
           class="flex items-center justify-center gap-3 w-full bg-[#1e2428] hover:bg-[#252c32] border border-[#2e3841] hover:border-[#3a4a5a] text-[#e5e5e5] font-medium py-2.5 rounded-xl transition-all text-sm mb-4">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 01-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
                <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 009 18z" fill="#34A853"/>
                <path d="M3.964 10.71A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 00.957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
            </svg>
            Continue with Google
        </a>

        <div class="flex items-center gap-3 mb-4">
            <div class="flex-1 h-px bg-[#1f2937]"></div>
            <span class="text-xs text-[#697d91]">or</span>
            <div class="flex-1 h-px bg-[#1f2937]"></div>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-[#e5e5e5] mb-1.5">Name</label>
                <input
                    type="text" id="name" name="name"
                    value="{{ old('name') }}" required autocomplete="name"
                    placeholder="Your name"
                    class="w-full bg-[#1e2428] border border-[#2e3841] rounded-xl px-4 py-2.5 text-sm text-[#e5e5e5] placeholder-[#697d91] focus:outline-none focus:border-[#0093fd] focus:ring-1 focus:ring-[#0093fd]/30 transition-colors"
                />
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-[#e5e5e5] mb-1.5">Email</label>
                <input
                    type="email" id="email" name="email"
                    value="{{ old('email') }}" required autocomplete="email"
                    placeholder="you@example.com"
                    class="w-full bg-[#1e2428] border border-[#2e3841] rounded-xl px-4 py-2.5 text-sm text-[#e5e5e5] placeholder-[#697d91] focus:outline-none focus:border-[#0093fd] focus:ring-1 focus:ring-[#0093fd]/30 transition-colors"
                />
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-[#e5e5e5] mb-1.5">Password</label>
                <input
                    type="password" id="password" name="password"
                    required autocomplete="new-password"
                    placeholder="Min 8 characters"
                    class="w-full bg-[#1e2428] border border-[#2e3841] rounded-xl px-4 py-2.5 text-sm text-[#e5e5e5] placeholder-[#697d91] focus:outline-none focus:border-[#0093fd] focus:ring-1 focus:ring-[#0093fd]/30 transition-colors"
                />
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-[#e5e5e5] mb-1.5">Confirm password</label>
                <input
                    type="password" id="password_confirmation" name="password_confirmation"
                    required autocomplete="new-password"
                    placeholder="Repeat password"
                    class="w-full bg-[#1e2428] border border-[#2e3841] rounded-xl px-4 py-2.5 text-sm text-[#e5e5e5] placeholder-[#697d91] focus:outline-none focus:border-[#0093fd] focus:ring-1 focus:ring-[#0093fd]/30 transition-colors"
                />
            </div>

            <button type="submit"
                class="w-full bg-[#0093fd] hover:bg-[#0080e0] text-white font-semibold py-2.5 rounded-xl transition-colors text-sm mt-2">
                Create account
            </button>
        </form>

        <p class="text-center text-sm text-[#697d91] mt-6">
            Already have an account?
            <a href="{{ route('login') }}" class="text-[#0093fd] hover:text-[#0080e0] font-medium transition-colors">Sign in</a>
        </p>

    </div>
</div>
@endsection
