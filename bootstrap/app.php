<?php

use App\Http\Middleware\EnforceTierAccess;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(ForceJsonResponse::class);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'tier'   => EnforceTierAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'error' => 'Unauthenticated.',
                'code'  => 'UNAUTHENTICATED',
            ], 401);
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            return response()->json([
                'error' => 'Too many requests.',
                'code'  => 'RATE_LIMIT_EXCEEDED',
            ], 429)->withHeaders([
                'Retry-After' => $e->getHeaders()['Retry-After'] ?? 60,
            ]);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'error'  => 'Validation failed.',
                'code'   => 'VALIDATION_ERROR',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            return response()->json([
                'error' => 'Resource not found.',
                'code'  => 'NOT_FOUND',
            ], 404);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            return response()->json([
                'error' => $e->getMessage() ?: 'Forbidden.',
                'code'  => 'FORBIDDEN',
            ], 403);
        });
    })->create();
