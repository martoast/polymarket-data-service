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
        $data = Cache::remember('public_live_v5', 30, fn () => $this->build());

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
            ->limit(count($assets) * 5)
            ->get(['assets.symbol', 'oracle_ticks.price_usd', 'oracle_ticks.ts']);

        $oracle = collect($assets)->mapWithKeys(function (string $symbol) use ($oracleRows) {
            $tick = $oracleRows->firstWhere('symbol', $symbol);
            return $tick
                ? [$symbol => ['price_usd' => (float) $tick->price_usd, 'ts' => (int) $tick->ts]]
                : [];
        })->filter()->all();

        // ── Active markets: one query for crypto assets + durations ──────────
        $assetMap = DB::table('assets')->whereIn('symbol', $assets)->pluck('id', 'symbol');

        $activeMarkets = DB::table('markets')
            ->whereIn('asset_id', $assetMap->values())
            ->whereIn('duration_sec', array_keys($durations))
            ->where('close_ts', '>', $nowMs)
            ->whereNull('outcome')
            ->where('break_value', '>', 0)
            ->orderByDesc('open_ts')
            ->get(['id', 'asset_id', 'duration_sec']);

        if ($activeMarkets->isEmpty()) {
            return ['oracle' => $oracle, 'clob' => (object) []];
        }

        // Pick the single most-recent market per asset+duration
        $marketMap = [];   // [asset_id][duration_sec] => market_id
        foreach ($activeMarkets as $m) {
            if (!isset($marketMap[$m->asset_id][$m->duration_sec])) {
                $marketMap[$m->asset_id][$m->duration_sec] = $m->id;
            }
        }
        $marketIds = collect($marketMap)->flatten()->unique()->values()->all();

        // ── CLOB: one query for all active markets ───────────────────────────
        // Time-bound to last hour so TimescaleDB can prune old chunks (6.5 GB table).
        $snapshots = DB::table('clob_snapshots')
            ->whereIn('market_id', $marketIds)
            ->where('ts', '>', $nowMs - 3_600_000)
            ->orderByDesc('ts')
            ->limit(200)
            ->get(['market_id', 'yes_bid', 'yes_ask', 'no_bid', 'no_ask', 'ts']);

        $latest = [];   // [market_id][col] => value
        foreach ($snapshots as $row) {
            $mid = $row->market_id;
            foreach (['yes_bid', 'yes_ask', 'no_bid', 'no_ask'] as $col) {
                if (!isset($latest[$mid][$col]) && $row->$col !== null) {
                    $latest[$mid][$col] = (float) $row->$col;
                }
            }
        }

        // ── Assemble response ────────────────────────────────────────────────
        $clob       = [];
        $symbolById = $assetMap->flip();   // id => symbol

        foreach ($marketMap as $assetId => $byDuration) {
            $symbol = $symbolById[$assetId] ?? null;
            if (!$symbol) continue;

            foreach ($byDuration as $durationSec => $marketId) {
                $label  = $durations[$durationSec];
                $values = $latest[$marketId] ?? [];

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
                    'market_id' => $marketId,
                    'ts'        => $nowMs,
                ];
            }
        }

        return ['oracle' => $oracle, 'clob' => $clob ?: (object) []];
    }
}
