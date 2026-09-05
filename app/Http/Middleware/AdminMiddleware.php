<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Ensure the authenticated user has an 'admin' role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Forbidden: Administrator privileges required.',
                ], 403);
            }

            Auth::logout();

            return redirect()->route('login')->withErrors([
                'email' => 'Candidate accounts cannot access the Web Admin Portal. Please use the mobile application.',
            ]);
        }

        return $next($request);
    }
}
