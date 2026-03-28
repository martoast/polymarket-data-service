<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ClobController extends Controller
{
    public function snapshots(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'endpoint coming soon'], 200);
    }
}
