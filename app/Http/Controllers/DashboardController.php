<?php

namespace App\Http\Controllers;

use App\Models\BlockCode;
use App\Models\CandidateDevice;
use App\Models\CampaignWorker;
use App\Models\GharanaSurvey;
use App\Models\District;
use App\Models\NationalAssembly;
use App\Models\PollingStation;
use App\Models\ProvincialAssembly;
use App\Models\SearchLog;
use App\Models\Tehsil;
use App\Models\UC;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. High-Level Electoral KPIs & Campaign Operations
        $stats = [
            'national_assemblies' => NationalAssembly::count(),
            'provincial_assemblies' => ProvincialAssembly::count(),
            'tehsils' => Tehsil::count(),
            'ucs' => UC::count(),
            'block_codes' => BlockCode::count(),
            'polling_stations' => PollingStation::count(),
            'voters' => Voter::count(),
            'candidates' => User::where('role', 'candidate')->count(),
            'active_devices' => CandidateDevice::where('is_revoked', false)->count(),
            'total_searches' => (int) SearchLog::sum('results_count'),
            'total_workers' => CampaignWorker::count(),
            'active_workers' => CampaignWorker::where('is_active', true)->count(),
            'total_surveys' => GharanaSurvey::count(),
            'pakka_votes' => GharanaSurvey::where('sentiment', 'pakka')->count(),
            'turnout_parchis' => GharanaSurvey::whereNotNull('parchi_issued_at')->count(),
        ];

        // 2. Tehsil $\rightarrow$ UC $\rightarrow$ Block Delimitation & Progress Matrix
        $tehsils = Tehsil::with(['district', 'ucs.blockCodes' => function ($q) {
                $q->withCount('voters');
            }])
            ->withCount(['ucs'])
            ->orderBy('name')
            ->get();

        $tehsilMatrix = $tehsils->map(function ($tehsil) {
            $totalBlocks = 0;
            $blocksWithData = 0;
            $totalVoters = 0;

            foreach ($tehsil->ucs as $uc) {
                $totalBlocks += $uc->blockCodes->count();
                foreach ($uc->blockCodes as $bc) {
                    if ($bc->voters_count > 0) {
                        $blocksWithData++;
                        $totalVoters += $bc->voters_count;
                    }
                }
            }

            $coveragePct = $totalBlocks > 0 ? round(($blocksWithData / $totalBlocks) * 100, 1) : 0;

            return [
                'id' => $tehsil->id,
                'name' => $tehsil->name,
                'district' => $tehsil->district->name ?? 'N/A',
                'ucs_count' => $tehsil->ucs_count,
                'total_blocks' => $totalBlocks,
                'blocks_with_data' => $blocksWithData,
                'pending_blocks' => $totalBlocks - $blocksWithData,
                'total_voters' => $totalVoters,
                'coverage_pct' => $coveragePct,
            ];
        });

        // 3. Candidate License & Device Quota Matrix
        $candidateMatrix = User::where('role', 'candidate')
            ->with(['uc.tehsil.district', 'devices'])
            ->withCount(['devices as active_devices_count' => fn ($q) => $q->where('is_revoked', false)])
            ->latest()
            ->take(8)
            ->get()
            ->map(function ($c) {
                $max = $c->max_devices ? $c->max_devices : 'Unlimited';
                $active = $c->active_devices_count;
                $lastDevice = $c->devices->where('is_revoked', false)->sortByDesc('last_active_at')->first();

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'email' => $c->email,
                    'phone' => $c->phone,
                    'uc_name' => $c->uc ? ($c->uc->tehsil?->name . ' - UC ' . ($c->uc->uc_no ?: $c->uc->id) . ' (' . $c->uc->name . ')') : 'Unassigned',
                    'active_devices' => $active,
                    'max_devices' => $max,
                    'status' => $c->status,
                    'expires_at' => $c->expires_at,
                    'last_active' => $lastDevice ? $lastDevice->last_active_at : null,
                ];
            });

        // 4. Live Search Activity & Query Distribution Matrix
        $searchBreakdown = [
            'cnic' => (int) SearchLog::sum('cnic_count'),
            'name' => (int) SearchLog::sum('name_count'),
            'gharana' => (int) SearchLog::sum('gharana_count'),
            'silsala' => (int) SearchLog::sum('silsala_count'),
        ];

        $recentSearches = SearchLog::with(['user', 'uc.tehsil'])
            ->latest('searched_at')
            ->take(8)
            ->get();

        // 5. Polling Station & Voter Assignment Matrix
        $votersWithStation = Voter::whereNotNull('polling_station_id')->count();
        $unassignedVoters = max(0, $stats['voters'] - $votersWithStation);
        $votersWithBlock = Voter::whereNotNull('block_code_id')->count();

        $pollingStats = [
            'total_stations' => PollingStation::count(),
            'assigned_voters' => $votersWithStation,
            'unassigned_voters' => $unassignedVoters,
            'voters_with_block' => $votersWithBlock,
        ];

        return view('dashboard', compact(
            'user',
            'stats',
            'tehsilMatrix',
            'candidateMatrix',
            'searchBreakdown',
            'recentSearches',
            'pollingStats'
        ));
    }

    /**
     * Live AJAX Telemetry for Field Search Breakdown.
     */
    public function searchTelemetry(Request $request)
    {
        $totalSearches = (int) SearchLog::sum('results_count');

        $searchBreakdown = [
            'cnic' => (int) SearchLog::sum('cnic_count'),
            'name' => (int) SearchLog::sum('name_count'),
            'gharana' => (int) SearchLog::sum('gharana_count'),
            'silsala' => (int) SearchLog::sum('silsala_count'),
        ];

        $recentSearches = SearchLog::with(['user', 'uc.tehsil'])
            ->latest('searched_at')
            ->take(8)
            ->get();

        $html = view('dashboard.partials.search_telemetry', compact('searchBreakdown', 'recentSearches'))->render();

        return response()->json([
            'success' => true,
            'total_searches' => number_format($totalSearches),
            'raw_total' => $totalSearches,
            'breakdown' => $searchBreakdown,
            'html' => $html,
            'timestamp' => now()->format('h:i:s A'),
        ]);
    }
}
