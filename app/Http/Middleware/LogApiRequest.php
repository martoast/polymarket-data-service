<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user) {
            DB::table('api_request_logs')->insert([
                'user_id'    => $user->id,
                'method'     => $request->method(),
                'path'       => '/' . ltrim($request->path(), '/'),
                'status'     => $response->getStatusCode(),
                'ip'         => $request->ip(),
                'created_at' => now(),
            ]);
        }

        return $response;
    }
}
