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
    Route::post('/auth/fcm-token', [\App\Http\Controllers\Api\CampaignController::class, 'updateFcmToken']);

    // Campaign White-Label Branding (Public for instant login screen morphing)
    Route::post('/auth/campaign-branding', [\App\Http\Controllers\Api\CampaignController::class, 'campaignBranding']);

    // Staff PIN Authentication
    Route::post('/staff/login', [\App\Http\Controllers\Api\CampaignController::class, 'staffLogin']);

    // Field Worker Operations
    Route::get('/staff/block/data', [\App\Http\Controllers\Api\CampaignController::class, 'staffBlockData']);
    Route::post('/staff/survey/sync', [\App\Http\Controllers\Api\CampaignController::class, 'staffSurveySync']);

    // Candidate Self-Service Staff Management
    Route::match(['GET', 'POST'], '/candidate/workers', [\App\Http\Controllers\Api\CampaignController::class, 'candidateWorkers']);
    Route::put('/candidate/workers/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'updateWorker']);
    Route::delete('/candidate/workers/{id}', [\App\Http\Controllers\Api\CampaignController::class, 'deleteWorker']);

    // Candidate War Room Mobile Dashboard
    Route::get('/candidate/war-room', [\App\Http\Controllers\Api\CampaignController::class, 'candidateWarRoom']);

    // Election Day Camp Thermal Parchi Issuance (GOTV Live Turnout)
    Route::post('/camp/issue-parchi', [\App\Http\Controllers\Api\CampaignController::class, 'campIssueParchi']);

    // Background Cron Automation (cron-job.org)
    Route::get('/cron/process-campaign-matrix', [\App\Http\Controllers\Api\CampaignController::class, 'processCampaignMatrix']);

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

        // Module 6: Union Council Relational Metadata
        Route::get('/ucs', [MobileApiController::class, 'ucs']);
        Route::get('/ucs/{uc}/voters', [MobileApiController::class, 'voters']);
        Route::get('/ucs/{uc}/block-codes', [MobileApiController::class, 'blockCodes']);
        Route::get('/ucs/{uc}/polling-stations', [MobileApiController::class, 'pollingStations']);
    });
});
