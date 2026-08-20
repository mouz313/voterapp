<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PdfExtractorService
{
    /**
     * Extract raw text from a PDF.
     * Digital/text PDFs are handled with pdftotext (-layout). If that yields
     * almost nothing and OCR is enabled, pages are rasterized and run through
     * Tesseract (Urdu). Returns ['text' => string, 'scanned' => bool].
     */
    public function extractText(string $pdfPath): array
    {
        $text = $this->runPdfToText($pdfPath);
        $scanned = mb_strlen(trim($text)) < 50;

        if ($scanned && config('pdfimport.ocr_enabled')) {
            $text = $this->runOcr($pdfPath);
            $scanned = false;
        }

        return ['text' => $text, 'scanned' => $scanned];
    }

    /**
     * Parse layout text into rows of cells. Columns are split on runs of two
     * or more spaces (pdftotext -layout alignment). Good enough for the
     * mapping UI; the user aligns columns explicitly.
     */
    public function parseRows(string $text, int $maxRows = 200): array
    {
        $lines = preg_split('/\r\n|\n|\r/', $text);
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $cells = array_map('trim', preg_split('/\s{2,}/u', $line));
            $cells = array_values(array_filter($cells, fn ($c) => $c !== ''));
            if (count($cells) < 2) {
                continue;
            }
            $rows[] = $cells;
            if (count($rows) >= $maxRows) {
                break;
            }
        }

        return $rows;
    }

    protected function runPdfToText(string $pdfPath): string
    {
        $bin = config('pdfimport.pdftotext_path');
        // -table keeps column gaps (multi-word names stay intact), unlike -layout.
        $cmd = escapeshellarg($bin) . ' -table -enc UTF-8 ' . escapeshellarg($pdfPath) . ' -';
        $output = @shell_exec($cmd);

        if ($output === null) {
            return '';
        }

        // Drop form-feed / null bytes that poppler inserts between pages.
        return str_replace(["\f", "\0"], '', $output);
    }

    protected function runOcr(string $pdfPath): string
    {
        $tmp = Storage::disk('local')->path(config('pdfimport.tmp_dir') . '/ocr_' . uniqid());
        File::ensureDirectoryExists($tmp);

        $script = storage_path('scripts/rasterize.py');
        $py = config('pdfimport.python_path');
        $rasterCmd = escapeshellarg($py) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($pdfPath) . ' ' . escapeshellarg($tmp);
        exec($rasterCmd, $out, $code);

        if ($code !== 0) {
            return '';
        }

        $tesseract = config('pdfimport.tesseract_path');
        $text = '';
        foreach (File::glob($tmp . '/*.png') as $png) {
            $tCmd = escapeshellarg($tesseract) . ' ' . escapeshellarg($png) . ' stdout -l urd';
            $page = @shell_exec($tCmd);
            if ($page !== null) {
                $text .= $page . "\n";
            }
        }

        return $text;
    }
}
