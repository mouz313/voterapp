<?php

namespace App\Http\Controllers;

use App\Models\NationalAssembly;
use Illuminate\Http\Request;

class NationalAssembliesController extends Controller
{
    public function index(Request $request)
    {
        $provinces = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad'];

        $assemblies = NationalAssembly::withCount(['provincialAssemblies', 'ucs'])
            ->when($request->query('province'), fn ($q, $p) => $q->where('province', $p))
            ->when($request->query('search'), function ($q, $s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%");
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('national_assemblies.index', compact('assemblies', 'provinces'));
    }

    public function create()
    {
        $provinces = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad'];
        return view('national_assemblies.create', compact('provinces'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:national_assemblies,code',
            'name' => 'required|string|max:255',
            'province' => 'required|string|in:Punjab,Sindh,Khyber Pakhtunkhwa,Balochistan,Islamabad',
            'description' => 'nullable|string',
        ]);

        NationalAssembly::create($validated);

        return redirect()->route('national-assemblies.index')
            ->with('toast', ['type' => 'success', 'message' => 'National Assembly constituency created successfully.']);
    }

    public function edit(NationalAssembly $nationalAssembly)
    {
        $provinces = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad'];
        return view('national_assemblies.edit', compact('nationalAssembly', 'provinces'));
    }

    public function update(Request $request, NationalAssembly $nationalAssembly)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:national_assemblies,code,' . $nationalAssembly->id,
            'name' => 'required|string|max:255',
            'province' => 'required|string|in:Punjab,Sindh,Khyber Pakhtunkhwa,Balochistan,Islamabad',
            'description' => 'nullable|string',
        ]);

        $nationalAssembly->update($validated);

        return redirect()->route('national-assemblies.index')
            ->with('toast', ['type' => 'success', 'message' => 'National Assembly constituency updated successfully.']);
    }

    public function destroy(NationalAssembly $nationalAssembly)
    {
        $nationalAssembly->delete();

        return redirect()->route('national-assemblies.index')
            ->with('toast', ['type' => 'success', 'message' => 'National Assembly constituency deleted.']);
    }
}
