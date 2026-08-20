<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\PollingStation;
use App\Models\UC;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                      ->orWhere('cnic', 'like', "%" . Voter::normalizeCnic($search) . "%")
                      ->orWhere('gharana_no', 'like', "%{$search}%");
                });
            });

        $voters = $query->orderBy('gharana_no')->orderBy('silsala_no')->paginate(15)->withQueryString();

        return view('voters.index', compact('voters', 'ucs'));
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
            'silsala_no' => 'nullable|string|max:50',
            'gharana_no' => 'nullable|string|max:50',
        ]);

        $validated['cnic'] = Voter::normalizeCnic($validated['cnic']);

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
            'cnic' => 'required|string|max:15|unique:voters,cnic,' . $voter->id,
            'silsala_no' => 'nullable|string|max:50',
            'gharana_no' => 'nullable|string|max:50',
        ]);

        $validated['cnic'] = Voter::normalizeCnic($validated['cnic']);

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

    public function importForm()
    {
        $ucs = UC::orderBy('name')->get();

        return view('voters.import', compact('ucs'));
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $uc = UC::findOrFail($validated['uc_id']);
        $path = $request->file('file')->getRealPath();

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Could not read the file.']);
        }

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        if ($header === false) {
            fclose($handle);
            return back()->with('toast', ['type' => 'error', 'message' => 'File is empty.']);
        }

        $map = $this->mapHeaders($header);
        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                if (count($row) < count($header)) {
                    $skipped++;
                    continue;
                }
                $data = [];
                foreach ($map as $field => $colIndex) {
                    $data[$field] = $colIndex !== null && isset($row[$colIndex]) ? trim($row[$colIndex]) : null;
                }

                $cnic = Voter::normalizeCnic((string) ($data['cnic'] ?? ''));
                if (!$cnic || strlen($cnic) !== 13 || Voter::where('cnic', $cnic)->exists()) {
                    $skipped++;
                    continue;
                }

                $block = BlockCode::firstWhere(['uc_id' => $uc->id, 'code' => $data['block_code'] ?? '']);
                $station = PollingStation::firstWhere(['uc_id' => $uc->id, 'name' => $data['polling_station'] ?? '']);
                if (!$block || !$station) {
                    $skipped++;
                    continue;
                }

                Voter::create([
                    'uc_id' => $uc->id,
                    'block_code_id' => $block->id,
                    'polling_station_id' => $station->id,
                    'name' => $data['name'] ?? 'Unknown',
                    'father_name' => $data['father_name'] ?? 'Unknown',
                    'age' => isset($data['age']) && $data['age'] !== '' ? (int) $data['age'] : null,
                    'cnic' => $cnic,
                    'silsala_no' => $data['silsala_no'],
                    'gharana_no' => $data['gharana_no'],
                ]);

                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return back()->with('toast', ['type' => 'error', 'message' => 'Import failed: ' . $e->getMessage()]);
        }

        fclose($handle);

        return redirect()->route('voters.index', ['uc_id' => $uc->id])
            ->with('toast', ['type' => 'success', 'message' => "Imported {$imported} voters, skipped {$skipped}."]);
    }

    private function mapHeaders(array $header): array
    {
        $normalized = array_map(fn ($h) => strtolower(trim($h)), $header);
        $fields = ['cnic', 'name', 'father_name', 'age', 'silsala_no', 'gharana_no', 'block_code', 'polling_station'];
        $map = array_fill_keys($fields, null);

        foreach ($fields as $field) {
            $index = array_search($field, $normalized);
            if ($index !== false) {
                $map[$field] = $index;
            }
        }

        // Allow "polling_station_name" alias
        if ($map['polling_station'] === null) {
            $alt = array_search('polling_station_name', $normalized);
            if ($alt !== false) {
                $map['polling_station'] = $alt;
            }
        }

        return $map;
    }
}
