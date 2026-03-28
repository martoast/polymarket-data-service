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
        $user        = $request->user();
        $apiToken    = session('api_token'); // Only available right after login/register
        $limitDays   = $user->historyLimitDays();
        $dailyLimit  = $user->dailyRateLimit();

        $tierInfo = [
            'limit_days'  => $limitDays,
            'daily_limit' => $dailyLimit,
        ];

        try {
            $lastOracleTs = OracleTick::max('ts');
        } catch (\Throwable $e) {
            $lastOracleTs = null;
        }

        return view('dashboard', compact('user', 'apiToken', 'tierInfo', 'lastOracleTs'));
    }

    public function regenerateKey(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $token = $user->createToken('web-session')->plainTextToken;
        session(['api_token' => $token]);

        return redirect()->route('dashboard')->with('success', 'API key regenerated successfully.');
    }
}
