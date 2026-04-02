<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicLiveController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $data = Cache::remember('public_live_v4', 30, fn () => $this->build());

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=30');
    }

    private function build(): array
    {
        $nowMs     = (int) (microtime(true) * 1000);
        $assets    = ['BTC', 'ETH', 'SOL'];
        $durations = [300 => '5m', 900 => '15m'];

        // ── Oracle: one query for all assets ────────────────────────────────
        $oracleRows = DB::table('oracle_ticks')
            ->join('assets', 'oracle_ticks.asset_id', '=', 'assets.id')
            ->whereIn('assets.symbol', $assets)
            ->orderByDesc('oracle_ticks.ts')
            ->limit(count($assets) * 5)        // grab a few recent rows per asset
            ->get(['assets.symbol', 'oracle_ticks.price_usd', 'oracle_ticks.ts']);

        $oracle = collect($assets)->mapWithKeys(function (string $symbol) use ($oracleRows) {
            $tick = $oracleRows->firstWhere('symbol', $symbol);
            return $tick
                ? [$symbol => ['price_usd' => (float) $tick->price_usd, 'ts' => (int) $tick->ts]]
                : [];
        })->filter()->all();

        // ── Active windows: one query for all assets + durations ─────────────
        $assetMap = DB::table('assets')->whereIn('symbol', $assets)->pluck('id', 'symbol');

        $activeWindows = DB::table('windows')
            ->whereIn('asset_id', $assetMap->values())
            ->whereIn('duration_sec', array_keys($durations))
            ->where('close_ts', '>', $nowMs)
            ->whereNull('outcome')
            ->where('break_price_usd', '>', 0)
            ->orderByDesc('open_ts')
            ->get(['id', 'asset_id', 'duration_sec']);

        if ($activeWindows->isEmpty()) {
            return ['oracle' => $oracle, 'clob' => (object) []];
        }

        // Pick the single most-recent window per asset+duration
        $windowMap = [];   // [asset_id][duration_sec] => window_id
        foreach ($activeWindows as $w) {
            if (!isset($windowMap[$w->asset_id][$w->duration_sec])) {
                $windowMap[$w->asset_id][$w->duration_sec] = $w->id;
            }
        }
        $windowIds = collect($windowMap)->flatten()->unique()->values()->all();

        // ── CLOB: one query for all active windows, small LIMIT ──────────────
        // Each row has at most one non-null price column (one row per side per event).
        // We grab the last 200 rows across all windows and pivot in PHP — no per-column queries.
        $snapshots = DB::table('clob_snapshots')
            ->whereIn('window_id', $windowIds)
            ->orderByDesc('ts')
            ->limit(200)
            ->get(['window_id', 'yes_bid', 'yes_ask', 'no_bid', 'no_ask', 'ts']);

        // Build latest non-null per column per window in PHP
        $latest = [];   // [window_id][col] => value
        foreach ($snapshots as $row) {
            $wid = $row->window_id;
            foreach (['yes_bid', 'yes_ask', 'no_bid', 'no_ask'] as $col) {
                if (!isset($latest[$wid][$col]) && $row->$col !== null) {
                    $latest[$wid][$col] = (float) $row->$col;
                }
            }
        }

        // ── Assemble response ────────────────────────────────────────────────
        $clob = [];
        $symbolById = $assetMap->flip();   // id => symbol

        foreach ($windowMap as $assetId => $byDuration) {
            $symbol = $symbolById[$assetId] ?? null;
            if (!$symbol) continue;

            foreach ($byDuration as $durationSec => $windowId) {
                $label  = $durations[$durationSec];
                $values = $latest[$windowId] ?? [];

                $yesBid = round($values['yes_bid'] ?? 0, 3);
                $yesAsk = round($values['yes_ask'] ?? 0, 3);
                $noBid  = round($values['no_bid']  ?? 0, 3);
                $noAsk  = round($values['no_ask']  ?? 0, 3);

                if (!$yesBid && !$yesAsk && !$noBid && !$noAsk) continue;

                $spread    = ($yesAsk && $yesBid) ? round($yesAsk - $yesBid, 3) : null;
                $mid       = ($yesAsk && $yesBid) ? round(($yesBid + $yesAsk) / 2, 3) : null;
                $denom     = $yesBid + $noBid;
                $imbalance = $denom > 0 ? round(($yesBid - $noBid) / $denom, 3) : null;

                $clob[$symbol][$label] = [
                    'yes_bid'   => $yesBid ?: null,
                    'yes_ask'   => $yesAsk ?: null,
                    'no_bid'    => $noBid  ?: null,
                    'no_ask'    => $noAsk  ?: null,
                    'spread'    => $spread,
                    'mid'       => $mid,
                    'imbalance' => $imbalance,
                    'window_id' => $windowId,
                    'ts'        => $nowMs,
                ];
            }
        }

        return ['oracle' => $oracle, 'clob' => $clob ?: (object) []];
    }
}
