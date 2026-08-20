<?php

use App\Http\Controllers\BlockCodesController;
use App\Http\Controllers\DistrictsController;
use App\Http\Controllers\PdfImportController;
use App\Http\Controllers\PollingStationsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TehsilsController;
use App\Http\Controllers\UCsController;
use App\Http\Controllers\VotersController;
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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard', ['user' => Auth::user()]);
    })->name('dashboard');

    Route::resource('districts', DistrictsController::class)->except(['show']);
    Route::resource('tehsils', TehsilsController::class)->except(['show']);
    Route::resource('ucs', UCsController::class)->except(['show']);
    Route::get('/ucs/{uc}', [UCsController::class, 'show'])->name('ucs.show');
    Route::resource('block-codes', BlockCodesController::class)->except(['show']);
    Route::resource('polling-stations', PollingStationsController::class)->except(['show']);
    Route::get('/polling-stations/import', [PollingStationsController::class, 'importForm'])->name('polling-stations.import.form');
    Route::post('/polling-stations/import', [PollingStationsController::class, 'import'])->name('polling-stations.import');

    Route::resource('voters', VotersController::class)->except(['show']);
    Route::get('/voters/import', [VotersController::class, 'importForm'])->name('voters.import.form');
    Route::post('/voters/import', [VotersController::class, 'import'])->name('voters.import');

    Route::get('/import/pdf', [PdfImportController::class, 'index'])->name('import.pdf.index');
    Route::post('/import/pdf', [PdfImportController::class, 'store'])->name('import.pdf.store');
    Route::get('/import/pdf/preview', [PdfImportController::class, 'preview'])->name('import.pdf.preview');
    Route::post('/import/pdf/confirm', [PdfImportController::class, 'confirm'])->name('import.pdf.confirm');

    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
});
