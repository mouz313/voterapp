<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\PollingStation;
use App\Models\UC;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Http\Request;
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

        return view('settings.index', [
            'db_name' => $dbName,
            'db_size' => $dbSize,
            'backups' => $backups,
            'counts' => [
                'voters' => Voter::count(),
                'stations' => PollingStation::count(),
                'blocks' => BlockCode::count(),
                'ucs' => UC::count(),
                'candidates' => User::where('role', 'candidate')->count(),
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
        $allowed = ['voters', 'polling-stations', 'block-codes', 'locations', 'all'];
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
            'all' => 'Everything',
        ];

        return redirect()->route('settings.index')
            ->with('toast', ['type' => 'success', 'message' => 'Removed '.$labels[$type].'.']);
    }
}
