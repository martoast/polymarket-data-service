<?php

use App\Http\Middleware\EnsureAdmin;
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
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [ForceJsonResponse::class]);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'tier'   => EnforceTierAccess::class,
            'admin'  => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Unauthenticated.',
                    'code'  => 'UNAUTHENTICATED',
                ], 401);
            }
            return redirect()->guest('/login');
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Too many requests.',
                    'code'  => 'RATE_LIMIT_EXCEEDED',
                ], 429)->withHeaders([
                    'Retry-After' => $e->getHeaders()['Retry-After'] ?? 60,
                ]);
            }
            return null;
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'  => 'Validation failed.',
                    'code'   => 'VALIDATION_ERROR',
                    'errors' => $e->errors(),
                ], 422);
            }
            return null;
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Resource not found.',
                    'code'  => 'NOT_FOUND',
                ], 404);
            }
            return null;
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage() ?: 'Forbidden.',
                    'code'  => 'FORBIDDEN',
                ], 403);
            }
            return null;
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() === 403 && $request->expectsJson()) {
                return response()->json([
                    'error' => $e->getMessage() ?: 'Forbidden.',
                    'code'  => str_contains($e->getMessage(), 'verified')
                        ? 'EMAIL_NOT_VERIFIED'
                        : 'FORBIDDEN',
                ], 403);
            }
            return null;
        });
    })->create();
