<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BacktestController extends Controller
{
    public function run(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'endpoint coming soon'], 200);
    }
}
