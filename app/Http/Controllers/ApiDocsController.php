<?php

namespace App\Http\Controllers;

use App\Models\CandidateDevice;
use App\Models\UC;
use App\Models\User;
use Illuminate\Http\Request;

class ApiDocsController extends Controller
{
    public function index(Request $request)
    {
        $baseUrl = url('/');
        $sampleCandidate = User::where('role', 'candidate')->with('uc.tehsil')->first();
        $sampleUc = UC::with(['tehsil.district', 'nationalAssembly', 'provincialAssembly'])->first();

        return view('api_docs.index', compact('baseUrl', 'sampleCandidate', 'sampleUc'));
    }
}
