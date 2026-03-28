<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTierAccess
{
    public function handle(Request $request, Closure $next, string $requiredTier): Response
    {
        $tierOrder = ['free' => 0, 'builder' => 1, 'pro' => 2, 'enterprise' => 3];

        $userTier  = $request->user()?->tier ?? 'free';
        $userLevel = $tierOrder[$userTier] ?? 0;
        $required  = $tierOrder[$requiredTier] ?? 99;

        if ($userLevel < $required) {
            return response()->json([
                'error' => "This endpoint requires the {$requiredTier} tier or higher.",
                'code'  => 'INSUFFICIENT_TIER',
            ], 403);
        }

        return $next($request);
    }
}
