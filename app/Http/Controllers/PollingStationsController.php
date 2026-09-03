<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\PollingStation;
use App\Models\UC;
use App\Services\SpreadsheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollingStationsController extends Controller
{
    public function index(Request $request)
    {
        $ucs = UC::orderBy('name')->get();

        $stations = PollingStation::with('uc.tehsil.district')
            ->when($request->query('uc_id'), fn ($q, $id) => $q->where('uc_id', $id))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('polling_stations.index', compact('stations', 'ucs'));
    }

    public function create(Request $request)
    {
        $ucs = UC::orderBy('name')->get();
        $ucId = $request->query('uc_id');
        $blockCodes = $ucId ? BlockCode::where('uc_id', $ucId)->orderBy('code')->get() : collect();

        return view('polling_stations.create', compact('ucs', 'ucId', 'blockCodes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'block_code_id' => 'nullable|exists:block_codes,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        PollingStation::create($validated);

        return redirect()->route('polling-stations.index')
            ->with('toast', ['type' => 'success', 'message' => 'Polling station created.']);
    }

    public function edit(PollingStation $pollingStation)
    {
        $ucs = UC::orderBy('name')->get();
        $blockCodes = BlockCode::where('uc_id', $pollingStation->uc_id)->orderBy('code')->get();

        return view('polling_stations.edit', compact('pollingStation', 'ucs', 'blockCodes'));
    }

    public function update(Request $request, PollingStation $pollingStation)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'block_code_id' => 'nullable|exists:block_codes,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:500',
        ]);

        $pollingStation->update($validated);

        return redirect()->route('polling-stations.index')
            ->with('toast', ['type' => 'success', 'message' => 'Polling station updated.']);
    }

    public function destroy(PollingStation $pollingStation)
    {
        $pollingStation->delete();

        return redirect()->route('polling-stations.index')
            ->with('toast', ['type' => 'success', 'message' => 'Polling station deleted.']);
    }

    public function importForm()
    {
        $ucs = UC::with('tehsil.district')
            ->withCount('pollingStations')
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        $pendingUcs = $ucs->where('polling_stations_count', 0)->values();
        $completedUcs = $ucs->where('polling_stations_count', '>', 0)->values();

        return view('polling_stations.import', compact('ucs', 'pendingUcs', 'completedUcs'));
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
        ]);

        $ucId = $validated['uc_id'];
        $path = $request->file('file')->getRealPath();

        $rows = app(SpreadsheetService::class)->readRows($path);
        if (empty($rows)) {
            return back()->with('toast', ['type' => 'error', 'message' => 'File is empty or could not be read.']);
        }

        $header = array_shift($rows);
        if (! is_array($header) || empty($header)) {
            return back()->with('toast', ['type' => 'error', 'message' => 'File has no header row.']);
        }

        $map = $this->mapHeaders($header);
        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                if (! is_array($row) || count($row) < count($header)) {
                    $skipped++;

                    continue;
                }
                $data = [];
                foreach ($map as $field => $colIndex) {
                    $data[$field] = $colIndex !== null && isset($row[$colIndex]) ? trim($row[$colIndex]) : null;
                }

                $name = $data['name'] ?? '';
                if ($name === '' || PollingStation::where(['uc_id' => $ucId, 'name' => $name])->exists()) {
                    $skipped++;

                    continue;
                }

                PollingStation::create([
                    'uc_id' => $ucId,
                    'name' => $name,
                    'address' => $data['address'] ?? null,
                ]);

                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('toast', ['type' => 'error', 'message' => 'Import failed: '.$e->getMessage()]);
        }

        return redirect()->route('polling-stations.index', ['uc_id' => $ucId])
            ->with('toast', ['type' => 'success', 'message' => "Imported {$imported} stations, skipped {$skipped}."]);
    }

    private function mapHeaders(array $header): array
    {
        $normalized = array_map(fn ($h) => strtolower(trim($h)), $header);
        $fields = ['name', 'address'];
        $map = array_fill_keys($fields, null);

        foreach ($fields as $field) {
            $index = array_search($field, $normalized);
            if ($index !== false) {
                $map[$field] = $index;
            }
        }

        return $map;
    }
}
