<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Extracts voter data from an uploaded image (photo of a voter list) using
 * Tesseract OCR, then produces rows in the same layout as test.csv so the
 * resulting CSV/Excel can be corrected in Excel and re-imported.
 */
class PdfExtractorService
{
    /**
     * Full header row (mirrors test.csv) so the exported file re-imports cleanly.
     * Columns: سلسلہ نمبر، گھرانہ نمبر، نام، والد/پتی کا نام، قومی شناختی کارڈ نمبر، عمر، پتہ
     *
     * @var array<int, string>
     */
    public const HEADERS = [
        'سلسلہ نمبر',
        'گھرانہ نمبر',
        'نام',
        'والد / پتی کا نام',
        'قومی شناختی کارڈ نمبر',
        'عمر',
        'پتہ',
    ];

    /**
     * Run OCR on the image and return structured voter rows.
     *
     * @return array<int, array<string, string>>
     */
    public function extractVoters(string $imagePath): array
    {
        $text = $this->ocr($imagePath);
        $lines = preg_split('/\r\n|\n|\r/u', $text) ?: [];

        $rows = [];
        $n = 0;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $cnic = '';
            if (preg_match('/\b(\d{5}-\d{7}-\d{1})\b/', $line, $m)) {
                $cnic = $m[1];
            } elseif (preg_match('/\b(\d{13})\b/', $line, $m)) {
                $cnic = $m[1];
            }

            $age = '';
            $rest = $line;
            if (preg_match('/(\d+)\s*سال/u', $line, $m)) {
                $age = $m[1];
                $rest = trim(str_replace($m[0], '', $line));
            }

            $n++;
            $rows[] = [
                'silsala_no' => (string) $n,
                'gharana_no' => '',
                'name' => '',
                'father_name' => '',
                'cnic' => $cnic,
                'age' => $age,
                'address' => $rest,
            ];
        }

        return $rows;
    }

    /**
     * Run Tesseract OCR on a (preprocessed) image and return plain text.
     */
    protected function ocr(string $imagePath): string
    {
        $pre = $this->preprocess($imagePath);

        $prefix = config('pdfimport.tessdata_prefix');
        if ($prefix) {
            putenv('TESSDATA_PREFIX='.$prefix);
        }

        $out = tempnam(sys_get_temp_dir(), 'ocr_');
        if ($out === false) {
            return '';
        }

        $cmd = sprintf(
            '"%s" "%s" "%s" -l %s --psm %d 2>NUL',
            config('pdfimport.tesseract_path'),
            $pre,
            $out,
            config('pdfimport.languages'),
            (int) config('pdfimport.psm')
        );

        shell_exec($cmd);

        $txt = @file_get_contents($out.'.txt') ?: '';

        @unlink($out);
        @unlink($out.'.txt');
        if ($pre !== $imagePath) {
            @unlink($pre);
        }

        return $txt;
    }

    /**
     * GD preprocessing: upscale, grayscale, contrast. Returns a temp PNG path.
     */
    protected function preprocess(string $imagePath): string
    {
        if (! extension_loaded('gd')) {
            return $imagePath;
        }

        $info = @getimagesize($imagePath);
        if ($info === false) {
            return $imagePath;
        }

        $src = $this->loadImage($imagePath, $info[2]);
        if ($src === false) {
            return $imagePath;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(3, 4000 / max($w, $h));
        $nw = max(1, (int) ($w * $scale));
        $nh = max(1, (int) ($h * $scale));

        $img = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($img, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagefilter($img, IMG_FILTER_GRAYSCALE);
        imagefilter($img, IMG_FILTER_CONTRAST, -25);
        imagefilter($img, IMG_FILTER_BRIGHTNESS, 10);

        $out = tempnam(sys_get_temp_dir(), 'pre_').'.png';
        imagepng($img, $out);

        imagedestroy($src);
        imagedestroy($img);

        return $out;
    }

    /**
     * @param  int  $type  One of the IMAGETYPE_* constants.
     * @return \GdImage|false
     */
    protected function loadImage(string $path, int $type)
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => false,
        };
    }
}
