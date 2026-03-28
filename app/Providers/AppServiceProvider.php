<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api-tier', function (Request $request) {
            $user = $request->user();

            if (! $user) {
                return Limit::perDay(50)->by($request->ip());
            }

            return match ($user->tier) {
                'enterprise' => Limit::none(),
                'pro'        => Limit::perDay(100000)->by($user->id),
                'builder'    => Limit::perDay(10000)->by($user->id),
                default      => Limit::perDay(100)->by($user->id),
            };
        });
    }
}
