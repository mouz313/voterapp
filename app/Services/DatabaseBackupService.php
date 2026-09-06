<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseBackupService
{
    /**
     * Generate a complete .sql database backup file and store it in storage/app/backups.
     *
     * @return string Absolute file path of generated backup.
     */
    public static function createBackupFile(): string
    {
        @ini_set('max_execution_time', 600);
        @ini_set('memory_limit', '512M');

        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $dbName = config('database.connections.mysql.database');
        $timestamp = date('Y_m_d_His');
        $fileName = "{$dbName}_backup_{$timestamp}.sql";
        $filePath = "{$backupDir}/{$fileName}";

        $pdo = DB::connection()->getPdo();
        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $handle = fopen($filePath, 'w');

        fwrite($handle, "-- ========================================================\n");
        fwrite($handle, "-- VoterApp Official Complete Database Backup\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Database: {$dbName}\n");
        fwrite($handle, "-- PHP Version: " . phpversion() . "\n");
        fwrite($handle, "-- ========================================================\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($handle, "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

        foreach ($tables as $table) {
            fwrite($handle, "\n-- --------------------------------------------------------\n");
            fwrite($handle, "-- Table structure for `{$table}`\n");
            fwrite($handle, "-- --------------------------------------------------------\n\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

            $createTableStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC);
            $createSql = $createTableStmt['Create Table'] ?? array_values($createTableStmt)[1];
            fwrite($handle, $createSql . ";\n\n");

            fwrite($handle, "-- Dumping data for table `{$table}`\n");
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $batch = [];
            $batchSize = 250;

            $writeBatch = function ($rows) use ($handle, $table, $pdo) {
                if (empty($rows)) return;
                $columns = array_keys($rows[0]);
                $escapedCols = implode('`, `', $columns);
                fwrite($handle, "INSERT INTO `{$table}` (`{$escapedCols}`) VALUES\n");

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
                    fwrite($handle, $line);
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
            fwrite($handle, "\n");
        }

        fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        fwrite($handle, "-- Backup completed successfully on " . date('Y-m-d H:i:s') . "\n");

        fclose($handle);

        return $filePath;
    }

    /**
     * Delete backups older than specified number of days to conserve disk space.
     */
    public static function pruneOldBackups(int $days = 14): int
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            return 0;
        }

        $cutoff = time() - ($days * 86400);
        $deleted = 0;

        foreach (File::files($backupDir) as $file) {
            if ($file->getMTime() < $cutoff && $file->getExtension() === 'sql') {
                File::delete($file->getPathname());
                $deleted++;
            }
        }

        return $deleted;
    }
}
