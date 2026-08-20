<?php

namespace App\Http\Controllers;

use App\Models\PollingStation;
use App\Models\UC;
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

        return view('polling_stations.create', compact('ucs', 'ucId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
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

        return view('polling_stations.edit', compact('pollingStation', 'ucs'));
    }

    public function update(Request $request, PollingStation $pollingStation)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
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
        $ucs = UC::orderBy('name')->get();

        return view('polling_stations.import', compact('ucs'));
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $ucId = $validated['uc_id'];
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
            fclose($handle);

            return back()->with('toast', ['type' => 'error', 'message' => 'Import failed: ' . $e->getMessage()]);
        }

        fclose($handle);

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
