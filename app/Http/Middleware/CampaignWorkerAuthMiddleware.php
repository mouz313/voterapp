<?php

namespace App\Http\Middleware;

use App\Models\CampaignWorker;
use App\Models\CandidateDevice;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CampaignWorkerAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Enforces dual-path authentication: active Campaign Worker or Candidate token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return response()->json([
                'status' => false,
                'success' => false,
                'message' => 'Unauthorized: Missing authentication token. Please provide "Authorization: Bearer <token>" or "X-Worker-Token".',
            ], 401);
        }

        // 1. Path A: Campaign Worker Authentication
        $worker = CampaignWorker::with('candidate')
            ->where(function ($q) use ($token) {
                $q->where('api_token', $token)
                  ->orWhere('api_token', hash('sha256', $token));
            })
            ->first();

        if ($worker) {
            if (!$worker->is_active) {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'message' => 'Forbidden: Worker account is inactive or disabled. Contact your candidate.',
                ], 403);
            }

            $candidate = $worker->candidate;
            if (!$candidate || $candidate->status !== 'active') {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'message' => 'Forbidden: Associated candidate account is suspended or inactive.',
                ], 403);
            }

            // Bind worker and candidate to request
            $request->attributes->set('campaign_worker', $worker);
            $request->attributes->set('candidate_user', $candidate);
            $request->setUserResolver(fn () => $candidate);

            return $next($request);
        }

        // 2. Path B: Candidate Device Authentication
        $device = CandidateDevice::with('user')
            ->where(function ($q) use ($token) {
                $q->where('api_token', $token)
                  ->orWhere('api_token', hash('sha256', $token));
            })
            ->first();

        if ($device) {
            if ($device->is_revoked) {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'is_revoked' => true,
                    'message' => 'Forbidden: This device access has been revoked by administrator.',
                ], 403);
            }

            $candidate = $device->user;
            if (!$candidate || $candidate->status !== 'active') {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'message' => 'Forbidden: The candidate account is currently suspended.',
                ], 403);
            }

            // Update device telemetry
            $device->update([
                'last_active_at' => Carbon::now(),
                'ip_address' => $request->ip(),
            ]);

            $request->attributes->set('candidate_device', $device);
            $request->attributes->set('candidate_user', $candidate);
            $request->setUserResolver(fn () => $candidate);

            return $next($request);
        }

        // 3. Path C: Direct Candidate Code Authentication
        $candidateByCode = User::where('role', 'candidate')
            ->where('status', 'active')
            ->where(function ($q) use ($token) {
                $q->where('candidate_code', strtoupper($token))
                  ->orWhere('candidate_code', $token);
            })
            ->first();

        if ($candidateByCode) {
            $request->attributes->set('candidate_user', $candidateByCode);
            $request->setUserResolver(fn () => $candidateByCode);

            return $next($request);
        }

        // 4. Path D: Web Session Candidate Auth
        if (auth()->check() && auth()->user()->isCandidate() && auth()->user()->status === 'active') {
            $request->attributes->set('candidate_user', auth()->user());
            return $next($request);
        }

        // 5. If no valid entity found
        return response()->json([
            'status' => false,
            'success' => false,
            'message' => 'Unauthorized: Invalid or expired worker/candidate authentication token.',
        ], 401);
    }

    /**
     * Extract token from request headers, query, or body.
     */
    protected function extractToken(Request $request): ?string
    {
        $token = $request->bearerToken();

        if (!$token) {
            $authHeader = $request->header('Authorization')
                ?? $request->header('authorization')
                ?? $request->server('HTTP_AUTHORIZATION')
                ?? $request->server('REDIRECT_HTTP_AUTHORIZATION');

            if ($authHeader) {
                $token = preg_replace('/^\s*Bearer\s+/i', '', trim($authHeader));
            }
        }

        if (!$token) {
            $token = $request->header('X-Worker-Token')
                ?? $request->header('X-Candidate-Token')
                ?? $request->header('X-Device-Token')
                ?? $request->header('X-Auth-Token')
                ?? $request->header('token');
        }

        if (!$token) {
            $token = $request->input('token')
                ?? $request->input('api_token')
                ?? $request->query('token')
                ?? $request->query('api_token');
        }

        if ($token && is_string($token)) {
            $token = trim($token, " \t\n\r\0\x0B\"'");
            return $token !== '' ? $token : null;
        }

        return null;
    }
}
