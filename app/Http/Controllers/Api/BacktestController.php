<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BacktestRequest;
use App\Http\Resources\WindowFeatureResource;
use App\Models\WindowFeature;
use Illuminate\Http\JsonResponse;

class BacktestController extends Controller
{
    public function run(BacktestRequest $request): JsonResponse
    {
        $user      = $request->user();
        $limitDays = $user->historyLimitDays();

        $query = WindowFeature::query()
            ->when($request->asset, fn ($q) => $q->where('asset', strtoupper($request->asset)))
            ->when($request->duration, fn ($q) => $q->where('duration_sec', $request->duration))
            ->when($request->from, fn ($q) => $q->where('open_ts', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->where('open_ts', '<=', $request->to))
            ->when($request->quality === 'strict', fn ($q) => $q
                ->where('has_full_oracle_coverage', true)
                ->where('has_clob_coverage', true)
                ->where('recording_gap', false))
            ->when($limitDays, fn ($q) => $q->where('open_ts', '>=', now()->subDays($limitDays)->timestamp * 1000));

        foreach ($request->conditions as $condition) {
            $query->where($condition['field'], $condition['op'], $condition['value']);
        }

        $total   = (clone $query)->count();
        $results = $query->limit(5000)->get();

        return response()->json([
            'data'    => WindowFeatureResource::collection($results),
            'total'   => $total,
            'matched' => $results->count(),
        ]);
    }
}
