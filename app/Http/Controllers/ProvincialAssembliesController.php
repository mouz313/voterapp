<?php

namespace App\Http\Controllers;

use App\Models\NationalAssembly;
use App\Models\ProvincialAssembly;
use Illuminate\Http\Request;

class ProvincialAssembliesController extends Controller
{
    public function index(Request $request)
    {
        $provinces = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan'];
        $nationalAssemblies = NationalAssembly::orderBy('code')->get();

        $assemblies = ProvincialAssembly::with(['nationalAssembly'])
            ->withCount('ucs')
            ->when($request->query('province'), fn ($q, $p) => $q->where('province', $p))
            ->when($request->query('national_assembly_id'), fn ($q, $naId) => $q->where('national_assembly_id', $naId))
            ->when($request->query('search'), function ($q, $s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%");
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('provincial_assemblies.index', compact('assemblies', 'provinces', 'nationalAssemblies'));
    }

    public function create(Request $request)
    {
        $provinces = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan'];
        $nationalAssemblies = NationalAssembly::orderBy('code')->get();
        $selectedNaId = $request->query('national_assembly_id');

        return view('provincial_assemblies.create', compact('provinces', 'nationalAssemblies', 'selectedNaId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'national_assembly_id' => 'nullable|exists:national_assemblies,id',
            'code' => 'required|string|max:50|unique:provincial_assemblies,code',
            'name' => 'required|string|max:255',
            'province' => 'required|string|in:Punjab,Sindh,Khyber Pakhtunkhwa,Balochistan',
            'description' => 'nullable|string',
        ]);

        ProvincialAssembly::create($validated);

        return redirect()->route('provincial-assemblies.index')
            ->with('toast', ['type' => 'success', 'message' => 'Provincial Assembly constituency created successfully.']);
    }

    public function edit(ProvincialAssembly $provincialAssembly)
    {
        $provinces = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan'];
        $nationalAssemblies = NationalAssembly::orderBy('code')->get();

        return view('provincial_assemblies.edit', compact('provincialAssembly', 'provinces', 'nationalAssemblies'));
    }

    public function update(Request $request, ProvincialAssembly $provincialAssembly)
    {
        $validated = $request->validate([
            'national_assembly_id' => 'nullable|exists:national_assemblies,id',
            'code' => 'required|string|max:50|unique:provincial_assemblies,code,' . $provincialAssembly->id,
            'name' => 'required|string|max:255',
            'province' => 'required|string|in:Punjab,Sindh,Khyber Pakhtunkhwa,Balochistan',
            'description' => 'nullable|string',
        ]);

        $provincialAssembly->update($validated);

        return redirect()->route('provincial-assemblies.index')
            ->with('toast', ['type' => 'success', 'message' => 'Provincial Assembly constituency updated successfully.']);
    }

    public function destroy(ProvincialAssembly $provincialAssembly)
    {
        $provincialAssembly->delete();

        return redirect()->route('provincial-assemblies.index')
            ->with('toast', ['type' => 'success', 'message' => 'Provincial Assembly constituency deleted.']);
    }
}
