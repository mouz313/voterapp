<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\PollingStation;
use App\Models\UC;
use App\Models\Voter;
use App\Services\SpreadsheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VotersController extends Controller
{
    public function index(Request $request)
    {
        $ucs = UC::orderBy('name')->get();

        $query = Voter::with('uc.tehsil.district', 'blockCode', 'pollingStation')
            ->when($request->query('uc_id'), fn ($q, $id) => $q->where('uc_id', $id))
            ->when($request->query('block_code_id'), fn ($q, $id) => $q->where('block_code_id', $id))
            ->when($request->query('polling_station_id'), fn ($q, $id) => $q->where('polling_station_id', $id))
            ->when($request->query('search'), function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('father_name', 'like', "%{$search}%")
                        ->orWhere('cnic', 'like', '%'.Voter::normalizeCnic($search).'%')
                        ->orWhere('gharana_no', 'like', "%{$search}%");
                });
            });

        $perPage = in_array((int) $request->query('per_page'), [15, 50, 100, 1000], true) ? (int) $request->query('per_page') : 15;
        $voters = $query->orderBy('gharana_no')->orderBy('silsala_no')->paginate($perPage)->withQueryString();

        return view('voters.index', compact('voters', 'ucs', 'perPage'));
    }

    public function create(Request $request)
    {
        $ucs = UC::orderBy('name')->get();
        $ucId = $request->query('uc_id');

        $blockCodes = $ucId ? BlockCode::where('uc_id', $ucId)->orderBy('code')->get() : collect();
        $stations = $ucId ? PollingStation::where('uc_id', $ucId)->orderBy('name')->get() : collect();

        return view('voters.create', compact('ucs', 'ucId', 'blockCodes', 'stations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'block_code_id' => 'required|exists:block_codes,id',
            'polling_station_id' => 'required|exists:polling_stations,id',
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:1|max:120',
            'cnic' => 'required|string|max:15|unique:voters,cnic',
            'phone' => 'nullable|string|max:30|unique:voters,phone',
            'silsala_no' => 'nullable|string|max:50',
            'gharana_no' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        $validated['cnic'] = Voter::normalizeCnic($validated['cnic']);
        if (!empty($validated['phone'])) {
            $validated['phone'] = preg_replace('/[^\d+]/', '', trim($validated['phone']));
        }

        Voter::create($validated);

        return redirect()->route('voters.index')
            ->with('toast', ['type' => 'success', 'message' => 'Voter created.']);
    }

    public function edit(Voter $voter)
    {
        $ucs = UC::orderBy('name')->get();
        $blockCodes = BlockCode::where('uc_id', $voter->uc_id)->orderBy('code')->get();
        $stations = PollingStation::where('uc_id', $voter->uc_id)->orderBy('name')->get();

        return view('voters.edit', compact('voter', 'ucs', 'blockCodes', 'stations'));
    }

    public function update(Request $request, Voter $voter)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'block_code_id' => 'required|exists:block_codes,id',
            'polling_station_id' => 'required|exists:polling_stations,id',
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:1|max:120',
            'cnic' => 'required|string|max:15|unique:voters,cnic,'.$voter->id,
            'phone' => 'nullable|string|max:30|unique:voters,phone,'.$voter->id,
            'silsala_no' => 'nullable|string|max:50',
            'gharana_no' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        $validated['cnic'] = Voter::normalizeCnic($validated['cnic']);
        if (!empty($validated['phone'])) {
            $validated['phone'] = preg_replace('/[^\d+]/', '', trim($validated['phone']));
        }

        $voter->update($validated);

        return redirect()->route('voters.index')
            ->with('toast', ['type' => 'success', 'message' => 'Voter updated.']);
    }

    public function destroy(Voter $voter)
    {
        $voter->delete();

        return redirect()->route('voters.index')
            ->with('toast', ['type' => 'success', 'message' => 'Voter deleted.']);
    }

    public function show(Voter $voter)
    {
        $voter->load('uc.tehsil.district', 'blockCode', 'pollingStation');

        // All voters sharing the same Gharana No within this UC = one household.
        $family = collect();
        if (! empty($voter->gharana_no)) {
            $family = Voter::with('blockCode', 'pollingStation')
                ->where('uc_id', $voter->uc_id)
                ->where('gharana_no', $voter->gharana_no)
                ->orderBy('silsala_no')
                ->get();
        }

        // Fetch candidate assigned to this UC for Voter Parchi branding
        $candidate = \App\Models\User::where('role', 'candidate')
            ->where('uc_id', $voter->uc_id)
            ->latest()
            ->first();

        return view('voters.show', compact('voter', 'family', 'candidate'));
    }

    public function importForm()
    {
        $ucs = UC::with('tehsil.district')
            ->withCount('voters')
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        $pendingUcs = $ucs->where('voters_count', 0)->values();
        $completedUcs = $ucs->where('voters_count', '>', 0)->values();

        return view('voters.import', compact('ucs', 'pendingUcs', 'completedUcs'));
    }

    public function importPreview(Request $request)
    {
        @ini_set('max_execution_time', 300);
        @ini_set('memory_limit', '512M');

        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'block_code_id' => 'nullable|exists:block_codes,id',
            'polling_station_id' => 'nullable|exists:polling_stations,id',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:51200',
        ]);

        $uc = UC::findOrFail($validated['uc_id']);
        $blockCodeId = $validated['block_code_id'] ?? null;
        $pollingStationId = $validated['polling_station_id'] ?? null;

        $stored = $request->file('file')->storeAs('import_tmp', uniqid('imp_', true).'.'.$request->file('file')->getClientOriginalExtension());

        $path = Storage::disk('local')->path($stored);
        $rows = app(SpreadsheetService::class)->readRowsSample($path, 10);
        if (empty($rows)) {
            Storage::delete($stored);

            return redirect()->route('voters.import.form')->with('toast', ['type' => 'error', 'message' => 'File is empty or could not be read.']);
        }

        $header = array_shift($rows);
        if (! is_array($header) || empty($header)) {
            Storage::delete($stored);

            return redirect()->route('voters.import.form')->with('toast', ['type' => 'error', 'message' => 'File has no header row.']);
        }

        $autoMap = $this->mapHeaders($header);
        $sample = $rows;

        $fieldOptions = [
            'cnic' => 'CNIC',
            'phone' => 'Phone / Mobile',
            'name' => 'Name',
            'father_name' => 'Father Name',
            'age' => 'Age',
            'address' => 'Address',
            'age_address' => 'Age + Address (merged عمر و پتہ)',
            'silsala_no' => 'Silsala No',
            'gharana_no' => 'Gharana No',
            'block_code' => 'Block Code',
            'polling_station' => 'Polling Station',
            'ignore' => '— Ignore —',
        ];

        return view('voters.import-preview', compact('uc', 'blockCodeId', 'pollingStationId', 'stored', 'header', 'autoMap', 'sample', 'fieldOptions'));
    }

    public function import(Request $request)
    {
        @ini_set('max_execution_time', 300);
        @ini_set('memory_limit', '512M');

        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'block_code_id' => 'nullable|exists:block_codes,id',
            'polling_station_id' => 'nullable|exists:polling_stations,id',
            'stored' => 'required|string',
            'map' => 'required|array',
        ]);

        $uc = UC::findOrFail($validated['uc_id']);
        $path = Storage::disk('local')->path($validated['stored']);
        if (! is_file($path)) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Uploaded file expired. Please upload again.']);
        }

        // If a block is pre-selected on the form, assign it to every row.
        $preBlock = null;
        if (! empty($validated['block_code_id'])) {
            $preBlock = BlockCode::where('uc_id', $uc->id)->findOrFail($validated['block_code_id']);
        }

        // If a polling station is pre-selected on the form, assign it to every row.
        $preStation = null;
        if (! empty($validated['polling_station_id'])) {
            $preStation = PollingStation::where('uc_id', $uc->id)->findOrFail($validated['polling_station_id']);
        }

        // Build field => colIndex from the user-confirmed mapping.
        $map = [];
        foreach ($validated['map'] as $colIndex => $field) {
            if ($field && $field !== 'ignore') {
                $map[$field] = (int) $colIndex;
            }
        }

        $rows = app(SpreadsheetService::class)->readRows($path);
        $header = array_shift($rows);

        $imported = 0;
        $noVote = 0;
        $skippedRows = [];
        $seenCnics = [];
        $seenPhones = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                $lineNo = $i + 2; // 1-based + header
                $hasData = is_array($row) && ! empty(array_filter($row, fn ($v) => trim((string) ($v ?? '')) !== ''));

                $data = [];
                foreach ($map as $field => $colIndex) {
                    $data[$field] = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : null;
                }

                $age = $data['age'] ?? null;
                $address = $data['address'] ?? null;
                if (! empty($data['age_address'])) {
                    [$a, $addr] = $this->parseAgeAddress($data['age_address']);
                    if (($age === null || $age === '') && $a !== null) {
                        $age = $a;
                    }
                    if (($address === null || $address === '') && $addr !== null) {
                        $address = $addr;
                    }
                }

                $cnic = Voter::normalizeCnic((string) ($data['cnic'] ?? ''));
                $name = trim((string) ($data['name'] ?? ''));
                $nameValid = $name !== '';
                $cnicValid = $cnic && strlen($cnic) === 13;

                // Empty rows OR rows missing required name/CNIC become a "No Vote" placeholder
                // (so the list/serial sequence stays intact) instead of being skipped.
                if (! $hasData || ! $nameValid || ! $cnicValid) {
                    $block = $this->resolveNoVoteBlock($uc, $preBlock);
                    $station = $this->resolveNoVoteStation($uc, $preStation);

                    Voter::create([
                        'uc_id' => $uc->id,
                        'block_code_id' => $block->id,
                        'polling_station_id' => $station->id,
                        'name' => 'No Vote',
                        'father_name' => 'No Vote',
                        'age' => isset($age) && $age !== '' && $age !== null ? (int) preg_replace('/\D/', '', (string) $age) : null,
                        'cnic' => $this->nextNoVoteCnic(),
                        'silsala_no' => $data['silsala_no'] ?? null,
                        'gharana_no' => $data['gharana_no'] ?? null,
                        'address' => $address ?? null,
                    ]);

                    $noVote++;

                    continue;
                }

                // Deduplication on CNIC: Skip if exists in DB or repeated in file
                if (isset($seenCnics[$cnic]) || Voter::where('cnic', $cnic)->exists()) {
                    $skippedRows[] = ['row' => $lineNo, 'reason' => 'Duplicate CNIC: '.Voter::formatCnic($cnic)];

                    continue;
                }

                // Deduplication on Phone: Skip if exists in DB or repeated in file
                $phone = !empty($data['phone']) ? preg_replace('/[^\d+]/', '', trim((string) $data['phone'])) : null;
                if (!empty($phone)) {
                    if (isset($seenPhones[$phone]) || Voter::where('phone', $phone)->exists()) {
                        $skippedRows[] = ['row' => $lineNo, 'reason' => 'Duplicate Phone: '.$phone];

                        continue;
                    }
                }

                $seenCnics[$cnic] = true;
                if (!empty($phone)) {
                    $seenPhones[$phone] = true;
                }

                // Block: use the pre-selected block, else require a valid 9-digit block column.
                if ($preBlock) {
                    $block = $preBlock;
                } else {
                    $blockCodeVal = trim((string) ($data['block_code'] ?? ''));
                    if ($blockCodeVal === '' || ! preg_match('/^\d{9}$/', $blockCodeVal)) {
                        $skippedRows[] = ['row' => $lineNo, 'reason' => 'Invalid/missing block code: '.$blockCodeVal];

                        continue;
                    }
                    $block = BlockCode::firstOrCreate(['uc_id' => $uc->id, 'code' => $blockCodeVal]);
                }

                // Polling station: use pre-selected station, else auto-resolve from block code by voter gender, else column value, else '—'.
                if ($preStation) {
                    $station = $preStation;
                } else {
                    $resolvedStation = $block->getPollingStationForVoter($cnic);
                    if ($resolvedStation) {
                        $station = $resolvedStation;
                    } else {
                        $stationVal = trim((string) ($data['polling_station'] ?? ''));
                        if ($stationVal === '') {
                            $stationVal = '—';
                        }
                        $station = PollingStation::firstOrCreate(['uc_id' => $uc->id, 'name' => $stationVal]);
                    }
                }

                Voter::create([
                    'uc_id' => $uc->id,
                    'block_code_id' => $block->id,
                    'polling_station_id' => $station->id,
                    'name' => $name,
                    'father_name' => $data['father_name'] ?? 'Unknown',
                    'age' => isset($age) && $age !== '' && $age !== null ? (int) preg_replace('/\D/', '', (string) $age) : null,
                    'cnic' => $cnic,
                    'phone' => $phone,
                    'silsala_no' => $data['silsala_no'] ?? null,
                    'gharana_no' => $data['gharana_no'] ?? null,
                    'address' => $address ?? null,
                ]);

                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('toast', ['type' => 'error', 'message' => 'Import failed: '.$e->getMessage()]);
        }

        Storage::delete($validated['stored']);

        return view('voters.import-report', compact('uc', 'imported', 'noVote', 'skippedRows'));
    }

    /**
     * Auto-detect header columns using exact + substring matching (English + Urdu).
     * Returns field => column index. Supports the merged "age + address" column.
     */
    private function mapHeaders(array $header): array
    {
        $normalized = array_map(fn ($h) => strtolower(trim($h)), $header);
        $fields = ['cnic', 'phone', 'name', 'father_name', 'age', 'address', 'age_address', 'silsala_no', 'gharana_no', 'block_code', 'polling_station'];
        $aliases = [
            'cnic' => ['cnic', 'national_id', 'قومی شناختی کارڈ نمبر', 'قومی'],
            'phone' => ['phone', 'mobile', 'cell', 'contact', 'فون', 'موبائل', 'رابطہ', 'فون نمبر', 'موبائل نمبر'],
            'name' => ['name', 'voter_name', 'نام'],
            'father_name' => ['father_name', 'father', 'والد / پتی کا نام', 'والد'],
            'age' => ['age', 'عمر'],
            'address' => ['address', 'residential', 'مکان', 'پتہ'],
            'age_address' => ['عمر و پتہ', 'age_address', 'age and address'],
            'silsala_no' => ['silsala_no', 'silsala', 'serial_no', 'لسٹ', 'سلسلہ نمبر', 'سلسلہ'],
            'gharana_no' => ['gharana_no', 'gharana', 'family_no', 'گھرانہ نمبر', 'گھرانہ'],
            'block_code' => ['block_code', 'block', 'بلاک'],
            'polling_station' => ['polling_station', 'station', 'پولنگ سٹیشن', 'سٹیشن', 'پولنگ'],
        ];

        $map = array_fill_keys($fields, null);

        // Pass 1: exact match.
        foreach ($fields as $field) {
            foreach ($aliases[$field] as $alias) {
                $idx = array_search(strtolower($alias), $normalized);
                if ($idx !== false) {
                    $map[$field] = $idx;
                    break;
                }
            }
        }

        // Pass 2: substring match for fields still unmapped (skip ambiguous 'address' substring).
        foreach ($fields as $field) {
            if ($map[$field] !== null) {
                continue;
            }
            foreach ($aliases[$field] as $alias) {
                $a = strtolower($alias);
                foreach ($normalized as $idx => $h) {
                    if ($h !== '' && $h !== $a && mb_strpos($h, $a) !== false) {
                        $map[$field] = $idx;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Split a merged "NN سال، <address>" cell into [age, address].
     */
    private function parseAgeAddress(string $value): array
    {
        if (preg_match('/(\d+)\s*سال[،,]?\s*(.*)/u', $value, $m)) {
            return [(int) $m[1], trim($m[2]) !== '' ? trim($m[2]) : null];
        }

        return [null, trim($value) !== '' ? trim($value) : null];
    }

    /**
     * Generate a unique placeholder CNIC (13 digits, '9' prefix) for "No Vote" rows.
     */
    private function nextNoVoteCnic(): string
    {
        static $seq = null;
        if ($seq === null) {
            $max = Voter::where('cnic', 'like', '9%')->max('cnic');
            $seq = $max ? ((int) $max + 1) : 9000000000001;
        }

        return (string) $seq++;
    }

    /**
     * Resolve the block for a "No Vote" row: pre-selected block, else a default placeholder block.
     */
    private function resolveNoVoteBlock(UC $uc, ?BlockCode $preBlock): BlockCode
    {
        if ($preBlock) {
            return $preBlock;
        }

        return BlockCode::firstOrCreate(['uc_id' => $uc->id, 'code' => '000000000']);
    }

    /**
     * Resolve the polling station for a "No Vote" row: pre-selected station, else a default '—' station.
     */
    private function resolveNoVoteStation(UC $uc, ?PollingStation $preStation): PollingStation
    {
        if ($preStation) {
            return $preStation;
        }

        return PollingStation::firstOrCreate(['uc_id' => $uc->id, 'name' => '—']);
    }
}
