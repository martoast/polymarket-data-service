<?php

use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\BillingWebController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', fn () => view('landing'))->name('home');
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [WebAuthController::class, 'login'])->middleware('guest');
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [WebAuthController::class, 'register'])->middleware('guest');
Route::get('/docs', fn () => view('docs'))->name('docs');

// Authenticated web routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/regenerate-key', [DashboardController::class, 'regenerateKey'])->name('dashboard.regenerate');
    Route::get('/billing', [BillingWebController::class, 'index'])->name('billing');
    Route::post('/billing/checkout', [BillingWebController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/portal', [BillingWebController::class, 'portal'])->name('billing.portal');
});
