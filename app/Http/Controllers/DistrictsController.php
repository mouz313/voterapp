<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\Request;

class DistrictsController extends Controller
{
    public function index()
    {
        $districts = District::withCount('tehsils')
            ->orderBy('name')
            ->paginate(10);

        return view('districts.index', compact('districts'));
    }

    public function create()
    {
        return view('districts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:districts,name',
        ]);

        District::create($validated);

        return redirect()->route('districts.index')
            ->with('toast', ['type' => 'success', 'message' => 'District created.']);
    }

    public function edit(District $district)
    {
        return view('districts.edit', compact('district'));
    }

    public function update(Request $request, District $district)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:districts,name,' . $district->id,
        ]);

        $district->update($validated);

        return redirect()->route('districts.index')
            ->with('toast', ['type' => 'success', 'message' => 'District updated.']);
    }

    public function destroy(District $district)
    {
        $district->delete();

        return redirect()->route('districts.index')
            ->with('toast', ['type' => 'success', 'message' => 'District deleted.']);
    }
}
