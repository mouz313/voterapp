<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class SpreadsheetService
{
    /**
     * Read an uploaded CSV/TXT/XLSX/XLS file into an array of rows, where each
     * row is an array of cell strings. The first row is expected to be the header.
     *
     * @return array<int, array<int, string>>
     */
    public function readRows(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'txt') {
            return $this->readCsv($path);
        }

        return $this->readExcel($path);
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function readCsv(string $path): array
    {
        $rows = [];

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return $rows;
        }

        while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function readExcel(string $path): array
    {
        $rows = [];

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $worksheet = $spreadsheet->getActiveSheet();

            foreach ($worksheet->toArray(null, true, false, false) as $row) {
                $rows[] = array_map(fn ($cell) => $cell === null ? '' : (string) $cell, $row);
            }
        } catch (ReaderException $e) {
            // Fall through and return an empty set; the caller reports the error.
        }

        return $rows;
    }

    /**
     * Read only the header row plus a limited number of sample rows. Used by the
     * import preview so we never load an entire (possibly huge) sheet into memory
     * just to show a few sample rows.
     *
     * @return array<int, array<int, string>>
     */
    public function readRowsSample(string $path, int $sample = 10): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'txt') {
            $rows = [];

            $handle = fopen($path, 'r');
            if ($handle === false) {
                return $rows;
            }

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false && count($rows) <= $sample) {
                $rows[] = $row;
            }

            fclose($handle);

            return $rows;
        }

        // Excel: only load the first ($sample + 1) rows via a read filter so a
        // massive sheet doesn't get fully read into memory.
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $reader->setReadFilter(new class($sample) implements IReadFilter
            {
                private int $limit;

                public function __construct(int $limit)
                {
                    $this->limit = $limit;
                }

                public function readCell($columnAddress, $row, $worksheetName = ''): bool
                {
                    return $row <= $this->limit + 1;
                }
            });

            $spreadsheet = $reader->load($path);
            $worksheet = $spreadsheet->getActiveSheet();

            $rows = [];
            foreach ($worksheet->toArray(null, true, false, false) as $row) {
                $rows[] = array_map(fn ($cell) => $cell === null ? '' : (string) $cell, $row);
                if (count($rows) > $sample) {
                    break;
                }
            }

            return $rows;
        } catch (ReaderException $e) {
            return [];
        }
    }
}
