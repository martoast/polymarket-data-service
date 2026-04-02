<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarketIndexRequest;
use App\Http\Resources\MarketResource;
use App\Models\Market;
use Illuminate\Http\JsonResponse;

class MarketController extends Controller
{
    public function index(MarketIndexRequest $request): JsonResponse
    {
        $user      = $request->user();
        $limitDays = $user->historyLimitDays();
        $nowMs     = (int) (microtime(true) * 1000);

        $query = Market::with('asset')
            ->where('break_value', '>', 0)
            ->where(fn ($q) => $q->whereNotNull('outcome')->orWhere('close_ts', '>', $nowMs))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->asset, fn ($q) => $q->whereHas('asset', fn ($q2) => $q2->where('symbol', strtoupper($request->asset))))
            ->when($request->duration, fn ($q) => $q->where('duration_sec', $request->duration))
            ->when($request->outcome, fn ($q) => $q->where('outcome', $request->outcome))
            ->when($request->has_coverage, fn ($q) => $q->withCoverage())
            ->when($request->from, fn ($q) => $q->where('open_ts', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->where('open_ts', '<=', $request->to))
            ->when($limitDays, fn ($q) => $q->where('open_ts', '>=', now()->subDays($limitDays)->timestamp * 1000))
            ->orderBy('open_ts', 'desc');

        $paginated = $query->cursorPaginate($request->integer('per_page', 100));

        return response()->json([
            'data'        => MarketResource::collection($paginated->items()),
            'next_cursor' => $paginated->nextCursor()?->encode(),
            'has_more'    => $paginated->hasMorePages(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $market = Market::with('asset')->findOrFail($id);

        return response()->json(['data' => new MarketResource($market)]);
    }

    public function active(MarketIndexRequest $request): JsonResponse
    {
        $nowMs = (int) (microtime(true) * 1000);

        $markets = Market::with('asset')
            ->whereNull('outcome')
            ->where('close_ts', '>', $nowMs)
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->orderBy('close_ts', 'asc')
            ->get();

        return response()->json(['data' => MarketResource::collection($markets)]);
    }
}
