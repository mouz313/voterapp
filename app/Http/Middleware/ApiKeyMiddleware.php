<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Validate the shared mobile API key if provided.
     * Does not intercept candidate session tokens in the Authorization: Bearer header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Never block login, staff, or campaign endpoints
        if ($request->is('*auth/login*') || $request->is('*staff/*') || $request->is('*campaign*') || $request->is('*candidate/*')) {
            return $next($request);
        }

        // Allow candidate device and staff session tokens (vp_*)
        $bearer = $request->bearerToken();
        if ($bearer && (str_starts_with($bearer, 'vp_') || strlen($bearer) >= 30)) {
            return $next($request);
        }

        $expected = (string) config('mobile.api_key');

        // If MOBILE_API_KEY is not configured in .env, allow requests freely
        if ($expected === '') {
            return $next($request);
        }

        $provided = $request->header('X-API-KEY') ?? $request->query('api_key') ?? $bearer;

        if ($provided !== null && !hash_equals($expected, (string) $provided)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Invalid or missing API key.',
            ], 401);
        }

        return $next($request);
    }
}
