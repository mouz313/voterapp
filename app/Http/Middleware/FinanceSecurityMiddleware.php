<?php

namespace App\Http\Middleware;

use App\Models\FinanceSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinanceSecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ensure user is logged in
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 2. Check if finance is unlocked
        if (!session('finance_unlocked')) {
            if (!$request->routeIs('finance.unlock*')) {
                session(['finance_intended_url' => $request->fullUrl()]);
                return redirect()->route('finance.unlock')
                    ->with('toast', ['type' => 'warning', 'message' => 'Security password required to access Finance & Sales Vault.']);
            }
        } else {
            // 3. Check session timeout
            $timeoutMinutes = (int) FinanceSetting::get('session_timeout_minutes', 60);
            $unlockedAt = session('finance_unlocked_at');

            if ($unlockedAt && now()->diffInMinutes($unlockedAt) > $timeoutMinutes) {
                session()->forget(['finance_unlocked', 'finance_unlocked_at']);
                session(['finance_intended_url' => $request->fullUrl()]);

                return redirect()->route('finance.unlock')
                    ->with('toast', ['type' => 'info', 'message' => 'Finance vault session timed out. Please enter password again.']);
            }

            // Update heartbeat
            session(['finance_unlocked_at' => now()]);
        }

        return $next($request);
    }
}
