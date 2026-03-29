<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClobSnapshot;
use App\Models\OracleTick;
use Illuminate\Http\JsonResponse;

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

        // Latest CLOB snapshot for any window
        $clob = ClobSnapshot::orderByDesc('ts')->first();

        if ($clob) {
            $yesBid = round((float) $clob->yes_bid, 3);
            $yesAsk = round((float) $clob->yes_ask, 3);
            $noBid  = round((float) $clob->no_bid,  3);
            $noAsk  = round((float) $clob->no_ask,  3);
            $spread = round($yesAsk - $yesBid, 3);
            $mid    = round(($yesBid + $yesAsk) / 2, 3);
            $denom  = $yesBid + $noBid;
            $imbalance = $denom > 0 ? round(($yesBid - $noBid) / $denom, 3) : 0;
            $clobData = compact('yesBid', 'yesAsk', 'noBid', 'noAsk', 'spread', 'mid', 'imbalance') + ['ts' => (int) $clob->ts];
            // snake_case keys for consistency
            $clobData = [
                'yes_bid'   => $yesBid,
                'yes_ask'   => $yesAsk,
                'no_bid'    => $noBid,
                'no_ask'    => $noAsk,
                'spread'    => $spread,
                'mid'       => $mid,
                'imbalance' => $imbalance,
                'ts'        => (int) $clob->ts,
            ];
        }

        return response()->json([
            'oracle' => $oracle,
            'clob'   => $clob ? $clobData : null,
        ]);
    }
}
