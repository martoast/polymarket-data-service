<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OracleTick;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PublicLiveController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $data = Cache::remember('public_live_v3', 5, fn () => $this->build());

        return response()->json($data)
            ->header('Cache-Control', 'public, max-age=5');
    }

    private function build(): array
    {
        $nowMs     = (int) (microtime(true) * 1000);
        $assets    = ['BTC', 'ETH', 'SOL'];
        $durations = [300 => '5m', 900 => '15m'];

        // Latest oracle tick per symbol
        $oracle = collect($assets)->mapWithKeys(function (string $symbol) {
            $tick = OracleTick::whereHas('asset', fn ($q) => $q->where('symbol', $symbol))
                ->orderByDesc('ts')
                ->first();
            return $tick ? [$symbol => [
                'price_usd' => (float) $tick->price_usd,
                'ts'        => (int)   $tick->ts,
            ]] : [];
        })->filter();

        // Latest CLOB snapshot per asset per duration
        $clob = collect($assets)->mapWithKeys(function (string $symbol) use ($nowMs, $durations) {
            $assetId = DB::table('assets')->where('symbol', $symbol)->value('id');
            if (!$assetId) return [$symbol => null];

            $byDuration = [];
            foreach ($durations as $durationSec => $label) {
                $entry = $this->clobForWindow($assetId, $durationSec, $nowMs);
                if ($entry) {
                    $byDuration[$label] = $entry;
                }
            }

            return $byDuration ? [$symbol => $byDuration] : [$symbol => null];
        })->filter();

        return ['oracle' => $oracle, 'clob' => $clob];
    }

    private function clobForWindow(int $assetId, int $durationSec, int $nowMs): ?array
    {
        $windowId = DB::table('clob_snapshots')
            ->join('windows', 'clob_snapshots.window_id', '=', 'windows.id')
            ->where('clob_snapshots.asset_id', $assetId)
            ->where('windows.duration_sec', $durationSec)
            ->where('windows.close_ts', '>', $nowMs)
            ->whereNull('windows.outcome')
            ->orderByDesc('clob_snapshots.ts')
            ->value('clob_snapshots.window_id');

        if (!$windowId) return null;

        $pick = fn (string $col) => DB::table('clob_snapshots')
            ->where('window_id', $windowId)
            ->whereNotNull($col)
            ->orderByDesc('ts')
            ->value($col);

        $yesBid = round((float) ($pick('yes_bid') ?? 0), 3);
        $yesAsk = round((float) ($pick('yes_ask') ?? 0), 3);
        $noBid  = round((float) ($pick('no_bid')  ?? 0), 3);
        $noAsk  = round((float) ($pick('no_ask')  ?? 0), 3);

        if (!$yesBid && !$yesAsk && !$noBid && !$noAsk) return null;

        $spread    = ($yesAsk && $yesBid) ? round($yesAsk - $yesBid, 3) : null;
        $mid       = ($yesAsk && $yesBid) ? round(($yesBid + $yesAsk) / 2, 3) : null;
        $denom     = $yesBid + $noBid;
        $imbalance = $denom > 0 ? round(($yesBid - $noBid) / $denom, 3) : null;

        return [
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
