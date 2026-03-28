<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'polymarket-data') — Polymarket Oracle API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        mono: ['JetBrains Mono', 'Fira Code', 'Cascadia Code', 'monospace'],
                    },
                    colors: {
                        brand: {
                            green: '#22c55e',
                            dark:  '#0f0f0f',
                        },
                    },
                },
            },
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'JetBrains Mono', 'Fira Code', monospace; }
        .scrollbar-thin::-webkit-scrollbar { width: 4px; height: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #1a1a1a; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #22c55e44; border-radius: 2px; }
    </style>
</head>
<body class="h-full bg-[#0f0f0f] text-gray-100 antialiased">

    {{-- Nav --}}
    <nav class="border-b border-gray-800 bg-[#0f0f0f]/95 backdrop-blur sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="text-[#22c55e] font-mono font-bold text-sm tracking-tight hover:text-green-400 transition-colors">
                    polymarket-data<span class="text-gray-500">.api</span>
                </a>

                {{-- Nav links --}}
                <div class="flex items-center gap-6 text-sm">
                    <a href="{{ route('docs') }}" class="text-gray-400 hover:text-gray-100 transition-colors font-mono">docs</a>
                    <a href="https://github.com" target="_blank" class="text-gray-400 hover:text-gray-100 transition-colors font-mono">github</a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-100 transition-colors font-mono">dashboard</a>
                        <a href="{{ route('billing') }}" class="text-gray-400 hover:text-gray-100 transition-colors font-mono">billing</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-500 hover:text-red-400 transition-colors font-mono text-sm">
                                logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-400 hover:text-gray-100 transition-colors font-mono">login</a>
                        <a href="{{ route('register') }}" class="bg-[#22c55e] text-black font-mono font-semibold px-3 py-1 rounded text-xs hover:bg-green-400 transition-colors">
                            get api key
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Flash messages --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="fixed top-16 right-4 z-50 bg-green-900/80 border border-[#22c55e]/40 text-green-300 text-sm font-mono px-4 py-3 rounded shadow-lg transition-all">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             class="fixed top-16 right-4 z-50 bg-red-900/80 border border-red-500/40 text-red-300 text-sm font-mono px-4 py-3 rounded shadow-lg transition-all">
            {{ session('error') }}
        </div>
    @endif

    {{-- Content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t border-gray-800 mt-24 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between text-xs font-mono text-gray-600">
            <span>polymarket-data.api — raw oracle data for algo traders</span>
            <span>built on <span class="text-[#22c55e]">BonereaderBot</span> data</span>
        </div>
    </footer>

</body>
</html>
