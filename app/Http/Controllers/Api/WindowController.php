<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class WindowController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'endpoint coming soon'], 200);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => [], 'message' => 'endpoint coming soon'], 200);
    }
}
