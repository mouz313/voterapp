<?php

use App\Http\Controllers\BlockCodesController;
use App\Http\Controllers\CandidatesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistrictsController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\NationalAssembliesController;
use App\Http\Controllers\PdfImportController;
use App\Http\Controllers\PollingStationsController;
use App\Http\Controllers\ProvincialAssembliesController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TehsilsController;
use App\Http\Controllers\UCsController;
use App\Http\Controllers\VotersController;
use App\Models\PollingStation;
use App\Models\UC;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', fn () => redirect('/login'));

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        // Candidate accounts cannot access web admin console
        if (Auth::user()->isCandidate()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Candidate accounts cannot access the Web Admin Console. Please use the mobile application.']);
        }

        return redirect()->intended('/dashboard');
    }

    return back()
        ->withInput($request->only('email', 'remember'))
        ->withErrors(['email' => 'Invalid credentials.']);
});

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink($request->only('email'));

    return $status === Password::RESET_LINK_SENT
        ? back()->with('status', __($status))
        : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
})->name('password.email');

Route::get('/reset-password/{token}', function (string $token, Request $request) {
    return view('auth.reset-password', [
        'token' => $token,
        'email' => $request->query('email', ''),
    ]);
})->name('password.reset');

Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->setRememberToken(Str::random(60));

            $user->save();

            event(new PasswordReset($user));
        }
    );

    return $status === Password::PASSWORD_RESET
        ? redirect()->route('login')->with('status', __($status))
        : back()->withInput($request->only('email'))->withErrors(['email' => [__($status)]]);
})->name('password.update');

