<?php

namespace App\Http\Controllers;

use App\Models\UC;
use App\Models\Voter;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $by = $request->query('by'); // 'cnic' | 'gharana'
        $q = trim((string) $request->query('q', ''));
        $ucId = $request->query('uc_id');

        $voter = null;
        $family = collect();
        $error = null;

        $ucs = UC::orderBy('name')->get();

        if ($request->isMethod('get') && ($by === 'cnic' || $by === 'gharana') && $q !== '') {
            if ($by === 'cnic') {
                $voter = Voter::with('uc.tehsil.district', 'blockCode', 'pollingStation')
                    ->byCnic($q)
                    ->first();

                if ($voter) {
                    $family = Voter::with('uc.tehsil.district', 'blockCode', 'pollingStation')
                        ->where('gharana_no', $voter->gharana_no)
                        ->where('uc_id', $voter->uc_id)
                        ->orderBy('silsala_no')
                        ->get();
                } else {
                    $error = 'No voter found for this CNIC.';
                }
            } else {
                if (empty($ucId)) {
                    $error = 'Please select a Union Council to search by Gharana number.';
                } else {
                    $family = Voter::with('uc.tehsil.district', 'blockCode', 'pollingStation')
                        ->byGharana($q)
                        ->where('uc_id', $ucId)
                        ->orderBy('silsala_no')
                        ->get();

                    if ($family->isEmpty()) {
                        $error = 'No family found for this Gharana number in the selected UC.';
                    }
                }
            }
        }

        return view('search.index', compact('by', 'q', 'ucId', 'ucs', 'voter', 'family', 'error'));
    }
}
