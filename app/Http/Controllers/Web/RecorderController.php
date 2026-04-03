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
        $state   = RecorderState::get();
        $weather = RecorderState::getWeather();
        $nowMs   = (int) (microtime(true) * 1000);

        // Supplement in-session counters with real DB totals
        $state['oracle_written']              = DB::table('oracle_ticks')->count();
        $state['clob']['snapshots_written']   = DB::table('clob_snapshots')
            ->join('markets', 'markets.id', '=', 'clob_snapshots.market_id')
            ->where('markets.category', 'crypto')
            ->count();
        $state['candles_written']             = DB::table('candles_1m')->count();

        // Weather counters from DB
        $weather['readings_written']          = DB::table('weather_readings')->count();
        $weather['clob']['snapshots_written'] = DB::table('clob_snapshots')
            ->join('markets', 'markets.id', '=', 'clob_snapshots.market_id')
            ->where('markets.category', 'weather')
            ->count();

        // Active crypto markets
        $state['active_markets'] = DB::table('markets')
            ->join('assets', 'assets.id', '=', 'markets.asset_id')
            ->where('markets.category', 'crypto')
            ->whereNull('markets.outcome')
            ->where('markets.close_ts', '>', $nowMs)
            ->where('markets.open_ts', '<=', $nowMs)
            ->where('markets.break_value', '>', 0)
            ->select('markets.id', 'assets.symbol', 'markets.break_value', 'markets.open_ts', 'markets.close_ts')
            ->orderBy('markets.close_ts')
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'asset'       => $m->symbol,
                'break_value' => (float) $m->break_value,
                'open_ts'     => (int) $m->open_ts,
                'close_ts'    => (int) $m->close_ts,
                'closes_in_ms' => $m->close_ts - $nowMs,
            ]);

        // Active weather markets
        $weather['active_markets'] = DB::table('markets')
            ->join('assets', 'assets.id', '=', 'markets.asset_id')
            ->where('markets.category', 'weather')
            ->whereNull('markets.outcome')
            ->where('markets.close_ts', '>', $nowMs)
            ->where('markets.open_ts', '<=', $nowMs)
            ->select('markets.id', 'assets.symbol', 'markets.break_value', 'markets.open_ts', 'markets.close_ts')
            ->orderBy('markets.close_ts')
            ->get()
            ->map(fn ($m) => [
                'id'          => $m->id,
                'asset'       => $m->symbol,
                'break_value' => (float) $m->break_value,
                'open_ts'     => (int) $m->open_ts,
                'close_ts'    => (int) $m->close_ts,
                'closes_in_ms' => $m->close_ts - $nowMs,
            ]);

        return response()->json([
            'crypto'  => $state,
            'weather' => $weather,
        ]);
    }
}
