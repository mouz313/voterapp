<?php

namespace App\Http\Middleware;

use App\Models\CandidateDevice;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CandidateAuthMiddleware
{
    /**
     * Authenticate candidate device session token and enforce device & subscription status.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() 
            ?? $request->header('X-Device-Token') 
            ?? $request->header('X-Candidate-Token') 
            ?? $request->query('token');

        if (!$token || trim($token) === '') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Missing device session token. Please provide "Authorization: Bearer <token>".',
            ], 401);
        }

        $device = CandidateDevice::with(['user.uc'])->where('api_token', trim($token))->first();

        if (!$device) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Invalid or expired device session token. Please re-authenticate via /api/v1/auth/login.',
            ], 401);
        }

        // 1. Enforce device revocation check on EVERY request
        if ($device->is_revoked) {
            return response()->json([
                'status' => false,
                'is_revoked' => true,
                'message' => 'Forbidden: This device access has been revoked/blocked by the administrator.',
            ], 403);
        }

        $candidate = $device->user;

        // 2. Enforce candidate account existence and active status
        if (!$candidate || $candidate->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: The candidate account is currently suspended. Please contact the administrator.',
            ], 403);
        }

        // 3. Enforce candidate subscription expiration
        if ($candidate->expires_at && $candidate->expires_at->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: The candidate account subscription expired on ' . $candidate->expires_at->format('Y-m-d') . '. Access restricted.',
            ], 403);
        }

        // 4. Enforce candidate has an assigned Union Council (UC)
        if (!$candidate->uc_id) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: No Union Council (UC) is assigned to this candidate account. Please contact administrator.',
            ], 403);
        }

        // Update telemetry
        $device->last_active_at = Carbon::now();
        $device->ip_address = $request->ip();
        $device->save();

        // Bind candidate user and device to the current request
        $request->setUserResolver(fn () => $candidate);
        $request->attributes->set('candidate_device', $device);
        $request->attributes->set('candidate_user', $candidate);

        return $next($request);
    }
}
