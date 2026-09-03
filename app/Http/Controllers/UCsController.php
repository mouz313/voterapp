<?php

namespace App\Http\Controllers;

use App\Models\NationalAssembly;
use App\Models\ProvincialAssembly;
use App\Models\Tehsil;
use App\Models\UC;
use Illuminate\Http\Request;

class UCsController extends Controller
{
    public function index(Request $request)
    {
        $tehsils = Tehsil::with('district')->orderBy('name')->get();
        $nationalAssemblies = NationalAssembly::orderBy('code')->get();
        $provincialAssemblies = ProvincialAssembly::orderBy('code')->get();

        $ucs = UC::with(['tehsil.district', 'nationalAssembly', 'provincialAssembly'])
            ->withCount(['voters', 'blockCodes', 'pollingStations'])
            ->when($request->query('tehsil_id'), fn ($q, $id) => $q->where('tehsil_id', $id))
            ->when($request->query('national_assembly_id'), fn ($q, $id) => $q->where('national_assembly_id', $id))
            ->when($request->query('provincial_assembly_id'), fn ($q, $id) => $q->where('provincial_assembly_id', $id))
            ->when($request->query('search'), function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('name_ur', 'like', "%{$s}%")
                        ->orWhere('uc_no', $s);
                });
            })
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('ucs.index', compact('ucs', 'tehsils', 'nationalAssemblies', 'provincialAssemblies'));
    }

    public function create(Request $request)
    {
        $tehsils = Tehsil::with('district')->orderBy('name')->get();
        $nationalAssemblies = NationalAssembly::orderBy('code')->get();
        $provincialAssemblies = ProvincialAssembly::orderBy('code')->get();
        $tehsilId = $request->query('tehsil_id');

        return view('ucs.create', compact('tehsils', 'nationalAssemblies', 'provincialAssemblies', 'tehsilId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tehsil_id' => 'required|exists:tehsils,id',
            'uc_no' => 'nullable|string|max:20',
            'national_assembly_id' => 'nullable|exists:national_assemblies,id',
            'provincial_assembly_id' => 'nullable|exists:provincial_assemblies,id',
            'name' => 'required|string|max:255',
            'name_ur' => 'nullable|string|max:255',
        ]);

        UC::create($validated);

        return redirect()->route('ucs.index')
            ->with('toast', ['type' => 'success', 'message' => 'UC created successfully.']);
    }

    public function show(UC $uc)
    {
        $uc->load(['tehsil.district', 'nationalAssembly', 'provincialAssembly', 'pollingStations']);
        $uc->load(['blockCodes' => function ($q) {
            $q->withCount('voters')->orderBy('code');
        }]);

        $voters = $uc->voters()
            ->with('blockCode', 'pollingStation')
            ->orderBy('gharana_no')
            ->orderBy('silsala_no')
            ->paginate(15);

        return view('ucs.show', compact('uc', 'voters'));
    }

    public function blockCodes(UC $uc)
    {
        return response()->json(
            $uc->blockCodes()->orderBy('code')->get(['id', 'code', 'area_name', 'area_name_ur', 'population'])
        );
    }

    public function edit(UC $uc)
    {
        $tehsils = Tehsil::with('district')->orderBy('name')->get();
        $nationalAssemblies = NationalAssembly::orderBy('code')->get();
        $provincialAssemblies = ProvincialAssembly::orderBy('code')->get();

        return view('ucs.edit', compact('uc', 'tehsils', 'nationalAssemblies', 'provincialAssemblies'));
    }

    public function update(Request $request, UC $uc)
    {
        $validated = $request->validate([
            'tehsil_id' => 'required|exists:tehsils,id',
            'uc_no' => 'nullable|string|max:20',
            'national_assembly_id' => 'nullable|exists:national_assemblies,id',
            'provincial_assembly_id' => 'nullable|exists:provincial_assemblies,id',
            'name' => 'required|string|max:255',
            'name_ur' => 'nullable|string|max:255',
        ]);

        $uc->update($validated);

        return redirect()->route('ucs.index')
            ->with('toast', ['type' => 'success', 'message' => 'UC updated successfully.']);
    }

    public function destroy(UC $uc)
    {
        $uc->delete();

        return redirect()->route('ucs.index')
            ->with('toast', ['type' => 'success', 'message' => 'UC deleted.']);
    }
}
