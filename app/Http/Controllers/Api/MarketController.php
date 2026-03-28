<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WindowResource;
use App\Models\Window;
use Illuminate\Http\JsonResponse;

class MarketController extends Controller
{
    public function active(): JsonResponse
    {
        $nowMs = now()->timestamp * 1000;

        $windows = Window::with('asset')
            ->whereNull('outcome')
            ->where('close_ts', '>', $nowMs)
            ->orderBy('close_ts', 'asc')
            ->get();

        return response()->json([
            'data' => WindowResource::collection($windows),
        ]);
    }
}
