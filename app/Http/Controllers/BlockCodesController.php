<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\UC;
use Illuminate\Http\Request;

class BlockCodesController extends Controller
{
    public function index(Request $request)
    {
        $ucs = UC::orderBy('name')->get();

        $blockCodes = BlockCode::with('uc.tehsil.district')
            ->when($request->query('uc_id'), fn ($q, $id) => $q->where('uc_id', $id))
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('block_codes.index', compact('blockCodes', 'ucs'));
    }

    public function create(Request $request)
    {
        $ucs = UC::orderBy('name')->get();
        $ucId = $request->query('uc_id');

        return view('block_codes.create', compact('ucs', 'ucId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'code' => 'required|string|max:50',
        ]);

        BlockCode::create($validated);

        return redirect()->route('block-codes.index')
            ->with('toast', ['type' => 'success', 'message' => 'Block code created.']);
    }

    public function edit(BlockCode $blockCode)
    {
        $ucs = UC::orderBy('name')->get();

        return view('block_codes.edit', compact('blockCode', 'ucs'));
    }

    public function update(Request $request, BlockCode $blockCode)
    {
        $validated = $request->validate([
            'uc_id' => 'required|exists:ucs,id',
            'code' => 'required|string|max:50',
        ]);

        $blockCode->update($validated);

        return redirect()->route('block-codes.index')
            ->with('toast', ['type' => 'success', 'message' => 'Block code updated.']);
    }

    public function destroy(BlockCode $blockCode)
    {
        $blockCode->delete();

        return redirect()->route('block-codes.index')
            ->with('toast', ['type' => 'success', 'message' => 'Block code deleted.']);
    }
}
