<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\PollingStation;
use App\Models\UC;
use App\Models\Voter;
use App\Services\PdfExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfImportController extends Controller
{
    protected PdfExtractorService $extractor;

    protected array $voterFields = [
        'cnic', 'name', 'father_name', 'age', 'silsala_no', 'gharana_no', 'block_code', 'polling_station',
    ];

    protected array $psFields = [
        'name', 'address',
    ];

    public function __construct(PdfExtractorService $extractor)
    {
        $this->extractor = $extractor;
    }

    public function index()
    {
        $ucs = UC::orderBy('name')->get();

        return view('import.pdf', compact('ucs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entity' => 'required|in:voters,polling_stations',
            'uc_id' => 'required|exists:ucs,id',
            'file' => 'required|file|mimes:pdf|max:20480',
        ]);

        $path = $request->file('file')->store(config('pdfimport.tmp_dir'));
        $full = Storage::disk('local')->path($path);

        $result = $this->extractor->extractText($full);
        $rows = $this->extractor->parseRows($result['text']);

        if (empty($rows)) {
            Storage::delete($path);

            return back()->with('toast', [
                'type' => 'error',
                'message' => $result['scanned']
                    ? 'No text found. Enable OCR (Tesseract) for scanned PDFs.'
                    : 'Could not parse any rows from this PDF.',
            ])->withInput();
        }

        $token = Str::random(32);
        $payload = [
            'entity' => $validated['entity'],
            'uc_id' => $validated['uc_id'],
            'rows' => $rows,
            'scanned' => $result['scanned'],
            'path' => $path,
        ];
        Storage::disk('local')->put(config('pdfimport.tmp_dir') . "/rows_{$token}.json", json_encode($payload));

        return redirect()->route('import.pdf.preview', ['token' => $token]);
    }

    public function preview(Request $request)
    {
        $token = $request->query('token');
        $payload = $this->loadPayload($token);
        if (!$payload) {
            return redirect()->route('import.pdf.index')->with('toast', ['type' => 'error', 'message' => 'Session expired. Upload again.']);
        }

        $fields = $payload['entity'] === 'voters' ? $this->voterFields : $this->psFields;
        $header = $payload['rows'][0] ?? [];
        $autoMap = $this->autoMap($header, $fields);

        return view('import.pdf-preview', [
            'token' => $token,
            'entity' => $payload['entity'],
            'ucId' => $payload['uc_id'],
            'rows' => $payload['rows'],
            'header' => $header,
            'fields' => $fields,
            'autoMap' => $autoMap,
            'scanned' => $payload['scanned'],
            'colCount' => count($header) ?: (count($payload['rows'][0] ?? []) ?: 1),
        ]);
    }

    public function confirm(Request $request)
    {
        $token = $request->input('token');
        $payload = $this->loadPayload($token);
        if (!$payload) {
            return redirect()->route('import.pdf.index')->with('toast', ['type' => 'error', 'message' => 'Session expired. Upload again.']);
        }

        $mapping = $request->input('map', []);
        $entity = $payload['entity'];
        $ucId = $payload['uc_id'];
        $rows = $payload['rows'];

        $imported = 0;
        $skipped = [];

        if ($entity === 'voters') {
            foreach ($rows as $idx => $row) {
                if ($idx === 0 && $this->looksLikeHeader($row)) {
                    continue;
                }
                $data = $this->mapRow($row, $mapping);
                $cnic = Voter::normalizeCnic((string) ($data['cnic'] ?? ''));
                if (strlen($cnic) !== 13) {
                    $skipped[] = ['row' => $idx + 1, 'reason' => 'Invalid CNIC'];
                    continue;
                }
                if (Voter::where('cnic', $cnic)->exists()) {
                    $skipped[] = ['row' => $idx + 1, 'reason' => 'Duplicate CNIC'];
                    continue;
                }
                $block = BlockCode::firstWhere(['uc_id' => $ucId, 'code' => $data['block_code'] ?? '']);
                $station = PollingStation::firstWhere(['uc_id' => $ucId, 'name' => $data['polling_station'] ?? '']);
                if (!$block || !$station) {
                    $skipped[] = ['row' => $idx + 1, 'reason' => 'Block/Station not found in UC'];
                    continue;
                }
                Voter::create([
                    'uc_id' => $ucId,
                    'block_code_id' => $block->id,
                    'polling_station_id' => $station->id,
                    'name' => $data['name'] ?? 'Unknown',
                    'father_name' => $data['father_name'] ?? 'Unknown',
                    'age' => isset($data['age']) && $data['age'] !== '' ? (int) $data['age'] : null,
                    'cnic' => $cnic,
                    'silsala_no' => $data['silsala_no'] ?? null,
                    'gharana_no' => $data['gharana_no'] ?? null,
                ]);
                $imported++;
            }
            $redirect = route('voters.index', ['uc_id' => $ucId]);
        } else {
            foreach ($rows as $idx => $row) {
                if ($idx === 0 && $this->looksLikeHeader($row)) {
                    continue;
                }
                $data = $this->mapRow($row, $mapping);
                $name = trim((string) ($data['name'] ?? ''));
                if ($name === '') {
                    $skipped[] = ['row' => $idx + 1, 'reason' => 'Missing name'];
                    continue;
                }
                if (PollingStation::where(['uc_id' => $ucId, 'name' => $name])->exists()) {
                    $skipped[] = ['row' => $idx + 1, 'reason' => 'Duplicate station'];
                    continue;
                }
                PollingStation::create([
                    'uc_id' => $ucId,
                    'name' => $name,
                    'address' => trim((string) ($data['address'] ?? '')),
                ]);
                $imported++;
            }
            $redirect = route('polling-stations.index');
        }

        $this->cleanup($token, $payload['path']);

        return view('import.result', [
            'entity' => $entity,
            'imported' => $imported,
            'skipped' => $skipped,
            'redirect' => $redirect,
        ]);
    }

    protected function loadPayload(?string $token): ?array
    {
        if (!$token) {
            return null;
        }
        $file = config('pdfimport.tmp_dir') . "/rows_{$token}.json";
        if (!Storage::disk('local')->exists($file)) {
            return null;
        }
        $data = json_decode(Storage::disk('local')->get($file), true);

        return is_array($data) ? $data : null;
    }

    protected function cleanup(string $token, ?string $path): void
    {
        Storage::disk('local')->delete(config('pdfimport.tmp_dir') . "/rows_{$token}.json");
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }

    protected function autoMap(array $header, array $fields): array
    {
        $keywords = [
            'voters' => [
                'cnic' => ['cnic', 'قومی', 'نادر'],
                'name' => ['name', 'نام'],
                'father_name' => ['father', 'والد', 'ابو'],
                'age' => ['age', 'عمر'],
                'silsala_no' => ['silsala', 'سلسلہ', 'لسٹ'],
                'gharana_no' => ['gharana', 'گھرانہ'],
                'block_code' => ['block', 'بلاک'],
                'polling_station' => ['station', 'سٹیشن', 'پولنگ'],
            ],
            'polling_stations' => [
                'name' => ['name', 'نام'],
                'address' => ['address', 'پتہ', 'مقام'],
            ],
        ];

        $map = [];
        $rules = $keywords[$fields === $this->voterFields ? 'voters' : 'polling_stations'];

        foreach ($fields as $field) {
            $words = $rules[$field] ?? [];
            foreach ($header as $i => $cell) {
                $cellLower = mb_strtolower($cell);
                foreach ($words as $w) {
                    if (str_contains($cellLower, mb_strtolower($w))) {
                        $map[$field] = $i;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    protected function mapRow(array $row, array $mapping): array
    {
        $data = [];
        foreach ($mapping as $field => $col) {
            $data[$field] = $row[$col] ?? '';
        }

        return $data;
    }

    protected function looksLikeHeader(array $row): bool
    {
        $joined = mb_strtolower(implode(' ', $row));

        return str_contains($joined, 'cnic') || str_contains($joined, 'نام') || str_contains($joined, 'گھرانہ');
    }
}
