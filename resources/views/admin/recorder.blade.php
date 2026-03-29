@extends('layouts.app')

@section('title', 'Recorder — Admin')

@section('head')
<style>
    .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .dot-green { background: #26a05e; box-shadow: 0 0 6px #26a05e88; }
    .dot-red   { background: #ef4444; }
    .dot-yellow{ background: #f59e0b; box-shadow: 0 0 6px #f59e0b88; }
    .pulse { animation: pulse 2s infinite; }
    @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
    .stat-card { background: #17181c; border: 1px solid #1f2937; border-radius: 12px; padding: 20px 24px; }
    .feed-card { background: #17181c; border: 1px solid #1f2937; border-radius: 12px; padding: 16px 20px; }
    .mono { font-family: 'JetBrains Mono', monospace; font-size: 13px; }
</style>
@endsection

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-xl font-bold text-[#e5e5e5]">Recorder</h1>
            <p class="text-sm text-[#697d91] mt-1">Live market recorder status — updates every 3s</p>
        </div>
        <div id="status-badge" class="flex items-center gap-2 text-sm font-medium text-[#697d91] bg-[#17181c] border border-[#1f2937] rounded-lg px-4 py-2">
            <span class="dot dot-yellow pulse" id="status-dot"></span>
            <span id="status-text">Loading…</span>
        </div>
    </div>

    {{-- Top stats row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="stat-card">
            <div class="text-xs text-[#697d91] uppercase tracking-wider mb-1">Oracle ticks</div>
            <div class="text-2xl font-bold text-[#e5e5e5] mono" id="stat-oracle">—</div>
        </div>
        <div class="stat-card">
            <div class="text-xs text-[#697d91] uppercase tracking-wider mb-1">CLOB snapshots</div>
            <div class="text-2xl font-bold text-[#e5e5e5] mono" id="stat-clob">—</div>
        </div>
        <div class="stat-card">
            <div class="text-xs text-[#697d91] uppercase tracking-wider mb-1">Candles (1m)</div>
            <div class="text-2xl font-bold text-[#e5e5e5] mono" id="stat-candles">—</div>
        </div>
        <div class="stat-card">
            <div class="text-xs text-[#697d91] uppercase tracking-wider mb-1">Active markets</div>
            <div class="text-2xl font-bold text-[#0093fd] mono" id="stat-markets">—</div>
        </div>
    </div>

    {{-- Feed status row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

        {{-- Oracle feed --}}
        <div class="feed-card">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-semibold text-[#e5e5e5]">Oracle feed (Chainlink)</span>
                <span class="flex items-center gap-1.5 text-xs" id="oracle-status">
                    <span class="dot dot-yellow pulse"></span> connecting
                </span>
            </div>
            <div id="oracle-assets" class="space-y-2">
                <div class="text-xs text-[#697d91]">Waiting for data…</div>
            </div>
        </div>

        {{-- CLOB feed --}}
        <div class="feed-card">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-semibold text-[#e5e5e5]">CLOB feed (Polymarket)</span>
                <span class="flex items-center gap-1.5 text-xs" id="clob-status">
                    <span class="dot dot-yellow pulse"></span> connecting
                </span>
            </div>
            <div class="grid grid-cols-2 gap-3 text-xs">
                <div>
                    <div class="text-[#697d91] mb-1">Subscribed tokens</div>
                    <div class="text-[#e5e5e5] font-semibold mono" id="clob-subscribed">—</div>
                </div>
                <div>
                    <div class="text-[#697d91] mb-1">Snapshots written</div>
                    <div class="text-[#e5e5e5] font-semibold mono" id="clob-snapshots">—</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Markets table --}}
    <div class="stat-card">
        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-semibold text-[#e5e5e5]">Markets</span>
            <span class="text-xs text-[#697d91]" id="markets-summary">total: — / active: —</span>
        </div>
        <div id="markets-table" class="text-xs text-[#697d91]">Loading…</div>
    </div>

    {{-- Footer --}}
    <div class="mt-4 text-xs text-[#2e3841] mono text-right" id="last-updated">—</div>
</div>

<script>
let sessionOracleTicks = 0;
let sessionClob        = 0;
let sessionCandles     = 0;

async function fetchStatus() {
    try {
        const r = await fetch('{{ route("admin.recorder.status") }}');
        const d = await r.json();
        render(d);
    } catch(e) {
        setOffline();
    }
}

function render(d) {
    // Status badge
    const dot  = document.getElementById('status-dot');
    const text = document.getElementById('status-text');
    if (d.running) {
        dot.className  = 'dot dot-green pulse';
        text.textContent = 'Running';
        text.className   = 'text-[#26a05e] font-medium';
    } else {
        dot.className  = 'dot dot-red';
        text.textContent = 'Offline';
        text.className   = 'text-[#697d91]';
    }

    // Stats
    sessionOracleTicks = d.oracle_written || 0;
    sessionClob        = d.clob?.snapshots_written || 0;
    sessionCandles     = d.candles_written || 0;

    document.getElementById('stat-oracle').textContent   = fmt(sessionOracleTicks);
    document.getElementById('stat-clob').textContent     = fmt(sessionClob);
    document.getElementById('stat-candles').textContent  = fmt(sessionCandles);
    document.getElementById('stat-markets').textContent  = d.markets?.active ?? '—';
    document.getElementById('markets-summary').textContent = `total: ${d.markets?.total ?? '—'} / active: ${d.markets?.active ?? '—'}`;

    // Oracle feed
    const oracleConn = d.oracle && Object.keys(d.oracle).length > 0;
    document.getElementById('oracle-status').innerHTML = oracleConn
        ? `<span class="dot dot-green pulse"></span> <span class="text-[#26a05e]">live</span>`
        : `<span class="dot dot-yellow pulse"></span> <span class="text-[#697d91]">connecting</span>`;

    const oracleAssets = document.getElementById('oracle-assets');
    if (d.oracle && Object.keys(d.oracle).length) {
        oracleAssets.innerHTML = Object.entries(d.oracle).map(([asset, info]) => `
            <div class="flex items-center justify-between">
                <span class="text-[#697d91] font-medium">${asset}</span>
                <span class="mono text-[#e5e5e5]">$${Number(info.price || 0).toLocaleString('en-US', {minimumFractionDigits:2,maximumFractionDigits:2})}</span>
                <span class="text-[#697d91] text-xs">${ago(info.last_tick)}</span>
            </div>
        `).join('');
    } else {
        oracleAssets.innerHTML = '<div class="text-xs text-[#697d91]">Waiting for data…</div>';
    }

    // CLOB feed
    const clobConn = d.clob?.connected;
    document.getElementById('clob-status').innerHTML = clobConn
        ? `<span class="dot dot-green pulse"></span> <span class="text-[#26a05e]">live</span>`
        : `<span class="dot dot-yellow pulse"></span> <span class="text-[#697d91]">connecting</span>`;
    document.getElementById('clob-subscribed').textContent = fmt(d.clob?.subscribed ?? 0);
    document.getElementById('clob-snapshots').textContent  = fmt(d.clob?.snapshots_written ?? 0);

    // Last updated
    if (d.last_updated) {
        document.getElementById('last-updated').textContent = 'last updated ' + new Date(d.last_updated * 1000).toLocaleTimeString();
    }
}

function setOffline() {
    document.getElementById('status-dot').className  = 'dot dot-red';
    document.getElementById('status-text').textContent = 'Offline';
}

function fmt(n) {
    return Number(n).toLocaleString();
}

function ago(tsMs) {
    if (!tsMs) return '';
    const s = Math.floor((Date.now() - tsMs) / 1000);
    if (s < 5)  return 'just now';
    if (s < 60) return `${s}s ago`;
    return `${Math.floor(s/60)}m ago`;
}

fetchStatus();
setInterval(fetchStatus, 3000);
</script>
@endsection
