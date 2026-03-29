<?php

use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\BillingWebController;
use App\Http\Controllers\Web\RecorderController;
use App\Http\Controllers\Web\Admin\UsersAdminController;
use App\Http\Controllers\Web\Admin\RequestsAdminController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', fn () => view('landing'))->name('home');
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [WebAuthController::class, 'login'])->middleware('guest');
Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [WebAuthController::class, 'register'])->middleware('guest');
Route::get('/docs', fn () => view('docs'))->name('docs');

// Email verification routes
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('dashboard')->with('status', 'Email verified! Welcome aboard.');
    })->middleware('signed')->name('verification.verify');
    Route::post('/email/resend', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'Verification link sent!');
    })->middleware('throttle:6,1')->name('verification.send');
});

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/recorder', [RecorderController::class, 'index'])->name('admin.recorder');
    Route::get('/recorder/status', [RecorderController::class, 'status'])->name('admin.recorder.status');
    Route::get('/users', [UsersAdminController::class, 'index'])->name('admin.users');
    Route::get('/requests', [RequestsAdminController::class, 'index'])->name('admin.requests');
});

// Authenticated web routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/regenerate-key', [DashboardController::class, 'regenerateKey'])->name('dashboard.regenerate');
    Route::get('/billing', [BillingWebController::class, 'index'])->name('billing');
    Route::post('/billing/checkout', [BillingWebController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/portal', [BillingWebController::class, 'portal'])->name('billing.portal');
});
