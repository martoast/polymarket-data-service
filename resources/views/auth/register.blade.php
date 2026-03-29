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
