<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\CandidateDevice;
use App\Models\CandidateSale;
use App\Models\District;
use App\Models\PollingStation;
use App\Models\SalesParty;
use App\Models\SearchLog;
use App\Models\Tehsil;
use App\Models\UC;
use App\Models\User;
use App\Models\Voter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CandidatesController extends Controller
{
    public function index(Request $request)
    {
        $ucs = UC::with('tehsil.district')->orderBy('name')->get();

        $candidates = User::where('role', 'candidate')
            ->with(['uc.tehsil.district', 'devices', 'sale.party'])
            ->withCount(['devices as active_devices_count' => fn ($q) => $q->where('is_revoked', false)])
            ->when($request->query('uc_id'), fn ($q, $ucId) => $q->where('uc_id', $ucId))
            ->when($request->query('status'), fn ($q, $st) => $q->where('status', $st))
            ->when($request->query('search'), function ($q, $s) {
                $q->where(function ($sub) use ($s) {
                    $sub->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('phone', 'like', "%{$s}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('candidates.index', compact('candidates', 'ucs'));
    }

    public function create()
    {
        $tehsils = Tehsil::with('district')->orderBy('name')->get();
        $ucs = UC::with(['tehsil.district', 'provincialAssembly', 'nationalAssembly'])
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();
        $salesParties = SalesParty::where('is_active', true)->orderBy('name')->get();

        return view('candidates.create', compact('tehsils', 'ucs', 'salesParties'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::defaults()],
            'phone' => 'nullable|string|max:30',
            'uc_id' => 'required|exists:ucs,id',
            'max_devices' => 'nullable|integer|min:1|max:5000',
            'status' => 'required|in:active,suspended',
            'expires_at' => 'nullable|date',
            'party_name' => 'nullable|string|max:255',
            'candidate_code' => 'nullable|string|max:50|unique:users,candidate_code',
            'party_slogan' => 'nullable|string|max:255',
            'is_independent' => 'nullable|boolean',
            'candidate_symbol' => 'nullable|string|max:255',
            'party_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'candidate_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'candidate_symbol_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'leader_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'sales_party_id' => 'nullable|exists:sales_parties,id',
            'sale_amount' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|in:paid,pending,partial',
            'payment_method' => 'nullable|string|max:50',
            'payment_date' => 'nullable|date',
            'sales_notes' => 'nullable|string|max:500',
        ]);

        $validated['role'] = 'candidate';
        $validated['password'] = Hash::make($validated['password']);
        $validated['max_devices'] = !empty($validated['max_devices']) ? (int) $validated['max_devices'] : null;
        $validated['is_independent'] = $request->has('is_independent') || ($request->party_name === 'Independent' || $request->party_name === 'Azad');

        if (empty($validated['candidate_code'])) {
            $validated['candidate_code'] = User::generateUniqueCandidateCode($validated['party_name'] ?? null);
        } else {
            $validated['candidate_code'] = strtoupper(trim($validated['candidate_code']));
        }

        if ($request->hasFile('party_logo')) {
            $file = $request->file('party_logo');
            $name = 'party_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['party_logo'] = 'uploads/branding/' . $name;
        }

        if ($request->hasFile('candidate_image')) {
            $file = $request->file('candidate_image');
            $name = 'candidate_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['candidate_image'] = 'uploads/branding/' . $name;
        }

        if ($request->hasFile('candidate_symbol_image')) {
            $file = $request->file('candidate_symbol_image');
            $name = 'symbol_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['candidate_symbol_image'] = 'uploads/branding/' . $name;
        }

        if ($request->hasFile('leader_image')) {
            $file = $request->file('leader_image');
            $name = 'leader_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['leader_image'] = 'uploads/branding/' . $name;
        }

        $user = User::create($validated);

        // Record Candidate App License Sale
        $partyId = $request->input('sales_party_id');
        $saleAmountInput = $request->input('sale_amount');

        if (!empty($partyId) || $saleAmountInput !== null) {
            $saleAmount = $saleAmountInput !== null ? (float) $saleAmountInput : 20000.00;
            $paymentStatus = $request->input('payment_status', 'paid') ?: 'paid';
            $amountPaid = $request->filled('amount_paid')
                ? (float) $request->input('amount_paid')
                : ($paymentStatus === 'paid' ? $saleAmount : 0.00);

            CandidateSale::create([
                'candidate_id' => $user->id,
                'sales_party_id' => $partyId ?: null,
                'sale_amount' => $saleAmount,
                'amount_paid' => $amountPaid,
                'payment_status' => $paymentStatus,
                'payment_method' => $request->input('payment_method', 'Cash') ?: 'Cash',
                'payment_date' => $request->input('payment_date') ?: now()->toDateString(),
                'notes' => $request->input('sales_notes'),
            ]);
        }

        return redirect()->route('candidates.index')
            ->with('toast', ['type' => 'success', 'message' => 'Candidate account created successfully with party branding & sales record.']);
    }

    public function edit(User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $candidate->load('sale.party');
        $tehsils = Tehsil::with('district')->orderBy('name')->get();
        $ucs = UC::with(['tehsil.district', 'provincialAssembly', 'nationalAssembly'])
            ->orderBy('tehsil_id')
            ->orderByRaw('CAST(uc_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();
        $salesParties = SalesParty::where('is_active', true)->orderBy('name')->get();

        return view('candidates.edit', compact('candidate', 'tehsils', 'ucs', 'salesParties'));
    }

    public function update(Request $request, User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $candidate->id,
            'password' => ['nullable', Password::defaults()],
            'phone' => 'nullable|string|max:30',
            'uc_id' => 'required|exists:ucs,id',
            'max_devices' => 'nullable|integer|min:1|max:5000',
            'status' => 'required|in:active,suspended',
            'expires_at' => 'nullable|date',
            'party_name' => 'nullable|string|max:255',
            'candidate_code' => 'nullable|string|max:50|unique:users,candidate_code,' . $candidate->id,
            'party_slogan' => 'nullable|string|max:255',
            'is_independent' => 'nullable|boolean',
            'candidate_symbol' => 'nullable|string|max:255',
            'party_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'candidate_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'candidate_symbol_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'leader_image' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:4096',
            'sales_party_id' => 'nullable|exists:sales_parties,id',
            'sale_amount' => 'nullable|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|in:paid,pending,partial',
            'payment_method' => 'nullable|string|max:50',
            'payment_date' => 'nullable|date',
            'sales_notes' => 'nullable|string|max:500',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['max_devices'] = !empty($validated['max_devices']) ? (int) $validated['max_devices'] : null;
        $validated['is_independent'] = $request->has('is_independent') || ($request->party_name === 'Independent' || $request->party_name === 'Azad');

        if (!empty($validated['candidate_code'])) {
            $validated['candidate_code'] = strtoupper(trim($validated['candidate_code']));
        }

        if ($request->hasFile('party_logo')) {
            $file = $request->file('party_logo');
            $name = 'party_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['party_logo'] = 'uploads/branding/' . $name;
        }

        if ($request->hasFile('candidate_image')) {
            $file = $request->file('candidate_image');
            $name = 'candidate_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['candidate_image'] = 'uploads/branding/' . $name;
        }

        if ($request->hasFile('candidate_symbol_image')) {
            $file = $request->file('candidate_symbol_image');
            $name = 'symbol_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['candidate_symbol_image'] = 'uploads/branding/' . $name;
        }

        if ($request->hasFile('leader_image')) {
            $file = $request->file('leader_image');
            $name = 'leader_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/branding'), $name);
            $validated['leader_image'] = 'uploads/branding/' . $name;
        }

        $candidate->update($validated);

        // Update or Create Candidate App License Sale
        $partyId = $request->input('sales_party_id');
        $saleAmountInput = $request->input('sale_amount');

        if (!empty($partyId) || $saleAmountInput !== null) {
            $saleAmount = $saleAmountInput !== null ? (float) $saleAmountInput : 20000.00;
            $paymentStatus = $request->input('payment_status', 'paid') ?: 'paid';
            $amountPaid = $request->filled('amount_paid')
                ? (float) $request->input('amount_paid')
                : ($paymentStatus === 'paid' ? $saleAmount : 0.00);

            CandidateSale::updateOrCreate(
                ['candidate_id' => $candidate->id],
                [
                    'sales_party_id' => $partyId ?: null,
                    'sale_amount' => $saleAmount,
                    'amount_paid' => $amountPaid,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $request->input('payment_method', 'Cash') ?: 'Cash',
                    'payment_date' => $request->input('payment_date') ?: now()->toDateString(),
                    'notes' => $request->input('sales_notes'),
                ]
            );
        }

        return redirect()->route('candidates.index')
            ->with('toast', ['type' => 'success', 'message' => 'Candidate branding, account and sales record updated successfully.']);
    }

    public function destroy(User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $candidate->delete();

        return redirect()->route('candidates.index')
            ->with('toast', ['type' => 'success', 'message' => 'Candidate deleted.']);
    }

    /**
     * Show devices assigned to a candidate and allow revoking.
     */
    public function devices(User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $candidate->load(['uc', 'devices' => fn ($q) => $q->orderBy('last_active_at', 'desc')]);

        return view('candidates.devices', compact('candidate'));
    }

    /**
     * Toggle revoke status of a device.
     */
    public function toggleDeviceRevoke(CandidateDevice $device)
    {
        $device->is_revoked = !$device->is_revoked;
        $device->save();

        $msg = $device->is_revoked ? 'Device has been revoked and blocked.' : 'Device has been unblocked.';

        return back()->with('toast', ['type' => 'info', 'message' => $msg]);
    }

    /**
     * Delete a device record.
     */
    public function destroyDevice(CandidateDevice $device)
    {
        $device->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Device record removed.']);
    }

    /**
     * Reset the candidate's password from Admin console.
     */
    public function resetPassword(Request $request, User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        $candidate->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => "Password for {$candidate->name} has been successfully reset.",
        ]);
    }

    /**
     * Compute comprehensive campaign operations, field workers, and canvassing metrics.
     */
    protected function getCandidateCampaignMetrics(User $candidate): array
    {
        $campaignWorkers = $candidate->campaignWorkers()
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        foreach ($campaignWorkers as $w) {
            $w->visited_count = $candidate->gharanaSurveys()->where('visited_by_worker_id', $w->id)->count();
            $w->pakka_count = $candidate->gharanaSurveys()->where('visited_by_worker_id', $w->id)->where('sentiment', 'pakka')->count();
            $w->kacha_count = $candidate->gharanaSurveys()->where('visited_by_worker_id', $w->id)->where('sentiment', 'kacha')->count();
            $w->mukhalif_count = $candidate->gharanaSurveys()->where('visited_by_worker_id', $w->id)->where('sentiment', 'mukhalif')->count();
        }

        $totalWorkers = $campaignWorkers->count();
        $activeWorkers = $campaignWorkers->where('is_active', true)->count();

        $totalSurveys = $candidate->gharanaSurveys()->count();
        $pakkaVotes = (int) $candidate->gharanaSurveys()->where('sentiment', 'pakka')->count();
        $kachaVotes = (int) $candidate->gharanaSurveys()->where('sentiment', 'kacha')->count();
        $mukhalifVotes = (int) $candidate->gharanaSurveys()->where('sentiment', 'mukhalif')->count();

        $vipVisitRequests = $candidate->gharanaSurveys()
            ->where('is_vip_visit_requested', true)
            ->with('worker')
            ->latest()
            ->take(20)
            ->get();

        $totalUcBlockIds = $candidate->uc_id ? BlockCode::where('uc_id', $candidate->uc_id)->pluck('id')->toArray() : [];
        $totalUcDistinctGharanas = !empty($totalUcBlockIds)
            ? Voter::whereIn('block_code_id', $totalUcBlockIds)->whereNotNull('gharana_no')->select('block_code_id', 'gharana_no')->distinct()->count()
            : 0;

        $surveyedDistinctGharanas = $candidate->gharanaSurveys()->select('block_code', 'gharana_no')->distinct()->count();
        $coveragePct = $totalUcDistinctGharanas > 0 ? min(100.0, round(($surveyedDistinctGharanas / $totalUcDistinctGharanas) * 100, 1)) : 0.0;

        $turnoutParchis = (int) $candidate->gharanaSurveys()->whereNotNull('parchi_issued_at')->sum('voter_count');
        if ($turnoutParchis === 0) {
            $turnoutParchis = $candidate->gharanaSurveys()->whereNotNull('parchi_issued_at')->count();
        }

        $campaignStats = [
            'total_workers' => $totalWorkers,
            'active_workers' => $activeWorkers,
            'total_surveys' => $totalSurveys,
            'pakka_votes' => $pakkaVotes,
            'kacha_votes' => $kachaVotes,
            'mukhalif_votes' => $mukhalifVotes,
            'coverage_pct' => $coveragePct,
            'total_uc_gharanas' => $totalUcDistinctGharanas,
            'surveyed_gharanas' => $surveyedDistinctGharanas,
            'turnout_parchis' => $turnoutParchis,
            'vip_requests_count' => $candidate->gharanaSurveys()->where('is_vip_visit_requested', true)->count(),
        ];

        return [
            'campaignWorkers' => $campaignWorkers,
            'campaignStats' => $campaignStats,
            'vipVisitRequests' => $vipVisitRequests,
        ];
    }

    /**
     * Display the specified candidate's profile, data, and comprehensive operational performance matrix.
     */
    public function show(User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $candidate->load([
            'uc.tehsil.district',
            'uc.nationalAssembly',
            'uc.provincialAssembly',
            'devices' => fn ($q) => $q->orderBy('last_active_at', 'desc'),
            'sale.party',
        ]);

        $totalUcVoters = $candidate->uc_id ? Voter::where('uc_id', $candidate->uc_id)->count() : 0;
        $totalPollingStations = $candidate->uc_id ? PollingStation::where('uc_id', $candidate->uc_id)->count() : 0;
        $totalBlockCodes = $candidate->uc_id ? BlockCode::where('uc_id', $candidate->uc_id)->count() : 0;

        $maxDevices = $candidate->max_devices ?: 'Unlimited';
        $registeredDevices = $candidate->devices->count();
        $activeDevices = $candidate->devices->where('is_revoked', false)->count();
        $revokedDevices = $candidate->devices->where('is_revoked', true)->count();

        // Search Telemetry & Performance
        $searchLogs = SearchLog::where('user_id', $candidate->id)->get();
        $totalSearches = (int) $searchLogs->sum('results_count');
        $cnicCount = (int) $searchLogs->sum('cnic_count');
        $nameCount = (int) $searchLogs->sum('name_count');
        $gharanaCount = (int) $searchLogs->sum('gharana_count');
        $silsalaCount = (int) $searchLogs->sum('silsala_count');

        $reachPct = $totalUcVoters > 0 ? min(100, round(($totalSearches / $totalUcVoters) * 100, 1)) : 0;
        $quotaPct = 0;

        // Map searches to devices
        $searchLogsByDevice = $searchLogs->keyBy('device_uid');
        foreach ($candidate->devices as $dev) {
            $log = $searchLogsByDevice->get($dev->device_uid);
            $dev->searches_count = $log ? (int) $log->results_count : 0;
            $dev->last_searched_at = $log ? $log->searched_at : null;
        }

        // Campaign Operations Metrics
        $campaignData = $this->getCandidateCampaignMetrics($candidate);
        $campaignWorkers = $campaignData['campaignWorkers'];
        $campaignStats = $campaignData['campaignStats'];
        $vipVisitRequests = $campaignData['vipVisitRequests'];

        return view('candidates.show', compact(
            'candidate',
            'totalUcVoters',
            'totalPollingStations',
            'totalBlockCodes',
            'maxDevices',
            'registeredDevices',
            'activeDevices',
            'revokedDevices',
            'totalSearches',
            'cnicCount',
            'nameCount',
            'gharanaCount',
            'silsalaCount',
            'reachPct',
            'quotaPct',
            'campaignWorkers',
            'campaignStats',
            'vipVisitRequests'
        ));
    }

    /**
     * Generate / preview candidate performance executive report for print or PDF download.
     */
    public function report(Request $request, User $candidate)
    {
        if ($candidate->role !== 'candidate') {
            abort(404);
        }

        $candidate->load([
            'uc.tehsil.district',
            'uc.nationalAssembly',
            'uc.provincialAssembly',
            'devices' => fn ($q) => $q->orderBy('last_active_at', 'desc'),
            'sale.party',
        ]);

        $totalUcVoters = $candidate->uc_id ? Voter::where('uc_id', $candidate->uc_id)->count() : 0;
        $totalPollingStations = $candidate->uc_id ? PollingStation::where('uc_id', $candidate->uc_id)->count() : 0;
        $totalBlockCodes = $candidate->uc_id ? BlockCode::where('uc_id', $candidate->uc_id)->count() : 0;

        $maxDevices = $candidate->max_devices ?: 'Unlimited';
        $registeredDevices = $candidate->devices->count();
        $activeDevices = $candidate->devices->where('is_revoked', false)->count();
        $revokedDevices = $candidate->devices->where('is_revoked', true)->count();

        $searchLogs = SearchLog::where('user_id', $candidate->id)->get();
        $totalSearches = (int) $searchLogs->sum('results_count');
        $cnicCount = (int) $searchLogs->sum('cnic_count');
        $nameCount = (int) $searchLogs->sum('name_count');
        $gharanaCount = (int) $searchLogs->sum('gharana_count');
        $silsalaCount = (int) $searchLogs->sum('silsala_count');

        $reachPct = $totalUcVoters > 0 ? min(100, round(($totalSearches / $totalUcVoters) * 100, 1)) : 0;
        $quotaPct = 0;

        $searchLogsByDevice = $searchLogs->keyBy('device_uid');
        foreach ($candidate->devices as $dev) {
            $log = $searchLogsByDevice->get($dev->device_uid);
            $dev->searches_count = $log ? (int) $log->results_count : 0;
            $dev->last_searched_at = $log ? $log->searched_at : null;
        }

        // Campaign Operations Metrics
        $campaignData = $this->getCandidateCampaignMetrics($candidate);
        $campaignWorkers = $campaignData['campaignWorkers'];
        $campaignStats = $campaignData['campaignStats'];
        $vipVisitRequests = $campaignData['vipVisitRequests'];

        if ($request->query('download') === 'pdf') {
            $pdf = Pdf::loadView('candidates.pdf', compact(
                'candidate',
                'totalUcVoters',
                'totalPollingStations',
                'totalBlockCodes',
                'maxDevices',
                'registeredDevices',
                'activeDevices',
                'revokedDevices',
                'totalSearches',
                'cnicCount',
                'nameCount',
                'gharanaCount',
                'silsalaCount',
                'reachPct',
                'quotaPct',
                'campaignWorkers',
                'campaignStats',
                'vipVisitRequests'
            ))->setPaper('a4', 'portrait');

            $filename = 'Performance_Report_' . Str::slug($candidate->name) . '_' . date('Ymd_His') . '.pdf';

            return $pdf->download($filename);
        }

        return view('candidates.report', compact(
            'candidate',
            'totalUcVoters',
            'totalPollingStations',
            'totalBlockCodes',
            'maxDevices',
            'registeredDevices',
            'activeDevices',
            'revokedDevices',
            'totalSearches',
            'cnicCount',
            'nameCount',
            'gharanaCount',
            'silsalaCount',
            'reachPct',
            'quotaPct',
            'campaignWorkers',
            'campaignStats',
            'vipVisitRequests'
        ));
    }
}
