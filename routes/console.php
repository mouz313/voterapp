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

/**
 * Command: php artisan search-logs:clear
 * Purges search logs telemetry records from the database.
 */
Artisan::command('search-logs:clear {--days= : Number of days to retain logs (older records pruned)} {--all : Purge all search logs unconditionally}', function () {
    $all = $this->option('all');
    $days = $this->option('days');

    if ($all || (!$days && $days !== '0')) {
        $count = \App\Models\SearchLog::count();
        \App\Models\SearchLog::truncate();
        $this->info("Search logs successfully wiped. Total records cleared: {$count}");
        return;
    }

    $days = (int) $days;
    $cutoff = Carbon::now()->subDays($days);
    $deleted = \App\Models\SearchLog::where('searched_at', '<', $cutoff)->delete();
    $this->info("Search logs older than {$days} days pruned. Total records removed: {$deleted}");
})->purpose('Purge search logs telemetry records from the database');

/**
 * Command: php artisan voters:sync-polling-stations
 * Synchronizes voters' polling_station_id with their block code's male/female polling station based on CNIC gender.
 */
Artisan::command('voters:sync-polling-stations {--uc= : Optional UC ID}', function () {
    $ucId = $this->option('uc');
    $this->info('Starting automated voter polling station synchronization based on gender & block codes...');

    $query = \App\Models\Voter::with('blockCode');
    if ($ucId) {
        $query->where('uc_id', $ucId);
    }

    $total = $query->count();
    $updated = 0;
    $unmapped = 0;

    $this->info("Scanning {$total} voter records...");

    $query->chunkById(500, function ($voters) use (&$updated, &$unmapped) {
        foreach ($voters as $voter) {
            $correctStationId = $voter->resolvePollingStationId();
            if ($correctStationId && (int)$correctStationId !== (int)$voter->polling_station_id) {
                \App\Models\Voter::where('id', $voter->id)->update(['polling_station_id' => $correctStationId]);
                $updated++;
            } elseif (!$correctStationId) {
                $unmapped++;
            }
        }
    });

    $this->info("Synchronization complete! Updated: {$updated} voter(s), Unmapped: {$unmapped}.");
})->purpose('Synchronize voters polling stations according to CNIC gender and block code assignments');

/*
|--------------------------------------------------------------------------
| Scheduled Cron Jobs
|--------------------------------------------------------------------------
| To activate these on Linux / cPanel / Ubuntu VPS, add standard cron entry:
| * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
*/

// Check candidate expirations every hour
Schedule::command('candidates:check-expiry')->hourly();

// Daily automated database backup at 02:00 AM PKT
Schedule::command('db:backup')->dailyAt('02:00');

// Daily automated search logs cleanup at 03:00 AM PKT (prune older than 30 days)
Schedule::command('search-logs:clear --days=30')->dailyAt('03:00');

// Clear expired password reset tokens daily
Schedule::command('auth:clear-resets')->daily();
