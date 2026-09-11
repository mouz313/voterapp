<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\CampaignWorker;
use App\Models\GharanaSurvey;
use App\Models\UC;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GharanaSurveyController extends Controller
{
    /**
     * Display Door-to-Door Household Sentiment Intelligence Matrix
     */
    public function index(Request $request)
    {
        $query = GharanaSurvey::query()->with(['candidate.uc.tehsil', 'worker', 'blockCodeModel.uc.tehsil']);

        // Candidate filter
        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        // UC filter (via candidate or block code)
        if ($request->filled('uc_id')) {
            $ucId = $request->uc_id;
            $query->where(function ($q) use ($ucId) {
                $q->whereHas('candidate', fn ($cq) => $cq->where('uc_id', $ucId))
                  ->orWhereHas('blockCodeModel', fn ($bq) => $bq->where('uc_id', $ucId));
            });
        }

        // Block Code filter
        if ($request->filled('block_code')) {
            $query->where('block_code', 'like', '%' . trim($request->block_code) . '%');
        }

        // Sentiment filter
        if ($request->filled('sentiment') && $request->sentiment !== 'all') {
            $query->where('sentiment', $request->sentiment);
        }

        // VIP Request filter
        if ($request->filled('vip')) {
            if ($request->vip == '1') {
                $query->where('is_vip_visit_requested', true);
            } elseif ($request->vip == '0') {
                $query->where('is_vip_visit_requested', false);
            }
        }

        // Keyword search (influencer, phone, notes, gharana #)
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('influencer_name', 'like', "%{$s}%")
                  ->orWhere('influencer_phone', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%")
                  ->orWhere('gharana_no', 'like', "%{$s}%")
                  ->orWhere('block_code', 'like', "%{$s}%");
            });
        }

        // Calculate KPI Metrics across current filter scope
        $statsQuery = clone $query;
        $totalGharanas = (clone $statsQuery)->count();
        $totalVoters = (clone $statsQuery)->sum('voter_count') ?: 0;
        $pakkaCount = (clone $statsQuery)->where('sentiment', 'pakka')->count();
        $pakkaVoters = (clone $statsQuery)->where('sentiment', 'pakka')->sum('voter_count') ?: 0;
        $kachaCount = (clone $statsQuery)->where('sentiment', 'kacha')->count();
        $kachaVoters = (clone $statsQuery)->where('sentiment', 'kacha')->sum('voter_count') ?: 0;
        $mukhalifCount = (clone $statsQuery)->where('sentiment', 'mukhalif')->count();
        $mukhalifVoters = (clone $statsQuery)->where('sentiment', 'mukhalif')->sum('voter_count') ?: 0;
        $vipCount = (clone $statsQuery)->where('is_vip_visit_requested', true)->count();
        $parchiIssuedCount = (clone $statsQuery)->whereNotNull('parchi_issued_at')->count();

        $totalSentimentVoters = $pakkaVoters + $kachaVoters + $mukhalifVoters;
        $pakkaPct = $totalSentimentVoters > 0 ? round(($pakkaVoters / $totalSentimentVoters) * 100, 1) : 0;
        $kachaPct = $totalSentimentVoters > 0 ? round(($kachaVoters / $totalSentimentVoters) * 100, 1) : 0;
        $mukhalifPct = $totalSentimentVoters > 0 ? round(($mukhalifVoters / $totalSentimentVoters) * 100, 1) : 0;

        $surveys = $query->orderByDesc('updated_at')->paginate(25)->withQueryString();

        $candidates = User::where('role', 'candidate')->with('uc.tehsil')->orderBy('name')->get();
        $ucs = UC::with('tehsil')->orderBy('name')->get();

        return view('surveys.index', compact(
            'surveys',
            'candidates',
            'ucs',
            'totalGharanas',
            'totalVoters',
            'pakkaCount',
            'pakkaVoters',
            'kachaCount',
            'kachaVoters',
            'mukhalifCount',
            'mukhalifVoters',
            'vipCount',
            'parchiIssuedCount',
            'totalSentimentVoters',
            'pakkaPct',
            'kachaPct',
            'mukhalifPct'
        ));
    }

    /**
     * VIP Visit Radar — High Priority Candidate Daurah Requests
     */
    public function vipRadar(Request $request)
    {
        $query = GharanaSurvey::query()
            ->with(['candidate.uc.tehsil', 'worker', 'blockCodeModel.uc.tehsil'])
            ->where(function ($q) {
                $q->where('is_vip_visit_requested', true)
                  ->orWhere(function ($sub) {
                      $sub->where('sentiment', 'kacha')->where('voter_count', '>=', 4);
                  });
            });

        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->filled('block_code')) {
            $query->where('block_code', 'like', '%' . trim($request->block_code) . '%');
        }

        if ($request->filled('status')) {
            if ($request->status === 'requested') {
                $query->where('is_vip_visit_requested', true);
            } elseif ($request->status === 'swing_high') {
                $query->where('sentiment', 'kacha')->where('voter_count', '>=', 4);
            }
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('influencer_name', 'like', "%{$s}%")
                  ->orWhere('influencer_phone', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%")
                  ->orWhere('block_code', 'like', "%{$s}%");
            });
        }

        $vipList = $query->orderByDesc('is_vip_visit_requested')
            ->orderByDesc('voter_count')
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        $candidates = User::where('role', 'candidate')->orderBy('name')->get(['id', 'name', 'party_name', 'candidate_code']);

        return view('surveys.vip_radar', compact('vipList', 'candidates'));
    }

    /**
     * Toggle or update VIP visit status
     */
    public function toggleVipStatus(Request $request, GharanaSurvey $survey)
    {
        $newState = !$survey->is_vip_visit_requested;
        $survey->update([
            'is_vip_visit_requested' => $newState,
        ]);

        $msg = $newState 
            ? "VIP Visit Request active kar di gayi hai (Gharana #{$survey->gharana_no})." 
            : "VIP Visit status completed / resolved mark kar diya gaya hai (Gharana #{$survey->gharana_no}).";

        return back()->with('status', $msg);
    }

    /**
     * Printable Daily Route / Daurah Sheet for Candidate & Team
     */
    public function printVipList(Request $request)
    {
        $query = GharanaSurvey::query()
            ->with(['candidate.uc', 'worker', 'blockCodeModel.uc'])
            ->where(function ($q) {
                $q->where('is_vip_visit_requested', true)
                  ->orWhere(function ($sub) {
                      $sub->where('sentiment', 'kacha')->where('voter_count', '>=', 3);
                  });
            });

        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->filled('block_code')) {
            $query->where('block_code', 'like', '%' . trim($request->block_code) . '%');
        }

        $records = $query->orderBy('block_code')->orderByDesc('voter_count')->get();
        $candidate = $request->filled('candidate_id') ? User::find($request->candidate_id) : null;

        return view('surveys.vip_print', compact('records', 'candidate'));
    }

    /**
     * Field Workers Activity & Sync Monitoring
     */
    public function workersIndex(Request $request)
    {
        $query = CampaignWorker::query()
            ->with(['candidate.uc'])
            ->withCount([
                'surveys as total_visited' => fn ($q) => $q->whereNotNull('visited_at'),
                'surveys as pakka_count' => fn ($q) => $q->where('sentiment', 'pakka'),
                'surveys as kacha_count' => fn ($q) => $q->where('sentiment', 'kacha'),
                'surveys as vip_requests_count' => fn ($q) => $q->where('is_vip_visit_requested', true),
            ]);

        if ($request->filled('candidate_id')) {
            $query->where('candidate_id', $request->candidate_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('assigned_block_code', 'like', "%{$s}%");
            });
        }

        $workers = $query->latest('last_sync_at')->paginate(20)->withQueryString();
        $candidates = User::where('role', 'candidate')->orderBy('name')->get(['id', 'name', 'party_name', 'candidate_code']);

        return view('surveys.workers', compact('workers', 'candidates'));
    }
}
