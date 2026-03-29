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

        // Each WS event sets only one side at a time — aggregate latest non-null per field
        $recent = ClobSnapshot::orderByDesc('ts')->limit(1000)->get();
        $yesBid = $recent->whereNotNull('yes_bid')->first()?->yes_bid;
        $yesAsk = $recent->whereNotNull('yes_ask')->first()?->yes_ask;
        $noBid  = $recent->whereNotNull('no_bid')->first()?->no_bid;
        $noAsk  = $recent->whereNotNull('no_ask')->first()?->no_ask;

        $clobData = null;
        if ($yesBid || $yesAsk || $noBid || $noAsk) {
            $yesBid = round((float) $yesBid, 3);
            $yesAsk = round((float) $yesAsk, 3);
            $noBid  = round((float) $noBid,  3);
            $noAsk  = round((float) $noAsk,  3);
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
                'ts'        => (int) ($recent->first()?->ts ?? 0),
            ];
        }

        return response()->json([
            'oracle' => $oracle,
            'clob'   => $clobData,
        ]);
    }
}
