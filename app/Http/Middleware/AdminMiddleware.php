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
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->isCandidate()) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Candidate accounts cannot access the Web Admin Portal. Please use the mobile application.',
            ]);
        }

        if (!$user->isAdmin() && !$user->isDataEntry()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Forbidden: Web portal access denied.',
                ], 403);
            }
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Unauthorized account access.',
            ]);
        }

        // Restrictions strictly for data_entry role
        if ($user->isDataEntry()) {
            // 1. NO DELETE: Data Entry Operators CANNOT delete any record
            if ($request->isMethod('delete')) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Forbidden: Data entry operators do not have permission to delete records.',
                    ], 403);
                }
                return back()->with('toast', [
                    'type' => 'error',
                    'message' => 'Action Unauthorized: Data entry operators cannot delete records.',
                ]);
            }

            // 2. FORBIDDEN SECTIONS: Candidates, Finance, Settings, Assemblies, Operators management
            if (
                $request->is('candidates*') ||
                $request->is('finance*') ||
                $request->is('settings*') ||
                $request->is('operators*') ||
                $request->is('national-assemblies*') ||
                $request->is('provincial-assemblies*') ||
                $request->is('districts*') ||
                $request->is('tehsils*') ||
                $request->is('api-docs*')
            ) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Forbidden: You do not have permission to access this module.',
                    ], 403);
                }
                return redirect()->route('voters.index')->with('toast', [
                    'type' => 'error',
                    'message' => 'Access Restricted: Data entry operators only have access to data entry modules.',
                ]);
            }
        }

        return $next($request);
    }
}
