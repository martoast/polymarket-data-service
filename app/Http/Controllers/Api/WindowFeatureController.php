<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class WindowFeatureController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'endpoint coming soon'], 200);
    }
}
