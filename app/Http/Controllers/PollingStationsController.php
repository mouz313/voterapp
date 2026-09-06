<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\PollingStation;
use App\Models\UC;
use App\Models\Voter;
use App\Services\SpreadsheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class PollingStationsController extends Controller
{
    public function index(Request $request)
    {
        $ucs = UC::with('tehsil.district')
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        $selectedUcId = $request->query('uc_id');
        $selectedGender = $request->query('gender');
        $search = trim((string) $request->query('search', ''));

        $query = PollingStation::with(['uc.tehsil.district', 'maleBlockCodes', 'femaleBlockCodes'])
            ->withCount('voters')
            ->when($selectedUcId, fn ($q, $id) => $q->where('uc_id', $id))
            ->when($selectedGender, fn ($q, $g) => $q->where('gender', $g))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('station_no', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            });

        // Summary counts for filter pill badges
        $countsQuery = PollingStation::query()->when($selectedUcId, fn ($q, $id) => $q->where('uc_id', $id));
        $counts = [
            'total' => (clone $countsQuery)->count(),
            'male' => (clone $countsQuery)->where('gender', 'male')->count(),
            'female' => (clone $countsQuery)->where('gender', 'female')->count(),
            'combined' => (clone $countsQuery)->where('gender', 'combined')->count(),
        ];

        $stations = $query->orderByRaw('CAST(station_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('polling_stations.index', compact('stations', 'ucs', 'selectedUcId', 'selectedGender', 'search', 'counts'));
    }

    public function create(Request $request)
    {
        $ucs = UC::with('tehsil.district')
            ->withCount('pollingStations')
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        $ucId = $request->query('uc_id', $ucs->first()?->id);
        $blockCodes = $ucId ? BlockCode::where('uc_id', $ucId)->orderBy('code')->get() : collect();

        $pendingUcs = $ucs->where('polling_stations_count', 0)->values();
        $completedUcs = $ucs->where('polling_stations_count', '>', 0)->values();

        return view('polling_stations.create', compact('ucs', 'ucId', 'blockCodes', 'pendingUcs', 'completedUcs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'station_no' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,combined',
            'address' => 'nullable|string|max:500',
            'male_booths' => 'nullable|integer|min:0',
            'female_booths' => 'nullable|integer|min:0',
            'total_booths' => 'nullable|integer|min:0',
            'block_code_ids' => 'nullable|array',
            'block_code_ids.*' => 'exists:block_codes,id',
        ]);

        if (empty($validated['total_booths']) && (!empty($validated['male_booths']) || !empty($validated['female_booths']))) {
            $validated['total_booths'] = (int)($validated['male_booths'] ?? 0) + (int)($validated['female_booths'] ?? 0);
        }

        DB::beginTransaction();
        try {
            $station = PollingStation::create($validated);

            // Assign this station to the selected block codes
            if (!empty($validated['block_code_ids'])) {
                $blockCodes = BlockCode::where('uc_id', $validated['uc_id'])
                    ->whereIn('id', $validated['block_code_ids'])
                    ->get();

                foreach ($blockCodes as $bc) {
                    if ($station->gender === 'male') {
                        $bc->male_polling_station_id = $station->id;
                    } elseif ($station->gender === 'female') {
                        $bc->female_polling_station_id = $station->id;
                    } else { // combined
                        $bc->male_polling_station_id = $station->id;
                        $bc->female_polling_station_id = $station->id;
                    }
                    $bc->save();
                }
            }

            DB::commit();

            return redirect()->route('polling-stations.index', ['uc_id' => $station->uc_id])
                ->with('toast', ['type' => 'success', 'message' => "Polling station [{$station->name}] created successfully."]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('toast', ['type' => 'error', 'message' => 'Failed to create polling station: ' . $e->getMessage()]);
        }
    }

    public function edit(PollingStation $pollingStation)
    {
        $ucs = UC::with('tehsil.district')
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        $blockCodes = BlockCode::where('uc_id', $pollingStation->uc_id)->orderBy('code')->get();

        // Determine which block codes currently have this station assigned
        $assignedBlockCodeIds = BlockCode::where('uc_id', $pollingStation->uc_id)
            ->where(function ($q) use ($pollingStation) {
                $q->where('male_polling_station_id', $pollingStation->id)
                  ->orWhere('female_polling_station_id', $pollingStation->id);
            })
            ->pluck('id')
            ->toArray();

        return view('polling_stations.edit', compact('pollingStation', 'ucs', 'blockCodes', 'assignedBlockCodeIds'));
    }

    public function update(Request $request, PollingStation $pollingStation)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'station_no' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,combined',
            'address' => 'nullable|string|max:500',
            'male_booths' => 'nullable|integer|min:0',
            'female_booths' => 'nullable|integer|min:0',
            'total_booths' => 'nullable|integer|min:0',
            'block_code_ids' => 'nullable|array',
            'block_code_ids.*' => 'exists:block_codes,id',
        ]);

        if (empty($validated['total_booths']) && (!empty($validated['male_booths']) || !empty($validated['female_booths']))) {
            $validated['total_booths'] = (int)($validated['male_booths'] ?? 0) + (int)($validated['female_booths'] ?? 0);
        }

        DB::beginTransaction();
        try {
            $pollingStation->update($validated);

            $selectedBlockIds = $validated['block_code_ids'] ?? [];

            // 1. Detach old assignments for this station in this UC
            if ($pollingStation->gender === 'male') {
                BlockCode::where('uc_id', $pollingStation->uc_id)
                    ->where('male_polling_station_id', $pollingStation->id)
                    ->whereNotIn('id', $selectedBlockIds)
                    ->update(['male_polling_station_id' => null]);
            } elseif ($pollingStation->gender === 'female') {
                BlockCode::where('uc_id', $pollingStation->uc_id)
                    ->where('female_polling_station_id', $pollingStation->id)
                    ->whereNotIn('id', $selectedBlockIds)
                    ->update(['female_polling_station_id' => null]);
            } else {
                BlockCode::where('uc_id', $pollingStation->uc_id)
                    ->where('male_polling_station_id', $pollingStation->id)
                    ->whereNotIn('id', $selectedBlockIds)
                    ->update(['male_polling_station_id' => null]);
                BlockCode::where('uc_id', $pollingStation->uc_id)
                    ->where('female_polling_station_id', $pollingStation->id)
                    ->whereNotIn('id', $selectedBlockIds)
                    ->update(['female_polling_station_id' => null]);
            }

            // 2. Attach new assignments
            if (!empty($selectedBlockIds)) {
                $blockCodes = BlockCode::where('uc_id', $pollingStation->uc_id)
                    ->whereIn('id', $selectedBlockIds)
                    ->get();

                foreach ($blockCodes as $bc) {
                    if ($pollingStation->gender === 'male') {
                        $bc->male_polling_station_id = $pollingStation->id;
                    } elseif ($pollingStation->gender === 'female') {
                        $bc->female_polling_station_id = $pollingStation->id;
                    } else { // combined
                        $bc->male_polling_station_id = $pollingStation->id;
                        $bc->female_polling_station_id = $pollingStation->id;
                    }
                    $bc->save();
                }
            }

            DB::commit();

            return redirect()->route('polling-stations.index', ['uc_id' => $pollingStation->uc_id])
                ->with('toast', ['type' => 'success', 'message' => "Polling station [{$pollingStation->name}] updated."]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('toast', ['type' => 'error', 'message' => 'Failed to update polling station: ' . $e->getMessage()]);
        }
    }

    public function destroy(PollingStation $pollingStation)
    {
        $ucId = $pollingStation->uc_id;
        $name = $pollingStation->name;

        DB::beginTransaction();
        try {
            // Nullify assignments on block codes
            BlockCode::where('male_polling_station_id', $pollingStation->id)->update(['male_polling_station_id' => null]);
            BlockCode::where('female_polling_station_id', $pollingStation->id)->update(['female_polling_station_id' => null]);

            $pollingStation->delete();
            DB::commit();

            return redirect()->route('polling-stations.index', ['uc_id' => $ucId])
                ->with('toast', ['type' => 'success', 'message' => "Polling station [{$name}] deleted."]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('toast', ['type' => 'error', 'message' => 'Failed to delete polling station: ' . $e->getMessage()]);
        }
    }

    /**
     * Interactive Polling Scheme Mapping Matrix View for a selected UC.
     */
    public function mappingMatrix(Request $request)
    {
        $ucs = UC::with('tehsil.district')
            ->withCount(['blockCodes', 'pollingStations'])
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        $selectedUcId = $request->query('uc_id', $ucs->first()?->id);
        $selectedUc = $selectedUcId ? UC::with('tehsil.district')->find($selectedUcId) : null;

        $blockCodes = collect();
        $maleStations = collect();
        $femaleStations = collect();
        $allStations = collect();

        if ($selectedUc) {
            $blockCodes = BlockCode::with(['malePollingStation', 'femalePollingStation'])
                ->withCount('voters')
                ->where('uc_id', $selectedUc->id)
                ->orderBy('code')
                ->get();

            $allStations = PollingStation::where('uc_id', $selectedUc->id)
                ->orderByRaw('CAST(station_no AS UNSIGNED) ASC')
                ->orderBy('name')
                ->get();

            $maleStations = $allStations->whereIn('gender', ['male', 'combined'])->values();
            $femaleStations = $allStations->whereIn('gender', ['female', 'combined'])->values();
        }

        $totalBlocks = $blockCodes->count();
        $fullyMapped = $blockCodes->filter->is_fully_mapped->count();
        $pendingBlocks = $totalBlocks - $fullyMapped;

        return view('polling_stations.mapping', compact(
            'ucs',
            'selectedUc',
            'selectedUcId',
            'blockCodes',
            'maleStations',
            'femaleStations',
            'allStations',
            'totalBlocks',
            'fullyMapped',
            'pendingBlocks'
        ));
    }

    /**
     * Save/Update batch mapping matrix of block codes to polling stations.
     */
    public function updateMappingMatrix(Request $request)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'mappings' => 'required|array',
            'mappings.*.male_station_id' => 'nullable|exists:polling_stations,id',
            'mappings.*.female_station_id' => 'nullable|exists:polling_stations,id',
        ]);

        $ucId = $validated['uc_id'];
        $updated = 0;

        DB::beginTransaction();
        try {
            foreach ($validated['mappings'] as $blockCodeId => $stationIds) {
                $block = BlockCode::where('uc_id', $ucId)->find($blockCodeId);
                if ($block) {
                    $block->male_polling_station_id = !empty($stationIds['male_station_id']) ? (int)$stationIds['male_station_id'] : null;
                    $block->female_polling_station_id = !empty($stationIds['female_station_id']) ? (int)$stationIds['female_station_id'] : null;
                    $block->save();
                    $updated++;
                }
            }
            DB::commit();

            return redirect()->route('polling-stations.mapping', ['uc_id' => $ucId])
                ->with('toast', ['type' => 'success', 'message' => "Successfully updated Polling Scheme mapping for {$updated} Census Block(s)."]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('toast', ['type' => 'error', 'message' => 'Failed to save mapping: ' . $e->getMessage()]);
        }
    }

    /**
     * Web trigger: Synchronize all voters' polling station based on CNIC parity and block code.
     */
    public function syncVoters(Request $request)
    {
        $ucId = $request->input('uc_id');
        $params = [];
        if (!empty($ucId)) {
            $params['--uc'] = $ucId;
        }

        try {
            Artisan::call('voters:sync-polling-stations', $params);
            $output = trim(Artisan::output());

            return back()->with('toast', ['type' => 'success', 'message' => $output ?: 'Voters synchronized with polling stations successfully!']);
        } catch (\Throwable $e) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Download sample CSV template for ECP Polling Scheme import.
     */
    public function downloadSampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="polling_stations_sample_template.csv"',
        ];

        $columns = ['station_no', 'name', 'gender', 'block_codes', 'address', 'male_booths', 'female_booths', 'total_booths'];
        $samples = [
            ['1', 'Govt Boys High School No. 1', 'male', '123456701, 123456702', 'Near Main Bazar Road', '4', '0', '4'],
            ['2', 'Govt Girls Community School', 'female', '123456701, 123456702', 'Near Ladies Park', '0', '4', '4'],
            ['3', 'Govt Primary School Model Town', 'combined', '123456703', 'Civil Lines, Street 4', '2', '2', '4'],
        ];

        $callback = function () use ($columns, $samples) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($samples as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath() ?: $file->getPathname();

        $rows = app(SpreadsheetService::class)->readRows($path, $ext);
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
                    $data[$field] = $colIndex !== null && isset($row[$colIndex]) ? trim((string)$row[$colIndex]) : null;
                }

                $name = $data['name'] ?? '';
                if ($name === '') {
                    $skipped++;
                    continue;
                }

                $stationNo = $data['station_no'] ?? null;

                // Normalize Gender
                $genderRaw = strtolower(trim((string)($data['gender'] ?? '')));
                $gender = match (true) {
                    str_contains($genderRaw, 'comb') || str_contains($genderRaw, 'مشترکہ') || str_contains($genderRaw, 'both') || str_contains($genderRaw, 'mix') || $genderRaw === 'c' => 'combined',
                    str_contains($genderRaw, 'female') || str_contains($genderRaw, 'زنانہ') || str_contains($genderRaw, 'زنانه') || str_contains($genderRaw, 'khawateen') || $genderRaw === 'f' => 'female',
                    str_contains($genderRaw, 'male') || str_contains($genderRaw, 'مردانہ') || str_contains($genderRaw, 'مردانه') || str_contains($genderRaw, 'mard') || $genderRaw === 'm' => 'male',
                    default => 'combined',
                };

                $maleBooths = isset($data['male_booths']) && is_numeric($data['male_booths']) ? (int)$data['male_booths'] : null;
                $femaleBooths = isset($data['female_booths']) && is_numeric($data['female_booths']) ? (int)$data['female_booths'] : null;
                $totalBooths = isset($data['total_booths']) && is_numeric($data['total_booths']) ? (int)$data['total_booths'] : ($maleBooths + $femaleBooths ?: null);

                $station = PollingStation::updateOrCreate(
                    [
                        'uc_id' => $ucId,
                        'name' => $name,
                        'gender' => $gender,
                    ],
                    [
                        'station_no' => $stationNo,
                        'address' => $data['address'] ?? null,
                        'male_booths' => $maleBooths,
                        'female_booths' => $femaleBooths,
                        'total_booths' => $totalBooths,
                    ]
                );

                // Handle assigned block codes if provided in column
                $blockCodesRaw = $data['block_codes'] ?? '';
                if (!empty($blockCodesRaw)) {
                    // Split on commas, slashes, or whitespace
                    $codes = preg_split('/[\s,\/]+/', $blockCodesRaw, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($codes as $code) {
                        $cleanCode = preg_replace('/\D/', '', $code);
                        if (!empty($cleanCode)) {
                            $block = BlockCode::firstOrCreate(
                                ['uc_id' => $ucId, 'code' => $cleanCode]
                            );

                            if ($gender === 'male') {
                                $block->male_polling_station_id = $station->id;
                            } elseif ($gender === 'female') {
                                $block->female_polling_station_id = $station->id;
                            } else {
                                $block->male_polling_station_id = $station->id;
                                $block->female_polling_station_id = $station->id;
                            }
                            $block->save();
                        }
                    }
                }

                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('toast', ['type' => 'error', 'message' => 'Import failed: '.$e->getMessage()]);
        }

        return redirect()->route('polling-stations.index', ['uc_id' => $ucId])
            ->with('toast', ['type' => 'success', 'message' => "Imported {$imported} polling stations, skipped {$skipped}."]);
    }

    private function mapHeaders(array $header): array
    {
        $normalized = array_map(fn ($h) => strtolower(preg_replace('/[^a-z0-9_]/', '', trim((string)$h))), $header);
        $fields = ['name', 'station_no', 'gender', 'block_codes', 'address', 'male_booths', 'female_booths', 'total_booths'];
        $map = array_fill_keys($fields, null);

        $aliases = [
            'name' => ['name', 'station_name', 'polling_station', 'polling_station_name', 'building_name', 'venue'],
            'station_no' => ['station_no', 'polling_station_no', 'ps_no', 'sr_no', 'number', 'no'],
            'gender' => ['gender', 'type', 'category', 'male_female', 'sex'],
            'block_codes' => ['block_codes', 'block_code', 'census_block', 'census_code', 'blocks'],
            'address' => ['address', 'location', 'venue_address'],
            'male_booths' => ['male_booths', 'booths_male', 'male_polling_booths'],
            'female_booths' => ['female_booths', 'booths_female', 'female_polling_booths'],
            'total_booths' => ['total_booths', 'booths', 'booths_count', 'polling_booths'],
        ];

        foreach ($fields as $field) {
            $possible = $aliases[$field] ?? [$field];
            foreach ($possible as $alias) {
                $idx = array_search(str_replace('_', '', $alias), array_map(fn($x) => str_replace('_', '', $x), $normalized));
                if ($idx !== false) {
                    $map[$field] = $idx;
                    break;
                }
            }
        }

        return $map;
    }
}
