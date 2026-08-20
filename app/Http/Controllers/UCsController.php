<?php

namespace App\Http\Controllers;

use App\Models\Tehsil;
use App\Models\UC;
use Illuminate\Http\Request;

class UCsController extends Controller
{
    public function index(Request $request)
    {
        $tehsils = Tehsil::orderBy('name')->get();

        $ucs = UC::with('tehsil.district')
            ->when($request->query('tehsil_id'), fn ($q, $id) => $q->where('tehsil_id', $id))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('ucs.index', compact('ucs', 'tehsils'));
    }

    public function create(Request $request)
    {
        $tehsils = Tehsil::orderBy('name')->get();
        $tehsilId = $request->query('tehsil_id');

        return view('ucs.create', compact('tehsils', 'tehsilId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tehsil_id' => 'required|exists:tehsils,id',
            'name' => 'required|string|max:255',
        ]);

        UC::create($validated);

        return redirect()->route('ucs.index')
            ->with('toast', ['type' => 'success', 'message' => 'UC created.']);
    }

    public function show(UC $uc)
    {
        $uc->load('tehsil.district', 'blockCodes', 'pollingStations');

        $voters = $uc->voters()
            ->with('blockCode', 'pollingStation')
            ->orderBy('gharana_no')
            ->orderBy('silsala_no')
            ->paginate(15);

        return view('ucs.show', compact('uc', 'voters'));
    }

    public function edit(UC $uc)
    {
        $tehsils = Tehsil::orderBy('name')->get();

        return view('ucs.edit', compact('uc', 'tehsils'));
    }

    public function update(Request $request, UC $uc)
    {
        $validated = $request->validate([
            'tehsil_id' => 'required|exists:tehsils,id',
            'name' => 'required|string|max:255',
        ]);

        $uc->update($validated);

        return redirect()->route('ucs.index')
            ->with('toast', ['type' => 'success', 'message' => 'UC updated.']);
    }

    public function destroy(UC $uc)
    {
        $uc->delete();

        return redirect()->route('ucs.index')
            ->with('toast', ['type' => 'success', 'message' => 'UC deleted.']);
    }
}
