<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarketFeatureIndexRequest;
use App\Http\Resources\MarketFeatureResource;
use App\Models\CryptoMarketFeature;
use App\Models\WeatherMarketFeature;
use Illuminate\Http\JsonResponse;

class FeatureController extends Controller
{
    public function index(MarketFeatureIndexRequest $request): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user      = $request->user();
        $limitDays = $user->historyLimitDays();

        $model = match ($request->category) {
            'weather' => new WeatherMarketFeature(),
            default   => new CryptoMarketFeature(),
        };

        $query = $model::query()
            ->when($request->asset, fn ($q) => $q->where('asset', strtoupper($request->asset)))
            ->when($request->duration, fn ($q) => $q->where('duration_sec', $request->duration))
            ->when($request->outcome, fn ($q) => $q->where('outcome', $request->outcome))
            ->when($request->quality === 'strict', fn ($q) => $q
                ->where('has_full_oracle_coverage', true)
                ->where('has_clob_coverage', true)
                ->where('recording_gap', false))
            ->when($request->from, fn ($q) => $q->where('open_ts', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->where('open_ts', '<=', $request->to))
            ->when($limitDays, fn ($q) => $q->where('open_ts', '>=', now()->subDays($limitDays)->timestamp * 1000))
            ->orderBy('open_ts', 'desc');

        // CSV export — Pro only
        if ($request->format === 'csv') {
            if (! $user->isProTier()) {
                return response()->json(['error' => 'CSV export requires the pro tier.', 'code' => 'TIER_REQUIRED'], 403);
            }

            $columns        = $request->columns ?? $model->getFillable();
            $alwaysIncluded = ['market_id', 'asset', 'open_ts', 'outcome'];
            $selectColumns  = array_unique(array_merge($alwaysIncluded, $columns));

            return response()->streamDownload(function () use ($query, $selectColumns, $model) {
                $out = fopen('php://output', 'w');
                fputcsv($out, $selectColumns);

                $query->select($selectColumns)->lazyById(500, 'market_id')->each(function ($feature) use ($out, $selectColumns) {
                    fputcsv($out, array_map(fn ($col) => $feature->{$col} ?? '', $selectColumns));
                });

                fclose($out);
            }, 'polymarket-features-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
        }

        if ($request->filled('columns')) {
            $alwaysIncluded = ['market_id', 'asset', 'open_ts', 'outcome'];
            $query->select(array_unique(array_merge($alwaysIncluded, $request->columns)));
        }

        $paginated = $query->cursorPaginate($request->integer('per_page', 100));

        return response()->json([
            'data'        => MarketFeatureResource::collection($paginated->items()),
            'next_cursor' => $paginated->nextCursor()?->encode(),
            'has_more'    => $paginated->hasMorePages(),
        ]);
    }
}
