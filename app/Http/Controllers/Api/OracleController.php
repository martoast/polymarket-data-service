<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OracleRangeRequest;
use App\Http\Requests\OracleTicksRequest;
use App\Http\Resources\OracleTickResource;
use App\Models\Asset;
use App\Models\OracleTick;
use App\Models\Window;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OracleController extends Controller
{
    public function ticks(OracleTicksRequest $request): JsonResponse
    {
        $user      = $request->user();
        $limitDays = $user->historyLimitDays();

        $asset = Asset::where('symbol', strtoupper($request->asset))->firstOrFail();

        $query = OracleTick::with('asset')
            ->where('asset_id', $asset->id)
            ->when($request->from, fn ($q) => $q->where('ts', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->where('ts', '<=', $request->to))
            ->when($limitDays, fn ($q) => $q->where('ts', '>=', now()->subDays($limitDays)->timestamp * 1000))
            ->orderBy('ts', 'asc');

        $paginated = $query->cursorPaginate($request->integer('per_page', 500));

        return response()->json([
            'data'        => OracleTickResource::collection($paginated->items()),
            'next_cursor' => $paginated->nextCursor()?->encode(),
            'has_more'    => $paginated->hasMorePages(),
        ]);
    }

    public function range(OracleRangeRequest $request): JsonResponse
    {
        $asset = Asset::where('symbol', strtoupper($request->asset))->firstOrFail();

        // Single pass: aggregate + first/last via ordered array_agg
        $stats = OracleTick::where('asset_id', $asset->id)
            ->where('ts', '>=', $request->from)
            ->where('ts', '<=', $request->to)
            ->selectRaw('
                MIN(price_bp)                             AS min_price_bp,
                MAX(price_bp)                             AS max_price_bp,
                COUNT(*)                                  AS tick_count,
                (array_agg(price_bp ORDER BY ts ASC))[1]  AS first_price_bp,
                (array_agg(price_bp ORDER BY ts DESC))[1] AS last_price_bp
            ')
            ->first();

        return response()->json([
            'data' => [
                'asset'           => strtoupper($request->asset),
                'from'            => (int) $request->from,
                'to'              => (int) $request->to,
                'min_price_bp'    => $stats->min_price_bp,
                'max_price_bp'    => $stats->max_price_bp,
                'first_price_bp'  => $stats->first_price_bp,
                'last_price_bp'   => $stats->last_price_bp,
                'tick_count'      => (int) $stats->tick_count,
            ],
        ]);
    }

    public function aligned(Request $request): JsonResponse
    {
        $request->validate([
            'asset'     => ['required', 'string'],
            'window_id' => ['required', 'string'],
        ]);

        $window = Window::findOrFail($request->window_id);
        $asset  = Asset::where('symbol', strtoupper($request->asset))->firstOrFail();

        $ticks = OracleTick::with('asset')
            ->where('asset_id', $asset->id)
            ->where('ts', '>=', $window->open_ts)
            ->where('ts', '<=', $window->close_ts)
            ->orderBy('ts', 'asc')
            ->get();

        return response()->json([
            'data' => OracleTickResource::collection($ticks),
        ]);
    }
}
