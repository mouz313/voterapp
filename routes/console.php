<?php

use App\Models\User;
use App\Services\DatabaseBackupService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Commands & Scheduled Tasks (Cron Jobs)
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Command: php artisan db:backup
 * Generates an automated full database SQL backup and prunes old backups.
 */
Artisan::command('db:backup', function () {
    $this->info('Starting automated database backup...');
    $filePath = DatabaseBackupService::createBackupFile();
    $this->info("Backup successfully generated: {$filePath}");

    $pruned = DatabaseBackupService::pruneOldBackups(14);
    if ($pruned > 0) {
        $this->comment("Pruned {$pruned} backup file(s) older than 14 days.");
    }
})->purpose('Generate a full database SQL backup and prune archives older than 14 days');

/**
 * Command: php artisan candidates:check-expiry
 * Scans candidate accounts and automatically suspends those with passed expiry dates.
 */
Artisan::command('candidates:check-expiry', function () {
    $this->info('Scanning candidate accounts for subscription expiration...');

    $expiredCandidates = User::where('role', 'candidate')
        ->where('status', 'active')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', Carbon::now())
        ->get();

    $count = $expiredCandidates->count();

    foreach ($expiredCandidates as $c) {
        $c->status = 'suspended';
        $c->save();
        $this->warn("Suspended expired candidate: {$c->name} ({$c->email}) - Expired at: {$c->expires_at}");
    }

    $this->info("Candidate expiration check completed. Total suspended: {$count}");
})->purpose('Suspend active candidate accounts whose subscription expires_at date has passed');

/*
|--------------------------------------------------------------------------
| Scheduled Cron Jobs
|--------------------------------------------------------------------------
| To activate these on Linux / cPanel / Ubuntu VPS, add standard cron entry:
| * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
*/

// Check candidate expirations every hour
Schedule::command('candidates:check-expiry')->hourly();

// Daily automated database backup at 02:00 AM
Schedule::command('db:backup')->dailyAt('02:00');

// Clear expired password reset tokens daily
Schedule::command('auth:clear-resets')->daily();
