<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OracleController extends Controller
{
    public function ticks(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'endpoint coming soon'], 200);
    }

    public function range(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'endpoint coming soon'], 200);
    }

    public function aligned(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'endpoint coming soon'], 200);
    }
}
