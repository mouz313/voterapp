<?php

namespace App\Http\Controllers;

use App\Services\PdfExtractorService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class PdfImportController extends Controller
{
    public function importForm()
    {
        return view('import.image');
    }

    /**
     * OCR the uploaded image and stream a CSV/Excel file (no DB write).
     * The exported file uses test.csv's layout so it can be corrected then re-imported.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'format' => 'required|in:csv,xlsx',
        ]);

        $path = $request->file('file')->getRealPath();
        $rows = app(PdfExtractorService::class)->extractVoters($path);

        $headers = PdfExtractorService::HEADERS;
        $filename = 'voters_export_'.date('Ymd_His');

        if ($validated['format'] === 'csv') {
            $csv = $this->toCsv($headers, $rows);

            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            ]);
        }

        $tmp = $this->toXlsx($headers, $rows);

        return response()->download($tmp, $filename.'.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<string, string>>  $rows
     */
    protected function toCsv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['silsala_no'],
                $row['gharana_no'],
                $row['name'],
                $row['father_name'],
                $row['cnic'],
                $row['age'],
                $row['address'],
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<string, string>>  $rows
     */
    protected function toXlsx(array $headers, array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');

        $r = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([
                $row['silsala_no'],
                $row['gharana_no'],
                $row['name'],
                $row['father_name'],
                $row['cnic'],
                $row['age'],
                $row['address'],
            ], null, 'A'.$r);
            $r++;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xls_').'.xlsx';
        (new XlsxWriter($spreadsheet))->save($tmp);

        return $tmp;
    }
}
