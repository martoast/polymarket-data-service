@extends('layouts.app')

@section('title', 'Requests — Admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-[#e5e5e5]">API Requests</h1>
            <p class="text-sm text-[#697d91] mt-1">{{ number_format($logs->total()) }} logged</p>
        </div>
        <form method="GET" class="flex gap-2">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Filter by email or path…"
                class="bg-[#17181c] border border-[#1f2937] rounded-lg px-3 py-2 text-sm text-[#e5e5e5] placeholder-[#697d91] focus:outline-none focus:border-[#0093fd]/40 w-64"
            />
            <button type="submit" class="bg-[#0093fd] hover:bg-[#0080e0] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Filter</button>
            @if($search)
                <a href="{{ route('admin.requests') }}" class="border border-[#1f2937] text-[#697d91] hover:text-[#e5e5e5] text-sm font-medium px-4 py-2 rounded-lg transition-colors">Clear</a>
            @endif
        </form>
    </div>

    {{-- Stats row --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-5">
            <div class="text-xs text-[#697d91] uppercase tracking-wider mb-1">Last hour</div>
            <div class="text-2xl font-bold text-[#e5e5e5] font-mono">{{ number_format($stats->last_hour) }}</div>
        </div>
        <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-5">
            <div class="text-xs text-[#697d91] uppercase tracking-wider mb-1">Last 24h</div>
            <div class="text-2xl font-bold text-[#e5e5e5] font-mono">{{ number_format($stats->last_day) }}</div>
        </div>
        <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl p-5">
            <div class="text-xs text-[#697d91] uppercase tracking-wider mb-1">All time</div>
            <div class="text-2xl font-bold text-[#e5e5e5] font-mono">{{ number_format($stats->total) }}</div>
        </div>
    </div>

    <div class="bg-[#17181c] border border-[#1f2937] rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-[#1f2937] text-xs text-[#697d91] uppercase tracking-wider">
                    <th class="text-left px-5 py-3">Time</th>
                    <th class="text-left px-5 py-3">User</th>
                    <th class="text-left px-5 py-3">Request</th>
                    <th class="text-left px-5 py-3">IP</th>
                    <th class="text-right px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#1f2937]">
                @forelse ($logs as $log)
                <tr class="hover:bg-[#1e2428]/50 transition-colors">
                    <td class="px-5 py-3 text-[#697d91] text-xs font-mono whitespace-nowrap">
                        {{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}
                        <span class="text-[#2e3841] ml-1">{{ \Carbon\Carbon::parse($log->created_at)->format('M j') }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="text-[#e5e5e5] text-xs">{{ $log->user_name }}</div>
                        <div class="text-[#697d91] text-xs">{{ $log->user_email }}</div>
                    </td>
                    <td class="px-5 py-3 font-mono text-xs">
                        <span class="text-[#697d91]">{{ $log->method }}</span>
                        <span class="text-[#e5e5e5] ml-1">{{ $log->path }}</span>
                    </td>
                    <td class="px-5 py-3 text-[#697d91] text-xs font-mono">{{ $log->ip }}</td>
                    <td class="px-5 py-3 text-right">
                        <span class="text-xs font-mono px-2 py-0.5 rounded font-semibold
                            @if($log->status >= 200 && $log->status < 300) bg-[#26a05e]/10 text-[#26a05e]
                            @elseif($log->status >= 400 && $log->status < 500) bg-amber-500/10 text-amber-400
                            @elseif($log->status >= 500) bg-red-500/10 text-red-400
                            @else text-[#697d91] @endif">
                            {{ $log->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-[#697d91] text-sm">No requests logged yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($logs->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $logs->links() }}
        </div>
    @endif

</div>
@endsection
