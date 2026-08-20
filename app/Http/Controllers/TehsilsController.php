<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Tehsil;
use Illuminate\Http\Request;

class TehsilsController extends Controller
{
    public function index(Request $request)
    {
        $districts = District::orderBy('name')->get();

        $tehsils = Tehsil::with('district')
            ->when($request->query('district_id'), fn ($q, $id) => $q->where('district_id', $id))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('tehsils.index', compact('tehsils', 'districts'));
    }

    public function create(Request $request)
    {
        $districts = District::orderBy('name')->get();
        $districtId = $request->query('district_id');

        return view('tehsils.create', compact('districts', 'districtId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'name' => 'required|string|max:255',
        ]);

        Tehsil::create($validated);

        return redirect()->route('tehsils.index')
            ->with('toast', ['type' => 'success', 'message' => 'Tehsil created.']);
    }

    public function edit(Tehsil $tehsil)
    {
        $districts = District::orderBy('name')->get();

        return view('tehsils.edit', compact('tehsil', 'districts'));
    }

    public function update(Request $request, Tehsil $tehsil)
    {
        $validated = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'name' => 'required|string|max:255',
        ]);

        $tehsil->update($validated);

        return redirect()->route('tehsils.index')
            ->with('toast', ['type' => 'success', 'message' => 'Tehsil updated.']);
    }

    public function destroy(Tehsil $tehsil)
    {
        $tehsil->delete();

        return redirect()->route('tehsils.index')
            ->with('toast', ['type' => 'success', 'message' => 'Tehsil deleted.']);
    }
}
