<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Recorder\RecorderState;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RecorderController extends Controller
{
    public function index(): View
    {
        return view('admin.recorder');
    }

    public function status(): JsonResponse
    {
        return response()->json(RecorderState::get());
    }
}
