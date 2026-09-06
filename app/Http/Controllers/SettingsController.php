<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\PollingStation;
use App\Models\SearchLog;
use App\Models\UC;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function index()
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $files = File::files($backupDir);
        $backups = collect($files)->map(function ($file) {
            return [
                'name' => $file->getFilename(),
                'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB (' . round($file->getSize() / 1024, 1) . ' KB)',
                'timestamp' => $file->getMTime(),
                'date' => date('d M Y, h:i:s A', $file->getMTime()),
            ];
        })->sortByDesc('timestamp')->values();

        $dbName = config('database.connections.mysql.database');
        $dbSize = 0;
        try {
            $sizeRow = DB::selectOne(
                "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.TABLES WHERE table_schema = ?",
                [$dbName]
            );
            $dbSize = $sizeRow->size_mb ?? 0;
        } catch (\Throwable $e) {
            $dbSize = 0;
        }

        $cronJobs = [
            [
                'id' => 'candidates:check-expiry',
                'name' => 'Candidate Expiration Scanner',
                'schedule' => 'Hourly (0 * * * *)',
                'command' => 'php artisan candidates:check-expiry',
                'description' => 'Scans active candidate accounts and automatically suspends accounts whose expires_at date has passed.',
                'icon' => 'bi-person-x',
                'badge_color' => 'warning',
            ],
            [
                'id' => 'db:backup',
                'name' => 'Daily Automated Database Backup',
                'schedule' => 'Daily at 02:00 AM PKT (0 2 * * *)',
                'command' => 'php artisan db:backup',
                'description' => 'Generates full SQL database dump into storage/app/backups and prunes archives older than 14 days.',
                'icon' => 'bi-database-down',
                'badge_color' => 'success',
            ],
            [
                'id' => 'search-logs:clear',
                'name' => 'Search Telemetry Logs Pruner',
                'schedule' => 'Daily at 03:00 AM PKT (0 3 * * *)',
                'command' => 'php artisan search-logs:clear --days=30',
                'description' => 'Prunes field search telemetry logs older than 30 days to optimize database storage.',
                'icon' => 'bi-trash3',
                'badge_color' => 'info',
            ],
            [
                'id' => 'auth:clear-resets',
                'name' => 'Password Reset Tokens Cleaner',
                'schedule' => 'Daily at 00:00 AM PKT (0 0 * * *)',
                'command' => 'php artisan auth:clear-resets',
                'description' => 'Clears expired password reset tokens from the password_reset_tokens table.',
                'icon' => 'bi-key',
                'badge_color' => 'secondary',
            ],
        ];

        return view('settings.index', [
            'db_name' => $dbName,
            'db_size' => $dbSize,
            'backups' => $backups,
            'timezone' => config('app.timezone'),
            'server_time' => now()->format('d M Y, h:i:s A T'),
            'cron_jobs' => $cronJobs,
            'counts' => [
                'voters' => Voter::count(),
                'stations' => PollingStation::count(),
                'blocks' => BlockCode::count(),
                'ucs' => UC::count(),
                'candidates' => User::where('role', 'candidate')->count(),
                'search_logs' => SearchLog::count(),
                'total_searches' => (int) SearchLog::sum('results_count'),
            ],
        ]);
    }

    /**
     * Generate, store, and stream full database SQL backup for download.
     */
    public function downloadBackup()
    {
        $filePath = \App\Services\DatabaseBackupService::createBackupFile();

        return response()->download($filePath, basename($filePath), [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * Download an existing backup from storage.
     */
    public function downloadBackupFile(string $filename)
    {
        $filename = basename($filename);
        $filePath = storage_path("app/backups/{$filename}");
        if (!File::exists($filePath)) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Backup file not found.']);
        }

        return response()->download($filePath);
    }

    /**
     * Delete an existing backup file.
     */
    public function deleteBackup(string $filename)
    {
        $filename = basename($filename);
        $filePath = storage_path("app/backups/{$filename}");
        if (File::exists($filePath)) {
            File::delete($filePath);
            return back()->with('toast', ['type' => 'success', 'message' => "Backup {$filename} deleted."]);
        }

        return back()->with('toast', ['type' => 'error', 'message' => 'File not found.']);
    }

    public function purge(Request $request, string $type)
    {
        $allowed = ['voters', 'polling-stations', 'block-codes', 'locations', 'search-logs', 'all'];
        if (! in_array($type, $allowed, true)) {
            abort(404);
        }

        if (! $request->boolean('confirm')) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Confirmation required.']);
        }

        // Locations/out implies wiping dependent child records (FK safety).
        $wipeLocations = $type === 'locations' || $type === 'all';

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        if ($type === 'voters' || $wipeLocations) {
            Voter::query()->delete();
        }
        if ($type === 'polling-stations' || $wipeLocations) {
            PollingStation::query()->delete();
        }
        if ($type === 'block-codes' || $wipeLocations) {
            BlockCode::query()->delete();
        }
        if ($type === 'search-logs' || $type === 'all') {
            SearchLog::query()->truncate();
        }
        if ($wipeLocations) {
            UC::query()->delete();
            DB::table('tehsils')->delete();
            DB::table('districts')->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $labels = [
            'voters' => 'All voters',
            'polling-stations' => 'All polling stations',
            'block-codes' => 'All block codes',
            'locations' => 'All locations (UCs/Tehsils/Districts) and dependent data',
            'search-logs' => 'All field search telemetry records',
            'all' => 'Everything',
        ];

        return redirect()->route('settings.index')
            ->with('toast', ['type' => 'success', 'message' => 'Removed '.$labels[$type].'.']);
    }

    /**
     * Web-based manual trigger for scheduled CronJobs.
     */
    public function runCronJob(Request $request, string $job)
    {
        $validJobs = [
            'candidates:check-expiry' => 'Candidate Expiration Scanner',
            'db:backup' => 'Automated Database Backup',
            'search-logs:clear' => 'Search Telemetry Logs Pruner',
            'auth:clear-resets' => 'Password Reset Tokens Cleaner',
        ];

        if (!array_key_exists($job, $validJobs)) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Invalid or unauthorized CronJob command.']);
        }

        $parameters = [];
        if ($job === 'search-logs:clear') {
            if ($request->has('all') && $request->boolean('all')) {
                $parameters['--all'] = true;
            } elseif ($request->filled('days')) {
                $parameters['--days'] = (int) $request->input('days');
            } else {
                $parameters['--all'] = true; // default manual action clears all
            }
        }

        try {
            Artisan::call($job, $parameters);
            $output = trim(Artisan::output());

            return back()->with('toast', [
                'type' => 'success',
                'message' => "CronJob [{$validJobs[$job]}] ran successfully: " . ($output ?: 'Completed successfully with status 0.'),
            ]);
        } catch (\Throwable $e) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => "CronJob execution failed: " . $e->getMessage(),
            ]);
        }
    }
}
