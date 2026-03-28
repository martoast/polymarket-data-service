<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClobSnapshotsRequest;
use App\Http\Resources\ClobSnapshotResource;
use App\Models\Asset;
use App\Models\ClobSnapshot;
use Illuminate\Http\JsonResponse;

class ClobController extends Controller
{
    public function snapshots(ClobSnapshotsRequest $request): JsonResponse
    {
        if ($request->filled('window_id')) {
            $query = ClobSnapshot::with('asset')
                ->where('window_id', $request->window_id)
                ->orderBy('ts', 'asc');
        } else {
            $asset = Asset::where('symbol', strtoupper($request->asset))->firstOrFail();

            $query = ClobSnapshot::with('asset')
                ->where('asset_id', $asset->id)
                ->where('ts', '>=', $request->from)
                ->where('ts', '<=', $request->to)
                ->orderBy('ts', 'asc');
        }

        $paginated = $query->cursorPaginate($request->integer('per_page', 500));

        return response()->json([
            'data'        => ClobSnapshotResource::collection($paginated->items()),
            'next_cursor' => $paginated->nextCursor()?->encode(),
            'has_more'    => $paginated->hasMorePages(),
        ]);
    }
}
