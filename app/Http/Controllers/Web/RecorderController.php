<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Recorder\RecorderState;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RecorderController extends Controller
{
    public function index(): View
    {
        return view('admin.recorder');
    }

    public function status(): JsonResponse
    {
        $state = RecorderState::get();
        $nowMs = (int) (microtime(true) * 1000);

        // Supplement in-session counters with real DB totals
        $state['oracle_written']              = DB::table('oracle_ticks')->count();
        $state['clob']['snapshots_written']   = DB::table('clob_snapshots')->count();
        $state['candles_written']             = DB::table('candles_1m')->count();

        // Active markets list for the table — only already-opened windows (break price known)
        $state['active_markets'] = DB::table('windows')
            ->join('assets', 'assets.id', '=', 'windows.asset_id')
            ->whereNull('windows.outcome')
            ->where('windows.close_ts', '>', $nowMs)
            ->where('windows.open_ts', '<=', $nowMs)
            ->where('windows.break_price_usd', '>', 0)
            ->select('windows.id', 'assets.symbol', 'windows.duration_sec', 'windows.break_price_usd', 'windows.open_ts', 'windows.close_ts')
            ->orderBy('windows.close_ts')
            ->get()
            ->map(fn ($w) => [
                'id'              => $w->id,
                'asset'           => $w->symbol,
                'duration'        => $w->duration_sec >= 900 ? '15m' : '5m',
                'break_price_usd' => (float) $w->break_price_usd,
                'open_ts'         => (int) $w->open_ts,
                'close_ts'        => (int) $w->close_ts,
                'closes_in_ms'    => $w->close_ts - $nowMs,
            ]);

        return response()->json($state);
    }
}
