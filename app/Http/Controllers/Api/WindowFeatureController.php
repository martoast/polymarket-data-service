<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WindowFeatureIndexRequest;
use App\Http\Resources\WindowFeatureResource;
use App\Models\WindowFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\LazyCollection;

class WindowFeatureController extends Controller
{
    public function index(WindowFeatureIndexRequest $request): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user      = $request->user();
        $limitDays = $user->historyLimitDays();

        $query = WindowFeature::query()
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

        // Handle CSV export (Pro only)
        if ($request->format === 'csv') {
            if (! $user->isProTier()) {
                return response()->json([
                    'error' => 'CSV export requires the pro tier.',
                    'code'  => 'TIER_REQUIRED',
                ], 403);
            }

            $columns        = $request->columns ?? (new WindowFeature())->getFillable();
            $alwaysIncluded = ['window_id', 'asset', 'open_ts', 'outcome'];
            $selectColumns  = array_unique(array_merge($alwaysIncluded, $columns));

            return response()->streamDownload(function () use ($query, $selectColumns) {
                $out = fopen('php://output', 'w');
                fputcsv($out, $selectColumns);

                $query->select($selectColumns)->lazyById(500, 'window_id')->each(function ($feature) use ($out, $selectColumns) {
                    $row = array_map(fn ($col) => $feature->{$col} ?? '', $selectColumns);
                    fputcsv($out, $row);
                });

                fclose($out);
            }, 'polymarket-features-' . now()->format('Y-m-d') . '.csv', [
                'Content-Type' => 'text/csv',
            ]);
        }

        // Handle optional column selection
        if ($request->filled('columns')) {
            $alwaysIncluded = ['window_id', 'asset', 'open_ts', 'outcome'];
            $selectColumns  = array_unique(array_merge($alwaysIncluded, $request->columns));
            $query->select($selectColumns);
        }

        $paginated = $query->cursorPaginate($request->integer('per_page', 100));

        return response()->json([
            'data'        => WindowFeatureResource::collection($paginated->items()),
            'next_cursor' => $paginated->nextCursor()?->encode(),
            'has_more'    => $paginated->hasMorePages(),
        ]);
    }
}
