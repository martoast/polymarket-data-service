<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Polymarket Data') — Oracle API</title>

    {{-- Inter font (Polymarket's UI font) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
                    },
                    colors: {
                        pm: {
                            bg:        '#0a0b10',
                            surface:   '#17181c',
                            surface2:  '#1e2428',
                            surface3:  '#242b32',
                            border:    '#1f2937',
                            border2:   '#2e3841',
                            brand:     '#0093fd',
                            'brand-hover': '#0080e0',
                            text:      '#e5e5e5',
                            muted:     '#697d91',
                            faint:     '#2e3841',
                        },
                    },
                },
            },
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        code, pre, .font-mono { font-family: 'JetBrains Mono', 'Fira Code', monospace; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: #17181c; }
        ::-webkit-scrollbar-thumb { background: #2e3841; border-radius: 4px; }

        /* Input autofill fix */
        input:-webkit-autofill,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #1e2428 inset !important;
            -webkit-text-fill-color: #e5e5e5 !important;
        }
    </style>
    @yield('head')
</head>
<body class="h-full bg-[#0a0b10] text-[#e5e5e5] antialiased">

    {{-- Nav --}}
    <nav class="border-b border-[#1f2937] bg-[#0a0b10]/95 backdrop-blur-sm sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                    <div class="w-6 h-6 rounded-md bg-[#0093fd] flex items-center justify-center">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M7 1L13 4.5V9.5L7 13L1 9.5V4.5L7 1Z" fill="white" fill-opacity="0.9"/>
                        </svg>
                    </div>
                    <span class="text-[#e5e5e5] font-semibold text-sm tracking-tight group-hover:text-white transition-colors">
                        Polymarket Data
                    </span>
                </a>

                {{-- Nav links --}}
                <div class="flex items-center gap-1 text-sm">
                    <a href="{{ route('docs') }}" class="text-[#697d91] hover:text-[#e5e5e5] transition-colors px-3 py-2 rounded-lg hover:bg-[#17181c] text-sm font-medium">
                        Docs
                    </a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="text-[#697d91] hover:text-[#e5e5e5] transition-colors px-3 py-2 rounded-lg hover:bg-[#17181c] text-sm font-medium">
                            Dashboard
                        </a>
                        @if (auth()->user()->is_admin)
                            <a href="{{ route('admin.users') }}" class="text-[#697d91] hover:text-[#e5e5e5] transition-colors px-3 py-2 rounded-lg hover:bg-[#17181c] text-sm font-medium">
                                Users
                            </a>
                            <a href="{{ route('admin.requests') }}" class="text-[#697d91] hover:text-[#e5e5e5] transition-colors px-3 py-2 rounded-lg hover:bg-[#17181c] text-sm font-medium">
                                Requests
                            </a>
                            <a href="{{ route('admin.recorder') }}" class="text-[#697d91] hover:text-[#e5e5e5] transition-colors px-3 py-2 rounded-lg hover:bg-[#17181c] text-sm font-medium">
                                Recorder
                            </a>
                        @else
                        <a href="{{ route('billing') }}" class="text-[#697d91] hover:text-[#e5e5e5] transition-colors px-3 py-2 rounded-lg hover:bg-[#17181c] text-sm font-medium">
                            Billing
                        </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="inline ml-1">
                            @csrf
                            <button type="submit" class="text-[#697d91] hover:text-[#e5e5e5] transition-colors px-3 py-2 rounded-lg hover:bg-[#17181c] text-sm font-medium">
                                Sign out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-[#697d91] hover:text-[#e5e5e5] transition-colors px-3 py-2 rounded-lg hover:bg-[#17181c] text-sm font-medium">
                            Sign in
                        </a>
                        <a href="{{ route('register') }}"
                           class="ml-1 bg-[#0093fd] hover:bg-[#0080e0] text-white font-semibold px-4 py-2 rounded-lg text-sm transition-colors">
                            Get started
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    {{-- Flash messages --}}
    @if (session('success') || session('status'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-16 right-4 z-50 bg-[#17181c] border border-[#1f2937] text-[#e5e5e5] text-sm px-4 py-3 rounded-xl shadow-xl flex items-center gap-3 max-w-sm">
            <div class="w-2 h-2 rounded-full bg-[#26a05e] flex-shrink-0"></div>
            {{ session('success') ?? session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-16 right-4 z-50 bg-[#17181c] border border-[#2d1b1b] text-[#e5e5e5] text-sm px-4 py-3 rounded-xl shadow-xl flex items-center gap-3 max-w-sm">
            <div class="w-2 h-2 rounded-full bg-red-500 flex-shrink-0"></div>
            {{ session('error') }}
        </div>
    @endif

    {{-- Content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t border-[#1f2937] mt-24 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-[#697d91]">
            <span class="font-medium">Polymarket Data — Oracle API</span>
            <div class="flex items-center gap-6">
                <a href="{{ route('docs') }}" class="hover:text-[#e5e5e5] transition-colors">Docs</a>
                <a href="{{ route('billing') }}" class="hover:text-[#e5e5e5] transition-colors">Pricing</a>
                <a href="https://polymarket.com" target="_blank" class="hover:text-[#e5e5e5] transition-colors">Polymarket</a>
            </div>
        </div>
    </footer>

</body>
</html>
