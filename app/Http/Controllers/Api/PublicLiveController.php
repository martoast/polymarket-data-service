<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClobSnapshot;
use App\Models\OracleTick;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PublicLiveController extends Controller
{
    public function __invoke(): JsonResponse
    {
        // Latest price tick per asset
        $ticks = OracleTick::with('asset')
            ->orderByDesc('ts')
            ->limit(50)
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

        // Find the most recently active BTC window and get latest per-field values from it
        $nowMs = (int) (microtime(true) * 1000);
        $btcAssetId = DB::table('assets')->where('symbol', 'BTC')->value('id');

        $clobData = null;
        if ($btcAssetId) {
            // Pick the active BTC window with the most recent CLOB snapshot
            $windowId = DB::table('clob_snapshots')
                ->join('windows', 'clob_snapshots.window_id', '=', 'windows.id')
                ->where('clob_snapshots.asset_id', $btcAssetId)
                ->where('windows.close_ts', '>', $nowMs)
                ->whereNull('windows.outcome')
                ->orderByDesc('clob_snapshots.ts')
                ->value('clob_snapshots.window_id');

            if ($windowId) {
                // Grab last 500 snapshots from that single window to get latest per-field
                $snaps = ClobSnapshot::where('window_id', $windowId)
                    ->orderByDesc('ts')
                    ->limit(500)
                    ->get();

                $yesBid = (float) ($snaps->whereNotNull('yes_bid')->first()?->yes_bid ?? 0);
                $yesAsk = (float) ($snaps->whereNotNull('yes_ask')->first()?->yes_ask ?? 0);
                $noBid  = (float) ($snaps->whereNotNull('no_bid')->first()?->no_bid  ?? 0);
                $noAsk  = (float) ($snaps->whereNotNull('no_ask')->first()?->no_ask  ?? 0);

                if ($yesBid || $yesAsk || $noBid || $noAsk) {
                    $yesBid = round($yesBid, 3);
                    $yesAsk = round($yesAsk, 3);
                    $noBid  = round($noBid,  3);
                    $noAsk  = round($noAsk,  3);
                    $spread = ($yesAsk && $yesBid) ? round($yesAsk - $yesBid, 3) : null;
                    $mid    = ($yesAsk && $yesBid) ? round(($yesBid + $yesAsk) / 2, 3) : null;
                    $denom  = $yesBid + $noBid;
                    $imbalance = $denom > 0 ? round(($yesBid - $noBid) / $denom, 3) : null;
                    $clobData = [
                        'yes_bid'   => $yesBid ?: null,
                        'yes_ask'   => $yesAsk ?: null,
                        'no_bid'    => $noBid  ?: null,
                        'no_ask'    => $noAsk  ?: null,
                        'spread'    => $spread,
                        'mid'       => $mid,
                        'imbalance' => $imbalance,
                        'window_id' => $windowId,
                        'ts'        => (int) ($snaps->first()?->ts ?? 0),
                    ];
                }
            }
        }

        return response()->json([
            'oracle' => $oracle,
            'clob'   => $clobData,
        ]);
    }
}
