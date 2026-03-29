@extends('layouts.app')

@section('title', 'Users — Admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-xl font-bold text-[#e5e5e5]">Users</h1>
            <p class="text-sm text-[#697d91] mt-1">{{ $users->total() }} total</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Search name or email…"
                class="bg-[#17181c] border border-[#1f2937] rounded-lg px-3 py-2 text-sm text-[#e5e5e5] placeholder-[#697d91] focus:outline-none focus:border-[#0093fd]/40 w-64"
            />
            <button type="submit" class="bg-[#0093fd] hover:bg-[#0080e0] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Search</button>
            @if($search)
                <a href="{{ route('admin.users') }}" class="border border-[#1f2937] text-[#697d91] hover:text-[#e5e5e5] text-sm font-medium px-4 py-2 rounded-lg transition-colors">Clear</a>
            @endif
        </form>
    </div>

    <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-[#1f2937] text-xs text-[#697d91] uppercase tracking-wider">
                    <th class="text-left px-5 py-3">User</th>
                    <th class="text-left px-5 py-3">Tier</th>
                    <th class="text-left px-5 py-3">Status</th>
                    <th class="text-left px-5 py-3">Joined</th>
                    <th class="text-right px-5 py-3">Tokens</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#1f2937]">
                @forelse ($users as $user)
                <tr class="hover:bg-[#1e2428]/50 transition-colors">
                    <td class="px-5 py-3.5">
                        <div class="font-medium text-[#e5e5e5]">{{ $user->name }}</div>
                        <div class="text-xs text-[#697d91] mt-0.5">{{ $user->email }}</div>
                    </td>
                    <td class="px-5 py-3.5">
                        @if ($user->is_admin)
                            <span class="bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-semibold px-2 py-0.5 rounded-full">Admin</span>
                        @elseif ($user->tier === 'pro')
                            <span class="bg-purple-500/10 border border-purple-500/30 text-purple-300 text-xs font-semibold px-2 py-0.5 rounded-full">Pro</span>
                        @elseif ($user->tier === 'builder')
                            <span class="bg-[#0093fd]/10 border border-[#0093fd]/30 text-[#0093fd] text-xs font-semibold px-2 py-0.5 rounded-full">Builder</span>
                        @else
                            <span class="bg-[#1e2428] border border-[#2e3841] text-[#697d91] text-xs font-semibold px-2 py-0.5 rounded-full">Free</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5">
                        @if ($user->email_verified_at)
                            <span class="text-[#26a05e] text-xs">Verified</span>
                        @else
                            <span class="text-amber-400 text-xs">Unverified</span>
                        @endif
                        @if (!$user->is_active)
                            <span class="text-red-400 text-xs ml-2">Inactive</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-[#697d91] text-xs">
                        {{ \Carbon\Carbon::parse($user->created_at)->format('M j, Y') }}
                    </td>
                    <td class="px-5 py-3.5 text-right text-[#697d91] text-xs font-mono">
                        {{ $user->tokens_count }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-[#697d91] text-sm">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $users->links() }}
        </div>
    @endif

</div>
@endsection
