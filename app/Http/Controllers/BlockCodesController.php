<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\District;
use App\Models\Tehsil;
use App\Models\UC;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BlockCodesController extends Controller
{
    public function index(Request $request)
    {
        $selectedTehsilId = $request->query('tehsil_id');
        $selectedUcId = $request->query('uc_id');

        $tehsils = Tehsil::with('district')->orderBy('name')->get();

        // If tehsil is selected, show only UCs for that tehsil
        $ucs = UC::with('tehsil.district')
            ->when($selectedTehsilId, fn ($q) => $q->where('tehsil_id', $selectedTehsilId))
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        // If selected UC doesn't belong to selected Tehsil, reset it
        if ($selectedTehsilId && $selectedUcId) {
            $ucBelongsToTehsil = $ucs->contains('id', $selectedUcId);
            if (!$ucBelongsToTehsil) {
                $selectedUcId = null;
            }
        }

        $blockCodes = BlockCode::with(['uc.tehsil.district', 'uc.provincialAssembly', 'malePollingStation', 'femalePollingStation'])
            ->withCount('voters')
            ->when($selectedTehsilId, function ($q, $tid) {
                $q->whereHas('uc', fn ($sub) => $sub->where('tehsil_id', $tid));
            })
            ->when($selectedUcId, fn ($q, $id) => $q->where('uc_id', $id))
            ->when($request->query('search'), function ($q, $s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('area_name', 'like', "%{$s}%")
                  ->orWhere('area_name_ur', 'like', "%{$s}%");
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('block_codes.index', compact('blockCodes', 'ucs', 'tehsils', 'selectedTehsilId', 'selectedUcId'));
    }

    public function create(Request $request)
    {
        $tehsils = Tehsil::with('district')->orderBy('name')->get();
        $ucs = UC::with('tehsil.district')
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        $ucId = $request->query('uc_id');
        $stations = $ucId ? \App\Models\PollingStation::where('uc_id', $ucId)->orderBy('name')->get() : collect();

        return view('block_codes.create', compact('tehsils', 'ucs', 'ucId', 'stations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'code' => 'required|string|max:50',
            'area_name' => 'nullable|string',
            'area_name_ur' => 'nullable|string',
            'population' => 'nullable|integer|min:0',
            'male_polling_station_id' => 'nullable|exists:polling_stations,id',
            'female_polling_station_id' => 'nullable|exists:polling_stations,id',
        ]);

        BlockCode::create($validated);

        return redirect()->route('block-codes.index', ['uc_id' => $validated['uc_id']])
            ->with('toast', ['type' => 'success', 'message' => 'Census Block code created successfully.']);
    }

    public function edit(BlockCode $blockCode)
    {
        $tehsils = Tehsil::with('district')->orderBy('name')->get();
        $ucs = UC::with('tehsil.district')
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        $stations = \App\Models\PollingStation::where('uc_id', $blockCode->uc_id)->orderBy('name')->get();

        return view('block_codes.edit', compact('blockCode', 'tehsils', 'ucs', 'stations'));
    }

    public function update(Request $request, BlockCode $blockCode)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'code' => 'required|string|max:50',
            'area_name' => 'nullable|string',
            'area_name_ur' => 'nullable|string',
            'population' => 'nullable|integer|min:0',
            'male_polling_station_id' => 'nullable|exists:polling_stations,id',
            'female_polling_station_id' => 'nullable|exists:polling_stations,id',
        ]);

        $blockCode->update($validated);

        return redirect()->route('block-codes.index', ['uc_id' => $validated['uc_id']])
            ->with('toast', ['type' => 'success', 'message' => 'Block code updated successfully.']);
    }

    public function destroy(BlockCode $blockCode)
    {
        $blockCode->delete();

        return redirect()->route('block-codes.index')
            ->with('toast', ['type' => 'success', 'message' => 'Block code deleted.']);
    }

    /**
     * Show import form for ECP Delimitation (Excel/CSV).
     */
    public function importForm()
    {
        $tehsils = Tehsil::with(['district', 'ucs.blockCodes'])
            ->orderBy('name')
            ->get()
            ->map(function ($t) {
                $blockCount = $t->ucs->sum(fn ($u) => $u->blockCodes->count());
                $ucCount = $t->ucs->count();
                $t->block_codes_count = $blockCount;
                $t->ucs_count = $ucCount;
                $t->has_data = $blockCount > 0;
                return $t;
            });

        $pendingTehsils = $tehsils->where('has_data', false)->values();
        $completedTehsils = $tehsils->where('has_data', true)->values();

        return view('block_codes.import', compact('tehsils', 'pendingTehsils', 'completedTehsils'));
    }

    /**
     * Handle bulk import of ECP Delimitation list.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls,txt',
            'tehsil_id' => 'nullable|exists:tehsils,id',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (empty($rows)) {
            return back()->with('toast', ['type' => 'error', 'message' => 'File is empty.']);
        }

        $imported = 0;
        $currentUcNo = null;
        $currentUcId = null;
        $defaultTehsilId = $request->tehsil_id;

        foreach ($rows as $index => $row) {
            $col0 = trim((string) ($row[0] ?? ''));
            $col1 = trim((string) ($row[1] ?? ''));
            $col2 = trim((string) ($row[2] ?? ''));
            $col3 = trim((string) ($row[3] ?? ''));

            if (empty($col0) && empty($col1) && empty($col2)) {
                continue;
            }

            if (preg_match('/(union council|census|block code|population|no\.)/i', $col0 . ' ' . $col1 . ' ' . $col2)) {
                continue;
            }

            if (!empty($col0) && is_numeric($col0)) {
                $currentUcNo = $col0;

                if ($defaultTehsilId) {
                    $uc = UC::firstOrCreate(
                        ['tehsil_id' => $defaultTehsilId, 'uc_no' => $currentUcNo],
                        ['name' => "UC {$currentUcNo}"]
                    );
                    $currentUcId = $uc->id;
                }
            }

            $areaName = $col1;
            $blockCodeStr = preg_replace('/\D/', '', $col2);
            $population = is_numeric($col3) ? (int) $col3 : null;

            if (!empty($blockCodeStr) && $currentUcId) {
                BlockCode::updateOrCreate(
                    ['uc_id' => $currentUcId, 'code' => $blockCodeStr],
                    [
                        'area_name' => $areaName,
                        'population' => $population,
                    ]
                );
                $imported++;
            }
        }

        return redirect()->route('block-codes.index')
            ->with('toast', ['type' => 'success', 'message' => "Successfully imported {$imported} Census Block Codes & Areas."]);
    }
}
