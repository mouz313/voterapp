<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Validate the shared mobile API key (Bearer token or api_key query param).
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Never block login and public auth check endpoints
        if ($request->is('*/auth/login') || $request->is('v1/auth/login') || $request->is('api/v1/auth/login')) {
            return $next($request);
        }

        $expected = (string) config('mobile.api_key');

        // If MOBILE_API_KEY is not configured in .env, allow requests freely
        if ($expected === '') {
            return $next($request);
        }

        $provided = $request->bearerToken() ?? $request->query('api_key') ?? $request->header('X-API-KEY');

        if ($provided === null || !hash_equals($expected, (string) $provided)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Invalid or missing API key.',
            ], 401);
        }

        return $next($request);
    }
}
