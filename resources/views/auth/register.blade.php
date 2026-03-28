@extends('layouts.app')

@section('title', 'Create account')

@section('content')
<div class="min-h-[calc(100vh-8rem)] flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-mono font-bold text-white">Create account</h1>
            <p class="text-gray-500 font-mono text-sm mt-1">Get your free API key instantly</p>
        </div>

        {{-- Errors --}}
        @if ($errors->any())
            <div class="bg-red-900/30 border border-red-500/30 rounded p-3 mb-6">
                @foreach ($errors->all() as $error)
                    <div class="text-red-400 text-sm font-mono">{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-mono text-gray-400 mb-1.5 uppercase tracking-widest">
                    Name
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    autocomplete="name"
                    placeholder="Your name"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2.5 text-sm font-mono text-white placeholder-gray-600 focus:outline-none focus:border-[#22c55e] transition-colors"
                />
            </div>

            <div>
                <label for="email" class="block text-xs font-mono text-gray-400 mb-1.5 uppercase tracking-widest">
                    Email
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    placeholder="you@example.com"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2.5 text-sm font-mono text-white placeholder-gray-600 focus:outline-none focus:border-[#22c55e] transition-colors"
                />
            </div>

            <div>
                <label for="password" class="block text-xs font-mono text-gray-400 mb-1.5 uppercase tracking-widest">
                    Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="min 8 characters"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2.5 text-sm font-mono text-white placeholder-gray-600 focus:outline-none focus:border-[#22c55e] transition-colors"
                />
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-mono text-gray-400 mb-1.5 uppercase tracking-widest">
                    Confirm Password
                </label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="repeat password"
                    class="w-full bg-gray-900 border border-gray-700 rounded px-3 py-2.5 text-sm font-mono text-white placeholder-gray-600 focus:outline-none focus:border-[#22c55e] transition-colors"
                />
            </div>

            <button
                type="submit"
                class="w-full bg-[#22c55e] text-black font-mono font-bold py-2.5 rounded hover:bg-green-400 transition-colors text-sm mt-2"
            >
                Create account
            </button>
        </form>

        <p class="text-center text-sm font-mono text-gray-500 mt-6">
            Already have an account?
            <a href="{{ route('login') }}" class="text-[#22c55e] hover:text-green-400 transition-colors">Sign in</a>
        </p>

    </div>
</div>
@endsection
