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
        @ini_set('max_execution_time', 600);
        @ini_set('memory_limit', '512M');

        $dbName = config('database.connections.mysql.database');
        $timestamp = date('Y_m_d_His');
        $fileName = "{$dbName}_backup_{$timestamp}.sql";
        $backupDir = storage_path('app/backups');

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $localFilePath = "{$backupDir}/{$fileName}";
        $localFile = fopen($localFilePath, 'w');

        $pdo = DB::connection()->getPdo();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        return response()->streamDownload(function () use ($dbName, $tables, $pdo, $localFile) {
            $out = fopen('php://output', 'w');

            $writeBoth = function ($content) use ($out, $localFile) {
                fwrite($out, $content);
                if ($localFile) {
                    fwrite($localFile, $content);
                }
            };

            $writeBoth("-- ========================================================\n");
            $writeBoth("-- VoterApp Official Complete Database Backup\n");
            $writeBoth("-- Generated: " . date('Y-m-d H:i:s') . "\n");
            $writeBoth("-- Database: {$dbName}\n");
            $writeBoth("-- PHP Version: " . phpversion() . "\n");
            $writeBoth("-- ========================================================\n\n");
            $writeBoth("SET FOREIGN_KEY_CHECKS=0;\n");
            $writeBoth("SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n");
            $writeBoth("SET time_zone = \"+00:00\";\n\n");

            foreach ($tables as $table) {
                $writeBoth("\n-- --------------------------------------------------------\n");
                $writeBoth("-- Table structure for `{$table}`\n");
                $writeBoth("-- --------------------------------------------------------\n\n");
                $writeBoth("DROP TABLE IF EXISTS `{$table}`;\n");

                $createTableStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
                $createSql = $createTableStmt['Create Table'] ?? array_values($createTableStmt)[1];
                $writeBoth($createSql . ";\n\n");

                $writeBoth("-- Dumping data for table `{$table}`\n");
                $stmt = $pdo->query("SELECT * FROM `{$table}`");
                $batch = [];
                $batchSize = 250;

                $writeBatch = function ($rows) use ($writeBoth, $table, $pdo) {
                    if (empty($rows)) return;
                    $columns = array_keys($rows[0]);
                    $escapedCols = implode('`, `', $columns);
                    $writeBoth("INSERT INTO `{$table}` (`{$escapedCols}`) VALUES\n");

                    $rowCount = count($rows);
                    $i = 0;
                    foreach ($rows as $row) {
                        $i++;
                        $vals = [];
                        foreach ($row as $val) {
                            if (is_null($val)) {
                                $vals[] = 'NULL';
                            } elseif (is_numeric($val) && !is_string($val)) {
                                $vals[] = $val;
                            } else {
                                $vals[] = $pdo->quote((string) $val);
                            }
                        }
                        $line = "(" . implode(', ', $vals) . ")" . ($i === $rowCount ? ";\n" : ",\n");
                        $writeBoth($line);
                    }
                };

                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    $batch[] = $row;
                    if (count($batch) >= $batchSize) {
                        $writeBatch($batch);
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    $writeBatch($batch);
                }
                $writeBoth("\n");
            }

            $writeBoth("\nSET FOREIGN_KEY_CHECKS=1;\n");
            $writeBoth("-- Backup completed successfully on " . date('Y-m-d H:i:s') . "\n");

            fclose($out);
            if ($localFile) {
                fclose($localFile);
            }
        }, $fileName, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
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
