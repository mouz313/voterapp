<?php

use App\Http\Controllers\Api\MobileApiController;
use App\Http\Middleware\CandidateAuthMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Application API Routes (v1)
|--------------------------------------------------------------------------
| Strictly UC-Scoped, Offline-First Voter Verification System.
| Enforces verifiable session token authentication and per-UC authorization.
*/

Route::prefix('v1')->group(function () {
    // 1. Public Authentication & Token Issuance
    Route::match(['GET', 'POST'], '/auth/login', [MobileApiController::class, 'login']);

    // 2. Protected Candidate Device Endpoints (Enforcing Token, Revocation & UC Scope)
    Route::middleware([CandidateAuthMiddleware::class])->group(function () {
        Route::get('/auth/check-device', [MobileApiController::class, 'checkDevice']);

        // Full UC Offline Dataset Download (Strictly Candidate's Assigned UC)
        Route::get('/download', [MobileApiController::class, 'downloadUcData']);
        Route::get('/uc/{uc}/download', [MobileApiController::class, 'downloadUcData']);

        // Silent Background Telemetry & Analytics Sync
        Route::post('/sync/searches', [MobileApiController::class, 'syncSearches']);
        Route::post('/sync/heartbeat', [MobileApiController::class, 'heartbeat']);

        // Live Online Voter Search Fallback (Scoped strictly to Candidate's UC)
        Route::match(['GET', 'POST'], '/voters/search', [MobileApiController::class, 'searchVoters']);
    });
});

// Legacy / Direct endpoints (Secured with CandidateAuthMiddleware)
Route::middleware([CandidateAuthMiddleware::class])->group(function () {
    Route::get('/ucs', [MobileApiController::class, 'ucs']);
    Route::get('/ucs/{uc}/voters', [MobileApiController::class, 'voters']);
    Route::get('/ucs/{uc}/block-codes', [MobileApiController::class, 'blockCodes']);
    Route::get('/ucs/{uc}/polling-stations', [MobileApiController::class, 'pollingStations']);
});
