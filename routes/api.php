<?php

use App\Http\Controllers\Api\MobileApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile Application API Routes (v1)
|--------------------------------------------------------------------------
| Strictly UC-Scoped, Offline-First Voter Verification System.
| Supports candidate login, device limit quota check, complete UC data download,
| and silent background telemetry synchronization.
*/

Route::prefix('v1')->group(function () {
    // 1. Authentication & Device Authorization
    Route::match(['GET', 'POST'], '/auth/login', [MobileApiController::class, 'login']);
    Route::get('/auth/check-device', [MobileApiController::class, 'checkDevice']);

    // 2. Full UC Offline Dataset Download (Voters, Polling Stations, Blocks)
    Route::get('/download', [MobileApiController::class, 'downloadUcData']);
    Route::get('/uc/{uc}/download', [MobileApiController::class, 'downloadUcData']);

    // 3. Silent Background Telemetry & Analytics Sync
    Route::post('/sync/searches', [MobileApiController::class, 'syncSearches']);
    Route::post('/sync/heartbeat', [MobileApiController::class, 'heartbeat']);

    // 4. Live Online Voter Search Fallback
    Route::post('/voters/search', [MobileApiController::class, 'searchVoters']);
    Route::get('/voters/search', [MobileApiController::class, 'searchVoters']);
});

// Legacy / Direct endpoints
Route::get('/ucs', [MobileApiController::class, 'ucs']);
Route::get('/ucs/{uc}/voters', [MobileApiController::class, 'voters']);
Route::get('/ucs/{uc}/block-codes', [MobileApiController::class, 'blockCodes']);
Route::get('/ucs/{uc}/polling-stations', [MobileApiController::class, 'pollingStations']);
