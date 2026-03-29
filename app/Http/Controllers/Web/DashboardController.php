<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OracleTick;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user     = $request->user();
        $tierInfo = [
            'limit_days'  => $user->historyLimitDays(),
            'daily_limit' => $user->dailyRateLimit(),
        ];

        try {
            $lastOracleTs = OracleTick::max('ts');
        } catch (\Throwable $e) {
            $lastOracleTs = null;
        }

        return view('dashboard', compact('user', 'tierInfo', 'lastOracleTs'));
    }

    public function regenerateKey(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $plain = $user->createToken('api')->plainTextToken;
        $user->update(['api_key' => $plain]);

        return redirect()->route('dashboard')->with('success', 'API key regenerated.');
    }
}
