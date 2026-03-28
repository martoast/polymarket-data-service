<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WindowIndexRequest;
use App\Http\Resources\WindowResource;
use App\Models\Window;
use Illuminate\Http\JsonResponse;

class WindowController extends Controller
{
    public function index(WindowIndexRequest $request): JsonResponse
    {
        $user      = $request->user();
        $limitDays = $user->historyLimitDays();

        $query = Window::with('asset')
            ->when($request->asset, fn ($q) => $q->whereHas('asset', fn ($q2) => $q2->where('symbol', strtoupper($request->asset))))
            ->when($request->duration, fn ($q) => $q->where('duration_sec', $request->duration))
            ->when($request->outcome, fn ($q) => $q->where('outcome', $request->outcome))
            ->when($request->has('has_coverage') && $request->has_coverage, fn ($q) => $q->withCoverage())
            ->when($request->from, fn ($q) => $q->where('open_ts', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->where('open_ts', '<=', $request->to))
            ->when($limitDays, fn ($q) => $q->where('open_ts', '>=', now()->subDays($limitDays)->timestamp * 1000))
            ->orderBy('open_ts', 'desc');

        $paginated = $query->cursorPaginate($request->integer('per_page', 100));

        return response()->json([
            'data'        => WindowResource::collection($paginated->items()),
            'next_cursor' => $paginated->nextCursor()?->encode(),
            'has_more'    => $paginated->hasMorePages(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $window = Window::with('asset')->findOrFail($id);

        return response()->json(['data' => new WindowResource($window)]);
    }
}
