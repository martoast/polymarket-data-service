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

        return response()->json([
            'oracle' => $oracle,
            'clob'   => $clob ? [
                'yes_bid' => round((float) $clob->yes_bid, 3),
                'yes_ask' => round((float) $clob->yes_ask, 3),
                'no_bid'  => round((float) $clob->no_bid,  3),
                'no_ask'  => round((float) $clob->no_ask,  3),
                'ts'      => (int) $clob->ts,
            ] : null,
        ]);
    }
}
