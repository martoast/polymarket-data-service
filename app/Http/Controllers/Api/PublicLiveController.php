<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClobSnapshot;
use App\Models\OracleTick;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicLiveController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // Cache the full response for 5 seconds — matches the landing page poll interval.
        // All DB queries are skipped on cache hits, making the endpoint safe under load.
        $data = Cache::remember('public_live_v1', 5, fn () => $this->build());

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=5');
    }

    private function build(): array
    {
        // Latest price tick per asset (3 rows max after dedup)
        $ticks = OracleTick::with('asset')
            ->orderByDesc('ts')
            ->limit(30)
            ->get()
            ->unique(fn ($t) => $t->asset->symbol ?? $t->asset_id)
            ->take(3)
            ->values();

        $oracle = $ticks->mapWithKeys(function ($tick) {
            $symbol = $tick->asset->symbol ?? 'BTC';
            return [$symbol => [
                'price_usd' => (float) $tick->price_usd,
                'ts'        => (int)   $tick->ts,
            ]];
        });

        // Find the active BTC window with the most recent CLOB snapshot
        $nowMs      = (int) (microtime(true) * 1000);
        $btcAssetId = DB::table('assets')->where('symbol', 'BTC')->value('id');

        $clobData = null;
        if ($btcAssetId) {
            $windowId = DB::table('clob_snapshots')
                ->join('windows', 'clob_snapshots.window_id', '=', 'windows.id')
                ->where('clob_snapshots.asset_id', $btcAssetId)
                ->where('windows.close_ts', '>', $nowMs)
                ->whereNull('windows.outcome')
                ->orderByDesc('clob_snapshots.ts')
                ->value('clob_snapshots.window_id');

            if ($windowId) {
                // Each WS event sets only ONE field — get latest non-null per column
                // via four targeted single-row queries instead of loading 500 rows in PHP
                $pick = fn (string $col) => DB::table('clob_snapshots')
                    ->where('window_id', $windowId)
                    ->whereNotNull($col)
                    ->orderByDesc('ts')
                    ->value($col);

                $yesBid = (float) ($pick('yes_bid') ?? 0);
                $yesAsk = (float) ($pick('yes_ask') ?? 0);
                $noBid  = (float) ($pick('no_bid')  ?? 0);
                $noAsk  = (float) ($pick('no_ask')  ?? 0);

                if ($yesBid || $yesAsk || $noBid || $noAsk) {
                    $yesBid = round($yesBid, 3);
                    $yesAsk = round($yesAsk, 3);
                    $noBid  = round($noBid,  3);
                    $noAsk  = round($noAsk,  3);
                    $spread    = ($yesAsk && $yesBid) ? round($yesAsk - $yesBid, 3) : null;
                    $mid       = ($yesAsk && $yesBid) ? round(($yesBid + $yesAsk) / 2, 3) : null;
                    $denom     = $yesBid + $noBid;
                    $imbalance = $denom > 0 ? round(($yesBid - $noBid) / $denom, 3) : null;
                    $clobData  = [
                        'yes_bid'   => $yesBid ?: null,
                        'yes_ask'   => $yesAsk ?: null,
                        'no_bid'    => $noBid  ?: null,
                        'no_ask'    => $noAsk  ?: null,
                        'spread'    => $spread,
                        'mid'       => $mid,
                        'imbalance' => $imbalance,
                        'window_id' => $windowId,
                        'ts'        => (int) (microtime(true) * 1000),
                    ];
                }
            }
        }

        return ['oracle' => $oracle, 'clob' => $clobData];
    }
}