Route::middleware(['auth', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/search-telemetry', [DashboardController::class, 'searchTelemetry'])->name('dashboard.search-telemetry');

    // Constituencies / Assemblies Hierarchy
    Route::resource('national-assemblies', NationalAssembliesController::class)->except(['show']);
    Route::resource('provincial-assemblies', ProvincialAssembliesController::class)->except(['show']);

    // Administrative Locations
    Route::resource('districts', DistrictsController::class)->except(['show']);
    Route::resource('tehsils', TehsilsController::class)->except(['show']);
    Route::resource('ucs', UCsController::class)->except(['show']);
    Route::get('/ucs/{uc}', [UCsController::class, 'show'])->name('ucs.show');
    Route::get('/ucs/{uc}/block-codes', [UCsController::class, 'blockCodes'])->name('ucs.block-codes');
    Route::get('/ucs/{uc}/polling-stations', fn (UC $uc) => PollingStation::where('uc_id', $uc->id)->orderBy('name')->get(['id', 'station_no', 'name', 'gender', 'address']))->name('ucs.polling-stations');
    Route::get('/block-codes/import', [BlockCodesController::class, 'importForm'])->name('block-codes.import.form');
    Route::post('/block-codes/import', [BlockCodesController::class, 'import'])->name('block-codes.import');
    Route::resource('block-codes', BlockCodesController::class)->except(['show']);

    // Polling Stations & Gender-Based Block Code Mapping
    Route::get('/polling-stations/mapping', [PollingStationsController::class, 'mappingMatrix'])->name('polling-stations.mapping');
    Route::post('/polling-stations/mapping', [PollingStationsController::class, 'updateMappingMatrix'])->name('polling-stations.mapping.update');
    Route::post('/polling-stations/sync-voters', [PollingStationsController::class, 'syncVoters'])->name('polling-stations.sync-voters');
    Route::get('/polling-stations/sample-template', [PollingStationsController::class, 'downloadSampleCsv'])->name('polling-stations.sample-template');
    Route::get('/polling-stations/import', [PollingStationsController::class, 'importForm'])->name('polling-stations.import.form');
    Route::post('/polling-stations/import', [PollingStationsController::class, 'import'])->name('polling-stations.import');
    Route::resource('polling-stations', PollingStationsController::class)->except(['show']);

    // Candidate Management & Device Tracking
    Route::resource('candidates', CandidatesController::class);
    Route::get('/candidates/{candidate}/report', [CandidatesController::class, 'report'])->name('candidates.report');
    Route::get('/candidates/{candidate}/devices', [CandidatesController::class, 'devices'])->name('candidates.devices');
    Route::post('/candidates/devices/{device}/toggle', [CandidatesController::class, 'toggleDeviceRevoke'])->name('candidates.devices.toggle');
    Route::delete('/candidates/devices/{device}', [CandidatesController::class, 'destroyDevice'])->name('candidates.devices.destroy');

    // Finance & Sales Vault Security Unlock
    Route::get('/finance/unlock', [FinanceController::class, 'showUnlockForm'])->name('finance.unlock');
    Route::post('/finance/unlock', [FinanceController::class, 'unlock'])->name('finance.unlock.post');
    Route::post('/finance/lock', [FinanceController::class, 'lock'])->name('finance.lock');

    // Password Protected Finance & Sales Routes
    Route::middleware(['finance.auth'])->prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::get('/sales', [FinanceController::class, 'sales'])->name('sales');
        Route::put('/sales/{sale}', [FinanceController::class, 'updateSale'])->name('sales.update');

        Route::get('/parties', [FinanceController::class, 'parties'])->name('parties');
        Route::post('/parties', [FinanceController::class, 'storeParty'])->name('parties.store');
        Route::put('/parties/{party}', [FinanceController::class, 'updateParty'])->name('parties.update');

        Route::get('/partners', [FinanceController::class, 'partners'])->name('partners');
        Route::post('/partners', [FinanceController::class, 'storePartner'])->name('partners.store');
        Route::put('/partners/{partner}', [FinanceController::class, 'updatePartner'])->name('partners.update');
        Route::post('/payouts', [FinanceController::class, 'storePayout'])->name('payouts.store');

        Route::get('/security', [FinanceController::class, 'securitySettings'])->name('security');
        Route::post('/security', [FinanceController::class, 'updateSecurity'])->name('security.update');
    });

    // Voters & Search
    Route::get('/voters/import', [VotersController::class, 'importForm'])->name('voters.import.form');
    Route::get('/voters/import/preview', fn () => redirect()->route('voters.import.form'));
    Route::post('/voters/import/preview', [VotersController::class, 'importPreview'])->name('voters.import.preview');
    Route::post('/voters/import', [VotersController::class, 'import'])->name('voters.import');

    Route::resource('voters', VotersController::class)->except(['show']);
    Route::get('/voters/{voter}', [VotersController::class, 'show'])->name('voters.show');

    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    Route::get('/import/image', [PdfImportController::class, 'importForm'])->name('import.image.form');
    Route::post('/import/image', [PdfImportController::class, 'store'])->name('import.image.store');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/cron/run/{job}', [SettingsController::class, 'runCronJob'])->name('settings.cron.run');
    Route::post('/settings/purge/{type}', [SettingsController::class, 'purge'])->name('settings.purge');
    Route::get('/settings/backup/download', [SettingsController::class, 'downloadBackup'])->name('settings.backup.download');
    Route::get('/settings/backup/file/{filename}', [SettingsController::class, 'downloadBackupFile'])->name('settings.backup.file');
    Route::delete('/settings/backup/file/{filename}', [SettingsController::class, 'deleteBackup'])->name('settings.backup.delete');

    Route::get('/api-docs', [\App\Http\Controllers\ApiDocsController::class, 'index'])->name('api.docs');
});

// Direct /v1/* routes (supports requests with or without /api prefix)
Route::prefix('v1')->group(function () {
    Route::match(['GET', 'POST'], '/auth/login', [\App\Http\Controllers\Api\MobileApiController::class, 'login']);

    Route::middleware([\App\Http\Middleware\CandidateAuthMiddleware::class])->group(function () {
        Route::get('/auth/check-device', [\App\Http\Controllers\Api\MobileApiController::class, 'checkDevice']);
        Route::get('/download', [\App\Http\Controllers\Api\MobileApiController::class, 'downloadUcData']);
        Route::get('/uc/{uc}/download', [\App\Http\Controllers\Api\MobileApiController::class, 'downloadUcData']);
        Route::post('/sync/searches', [\App\Http\Controllers\Api\MobileApiController::class, 'syncSearches']);
        Route::post('/sync/heartbeat', [\App\Http\Controllers\Api\MobileApiController::class, 'heartbeat']);
        Route::match(['GET', 'POST'], '/voters/search', [\App\Http\Controllers\Api\MobileApiController::class, 'searchVoters']);
    });
});
