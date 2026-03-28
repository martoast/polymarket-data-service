<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\WindowController;
use App\Http\Controllers\Api\WindowFeatureController;
use App\Http\Controllers\Api\OracleController;
use App\Http\Controllers\Api\ClobController;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\BacktestController;
use App\Http\Controllers\Api\ExportController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => response()->json([
    'api'     => 'polymarket-data',
    'version' => '1.0',
    'docs'    => 'https://github.com/martoast/polymarket-data-service',
]));

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/webhooks/stripe', [WebhookController::class, 'handle']);
Route::get('/health', [HealthController::class, 'check']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'active'])->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/token/regenerate', [AuthController::class, 'regenerateToken']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // Billing
    Route::post('/billing/checkout', [BillingController::class, 'checkout']);
    Route::get('/billing/portal', [BillingController::class, 'portal']);
    Route::get('/billing/subscription', [BillingController::class, 'subscription']);

    // Data endpoints — rate limited by tier
    Route::middleware(['throttle:api-tier'])->prefix('v1')->group(function () {
        Route::get('/windows', [WindowController::class, 'index']);
        Route::get('/windows/{id}', [WindowController::class, 'show']);

        Route::get('/features', [WindowFeatureController::class, 'index']);

        Route::get('/oracle/ticks', [OracleController::class, 'ticks']);
        Route::get('/oracle/range', [OracleController::class, 'range']);
        Route::get('/oracle/aligned', [OracleController::class, 'aligned']);

        Route::get('/clob/snapshots', [ClobController::class, 'snapshots']);

        Route::get('/markets/active', [MarketController::class, 'active']);

        // Pro tier only
        Route::middleware('tier:pro')->group(function () {
            Route::post('/backtest', [BacktestController::class, 'run']);
            Route::get('/export/sqlite', [ExportController::class, 'sqlite']);
            Route::get('/export/csv', [ExportController::class, 'csv']);
        });
    });
});
