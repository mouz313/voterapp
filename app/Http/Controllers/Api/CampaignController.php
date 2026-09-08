<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlockCode;
use App\Models\CampaignWorker;
use App\Models\CandidateDevice;
use App\Models\GharanaSurvey;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    /**
     * 1. Public Campaign Branding endpoint
     * Returns white-label party assets to morph mobile login screen.
     */
    public function campaignBranding(Request $request): JsonResponse
    {
        $request->validate([
            'candidate_code' => 'required|string',
        ]);

        $candidate = User::where('candidate_code', strtoupper(trim($request->candidate_code)))
            ->where('role', 'candidate')
            ->first();

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate code invalid or not found.',
            ], 404);
        }

        $partyColors = $this->resolvePartyColors($candidate->party_name);

        return response()->json([
            'success' => true,
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'candidate_code' => $candidate->candidate_code,
                'party_name' => $candidate->party_name ?? 'Independent',
                'party_slogan' => $candidate->party_slogan ?? 'Vote for a Better Future',
                'party_logo_url' => $candidate->party_logo_url,
                'candidate_image_url' => $candidate->candidate_image_url,
                'leader_image_url' => $candidate->leader_image_url,
                'candidate_symbol' => $candidate->candidate_symbol,
                'candidate_symbol_image_url' => $candidate->candidate_symbol_image_url,
                'uc_name' => $candidate->uc ? $candidate->uc->name : null,
                'colors' => $partyColors,
            ],
        ]);
    }

    /**
     * 2. Field Worker PIN Login
     * Takes candidate_code + 4-digit PIN + device_uid.
     */
    public function staffLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_code' => 'required|string',
            'pin' => 'required|string|min:4|max:10',
            'device_uid' => 'nullable|string|max:100',
        ]);

        $candidate = User::where('candidate_code', strtoupper(trim($validated['candidate_code'])))
            ->where('role', 'candidate')
            ->first();

        if (!$candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Candidate Code.',
            ], 404);
        }

        if ($candidate->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Candidate license is currently inactive.',
            ], 403);
        }

        $workers = CampaignWorker::where('candidate_id', $candidate->id)
            ->where('is_active', true)
            ->get();

        $worker = $workers->first(fn ($w) => Hash::check(trim($validated['pin']), $w->pin));

        if (!$worker) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid 4-digit PIN for this candidate campaign.',
            ], 401);
        }

        if (!empty($validated['device_uid'])) {
            $worker->update(['device_uid' => $validated['device_uid']]);
        }

        $token = $worker->generateApiToken();

        return response()->json([
            'success' => true,
            'message' => "Welcome {$worker->name}! Assigned to Block {$worker->assigned_block_code}",
            'token' => $token,
            'api_token' => $token,
            'worker' => [
                'id' => $worker->id,
                'name' => $worker->name,
                'phone' => $worker->phone,
                'assigned_block_code' => $worker->assigned_block_code,
                'last_sync_at' => $worker->last_sync_at,
            ],
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'candidate_code' => $candidate->candidate_code,
                'party_name' => $candidate->party_name,
                'uc_id' => $candidate->uc_id,
            ],
        ]);
    }

    /**
     * 3. Download Assigned Block Code Data for Offline Door-to-Door Walk
     */
    public function staffBlockData(Request $request): JsonResponse
    {
        $worker = $this->getAuthenticatedWorker($request);
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated staff member.'], 401);
        }

        $blockCode = $worker->assigned_block_code;

        // Find BlockCode model by code string
        $block = BlockCode::where('code', $blockCode)->first();
        $blockCodeId = $block ? $block->id : null;

        // Fetch voters in this block code grouped by gharana_no
        $voters = Voter::when($blockCodeId, fn ($q) => $q->where('block_code_id', $blockCodeId))
            ->select('id', 'name', 'father_name', 'cnic', 'gharana_no', 'silsala_no', 'age', 'address')
            ->orderBy('gharana_no')
            ->orderBy('silsala_no')
            ->get()
            ->groupBy('gharana_no');

        // Fetch existing survey outcomes for this block under this candidate
        $surveys = GharanaSurvey::where('candidate_id', $worker->candidate_id)
            ->where('block_code', $blockCode)
            ->get()
            ->keyBy('gharana_no');

        $gharanaList = [];
        foreach ($voters as $gharanaNo => $familyVoters) {
            $existing = $surveys->get($gharanaNo);
            $gharanaList[] = [
                'gharana_no' => (int) $gharanaNo,
                'voter_count' => $familyVoters->count(),
                'head_name' => $familyVoters->first()->name ?? 'Family',
                'voters' => $familyVoters->values(),
                'sentiment' => $existing ? $existing->sentiment : 'unassigned',
                'notes' => $existing ? $existing->notes : null,
                'influencer_name' => $existing ? $existing->influencer_name : null,
                'is_visited' => $existing && $existing->visited_at !== null,
                'visited_at' => $existing ? $existing->visited_at : null,
                'visited_by' => $existing && $existing->worker ? $existing->worker->name : null,
            ];
        }

        return response()->json([
            'success' => true,
            'block_code' => $blockCode,
            'total_gharanas' => count($gharanaList),
            'total_voters' => $voters->flatten()->count(),
            'gharanas' => $gharanaList,
        ]);
    }

    /**
     * 4. Batch Upload Surveyed Households (Silent Background Sync)
     */
    public function staffSurveySync(Request $request): JsonResponse
    {
        $worker = $this->getAuthenticatedWorker($request);
        if (!$worker) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated staff member.'], 401);
        }

        $request->validate([
            'surveys' => 'required|array',
            'surveys.*.gharana_no' => 'required|integer',
            'surveys.*.sentiment' => 'required|in:pakka,kacha,mukhalif,unassigned',
            'surveys.*.notes' => 'nullable|string|max:500',
            'surveys.*.influencer_name' => 'nullable|string|max:150',
            'surveys.*.is_vip_visit_requested' => 'nullable|boolean',
            'surveys.*.visited_at' => 'nullable|date',
        ]);

        $blockCode = $worker->assigned_block_code;
        $candidateId = $worker->candidate_id;
        $processed = 0;

        $block = BlockCode::where('code', $blockCode)->first();
        $blockCodeId = $block ? $block->id : null;

        foreach ($request->input('surveys') as $item) {
            $gharanaNo = (int) $item['gharana_no'];
            
            // Count actual voters in this gharana if not explicitly provided
            $voterCount = !empty($item['voter_count']) ? (int) $item['voter_count'] : (
                $blockCodeId ? Voter::where('block_code_id', $blockCodeId)->where('gharana_no', $gharanaNo)->count() : 1
            );
            if ($voterCount <= 0) {
                $voterCount = 1;
            }

            GharanaSurvey::updateOrCreate(
                [
                    'candidate_id' => $candidateId,
                    'block_code' => $blockCode,
                    'gharana_no' => $gharanaNo,
                ],
                [
                    'sentiment' => $item['sentiment'],
                    'notes' => $item['notes'] ?? null,
                    'influencer_name' => $item['influencer_name'] ?? null,
                    'voter_count' => $voterCount,
                    'is_vip_visit_requested' => !empty($item['is_vip_visit_requested']),
                    'visited_by_worker_id' => $worker->id,
                    'visited_at' => !empty($item['visited_at']) ? $item['visited_at'] : now(),
                ]
            );
            $processed++;
        }

        $worker->update(['last_sync_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "Successfully synced {$processed} household surveys.",
            'synced_count' => $processed,
            'last_sync_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * 5. Candidate Self-Service: List or Create Workers
     */
    public function candidateWorkers(Request $request): JsonResponse
    {
        $candidate = $this->getAuthenticatedCandidate($request);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated candidate.'], 401);
        }

        // Handle POST: Create new worker
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:150',
                'phone' => 'nullable|string|max:50',
                'assigned_block_code' => 'required|string|max:50',
                'pin' => 'nullable|string|min:4|max:10',
            ]);

            // Auto-generate 4-digit PIN if not specified
            $pin = !empty($validated['pin']) ? trim($validated['pin']) : (string) mt_rand(1000, 9999);

            $worker = CampaignWorker::create([
                'candidate_id' => $candidate->id,
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'assigned_block_code' => $validated['assigned_block_code'],
                'pin' => $pin,
                'is_active' => true,
            ]);

            $shareText = rawurlencode("Salam {$worker->name}! VoterApp Campaign login details:\nCandidate Code: {$candidate->candidate_code}\nWorker PIN: {$pin}\nAssigned Block: {$worker->assigned_block_code}");

            return response()->json([
                'success' => true,
                'message' => "Worker [{$worker->name}] created successfully.",
                'worker' => $worker,
                'plain_pin' => $pin,
                'whatsapp_message' => "Salam {$worker->name}! VoterApp Campaign login details:\nCandidate Code: {$candidate->candidate_code}\nWorker PIN: {$pin}\nAssigned Block: {$worker->assigned_block_code}",
                'whatsapp_share_url' => "https://wa.me/?text=" . $shareText,
            ], 201);
        }

        // Handle GET: List workers with performance metrics
        $workers = CampaignWorker::where('candidate_id', $candidate->id)
            ->withCount([
                'surveys as visited_count' => fn ($q) => $q->whereNotNull('visited_at'),
                'surveys as pakka_count' => fn ($q) => $q->where('sentiment', 'pakka'),
            ])
            ->latest()
            ->get()
            ->map(function ($w) use ($candidate) {
                return [
                    'id' => $w->id,
                    'name' => $w->name,
                    'phone' => $w->phone,
                    'pin' => '****',
                    'assigned_block_code' => $w->assigned_block_code,
                    'device_uid' => $w->device_uid,
                    'is_active' => $w->is_active,
                    'visited_count' => $w->visited_count,
                    'pakka_count' => $w->pakka_count,
                    'last_sync_at' => $w->last_sync_at ? $w->last_sync_at->diffForHumans() : 'Never',
                    'is_idle' => $w->last_sync_at ? $w->last_sync_at->diffInHours(now()) >= 3 : true,
                    'whatsapp_text' => "Salam {$w->name}! VoterApp details: Candidate Code: {$candidate->candidate_code} | Block: {$w->assigned_block_code}",
                ];
            });

        return response()->json([
            'success' => true,
            'workers' => $workers,
        ]);
    }

    /**
     * 6. Candidate Self-Service: Update Worker
     */
    public function updateWorker(Request $request, $id): JsonResponse
    {
        $candidate = $this->getAuthenticatedCandidate($request);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated candidate.'], 401);
        }

        $worker = CampaignWorker::where('candidate_id', $candidate->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'phone' => 'nullable|string|max:50',
            'assigned_block_code' => 'sometimes|string|max:50',
            'pin' => 'sometimes|string|min:4|max:10',
            'is_active' => 'sometimes|boolean',
        ]);

        $worker->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Worker [{$worker->name}] updated successfully.",
            'worker' => $worker,
        ]);
    }

    /**
     * 7. Candidate Self-Service: Delete / Deactivate Worker
     */
    public function deleteWorker(Request $request, $id): JsonResponse
    {
        $candidate = $this->getAuthenticatedCandidate($request);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated candidate.'], 401);
        }

        $worker = CampaignWorker::where('candidate_id', $candidate->id)->findOrFail($id);
        $worker->delete();

        return response()->json([
            'success' => true,
            'message' => 'Worker removed successfully.',
        ]);
    }

    /**
     * 8. Candidate War Room Dashboard
     * Returns complete real-time campaign matrix for mobile screen.
     */
    public function candidateWarRoom(Request $request): JsonResponse
    {
        $candidate = $this->getAuthenticatedCandidate($request);
        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated candidate.'], 401);
        }

        $ucId = $candidate->uc_id;

        // Total Voters and Block Codes in this candidate's UC
        $blockCodeModels = BlockCode::where('uc_id', $ucId)->get();
        $blockCodeIds = $blockCodeModels->pluck('id');
        $totalVotersCount = Voter::whereIn('block_code_id', $blockCodeIds)->count();
        $totalGharanasCount = Voter::whereIn('block_code_id', $blockCodeIds)->distinct('gharana_no')->count('gharana_no');

        // Aggregated Survey Data
        $surveys = GharanaSurvey::where('candidate_id', $candidate->id)->get();
        $visitedGharanasCount = $surveys->whereNotNull('visited_at')->count();
        $coveragePct = $totalGharanasCount > 0 ? round(($visitedGharanasCount / $totalGharanasCount) * 100, 1) : 0;

        $pakkaVotes = (int) $surveys->where('sentiment', 'pakka')->sum('voter_count');
        $kachaVotes = (int) $surveys->where('sentiment', 'kacha')->sum('voter_count');
        $mukhalifVotes = (int) $surveys->where('sentiment', 'mukhalif')->sum('voter_count');
        $surveyedVoters = $pakkaVotes + $kachaVotes + $mukhalifVotes;
        $unassignedVoters = max(0, $totalVotersCount - $surveyedVoters);

        // Turnout on election day (Parchi issued)
        $turnoutCount = (int) $surveys->whereNotNull('parchi_issued_at')->sum('voter_count');

        // Worker Leaderboard
        $workers = CampaignWorker::where('candidate_id', $candidate->id)
            ->withCount([
                'surveys as visited_count' => fn ($q) => $q->whereNotNull('visited_at'),
                'surveys as pakka_count' => fn ($q) => $q->where('sentiment', 'pakka'),
            ])
            ->get()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'name' => $w->name,
                    'block_code' => $w->assigned_block_code,
                    'visited_count' => $w->visited_count,
                    'pakka_count' => $w->pakka_count,
                    'is_idle' => $w->last_sync_at ? $w->last_sync_at->diffInHours(now()) >= 3 : true,
                    'last_sync' => $w->last_sync_at ? $w->last_sync_at->diffForHumans() : 'Never',
                ];
            });

        // Top VIP / Swing Hit-List
        $vipHitList = GharanaSurvey::where('candidate_id', $candidate->id)
            ->where(function ($q) {
                $q->where('is_vip_visit_requested', true)
                  ->orWhere('sentiment', 'kacha');
            })
            ->orderByDesc('voter_count')
            ->take(20)
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'block_code' => $s->block_code,
                    'gharana_no' => $s->gharana_no,
                    'voter_count' => $s->voter_count,
                    'influencer_name' => $s->influencer_name,
                    'notes' => $s->notes,
                    'sentiment' => $s->sentiment,
                ];
            });

        $summary = [
            'total_voters' => $totalVotersCount,
            'total_gharanas' => $totalGharanasCount,
            'visited_gharanas' => $visitedGharanasCount,
            'coverage_pct' => $coveragePct,
            'pakka_votes' => $pakkaVotes,
            'kacha_votes' => $kachaVotes,
            'mukhalif_votes' => $mukhalifVotes,
            'unassigned_votes' => $unassignedVoters,
            'turnout_voted' => $turnoutCount,
        ];

        return response()->json([
            'success' => true,
            'uc_name' => $candidate->uc ? $candidate->uc->name : 'Assigned UC',
            'summary' => $summary,
            'campaign_metrics' => $summary,
            'workers' => $workers,
            'worker_leaderboard' => $workers,
            'vip_hit_list' => $vipHitList,
            'vip_visit_hitlist' => $vipHitList,
        ]);
    }

    /**
     * 9. Camp Thermal Parchi Issuance (Election Day Turnout Trigger)
     */
    public function campIssueParchi(Request $request): JsonResponse
    {
        // Auth check: Worker or Candidate token required
        $worker = $this->getAuthenticatedWorker($request);
        $candidate = null;

        if ($worker) {
            $candidate = $worker->candidate;
        } else {
            $candidate = $this->getAuthenticatedCandidate($request);
        }

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'voter_id' => 'nullable|integer',
            'block_code' => 'nullable|string',
            'gharana_no' => 'nullable|integer',
        ]);

        $blockCodeStr = $request->block_code ?: ($worker ? $worker->assigned_block_code : null);
        $gharanaNo = $request->gharana_no;

        if ($request->filled('voter_id')) {
            $voter = Voter::with('blockCode')->find($request->voter_id);
            if ($voter) {
                $blockCodeStr = $blockCodeStr ?: ($voter->blockCode ? $voter->blockCode->code : null);
                $gharanaNo = $gharanaNo ?: $voter->gharana_no;
            }
        }

        $survey = GharanaSurvey::where('candidate_id', $candidate->id)
            ->where(function ($q) use ($blockCodeStr, $gharanaNo) {
                if ($blockCodeStr) {
                    $q->where('block_code', $blockCodeStr);
                }
                if ($gharanaNo) {
                    $q->where('gharana_no', $gharanaNo);
                }
            })
            ->first();

        if ($survey) {
            $survey->update(['parchi_issued_at' => now()]);
        } else {
            $survey = GharanaSurvey::create([
                'candidate_id' => $candidate->id,
                'block_code' => $blockCodeStr ?: '101010101',
                'gharana_no' => $gharanaNo ?: 1,
                'sentiment' => 'pakka',
                'voter_count' => 1,
                'parchi_issued_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Parchi issued and turnout recorded.',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * 10. Background Cron Job Engine (for cron-job.org)
     */
    public function processCampaignMatrix(Request $request): JsonResponse
    {
        // Verify Cron Secret Key
        $secret = config('app.cron_secret') ?: env('CRON_SECRET');
        if (empty($secret) || $request->header('X-Cron-Secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $candidates = User::where('role', 'candidate')->where('status', 'active')->get();
        $processed = 0;

        foreach ($candidates as $cand) {
            // Pre-calculate and cache candidate stats for instantaneous mobile load
            $cacheKey = "candidate_war_room_{$cand->id}";
            $surveys = GharanaSurvey::where('candidate_id', $cand->id)->get();
            
            $stats = [
                'updated_at' => now()->toDateTimeString(),
                'pakka_votes' => (int) $surveys->where('sentiment', 'pakka')->sum('voter_count'),
                'kacha_votes' => (int) $surveys->where('sentiment', 'kacha')->sum('voter_count'),
                'mukhalif_votes' => (int) $surveys->where('sentiment', 'mukhalif')->sum('voter_count'),
                'visited_gharanas' => $surveys->whereNotNull('visited_at')->count(),
            ];

            Cache::put($cacheKey, $stats, now()->addHours(6));
            $processed++;
        }

        return response()->json([
            'success' => true,
            'message' => "Processed campaign matrix for {$processed} active candidates.",
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Helper to map political party names to authentic Pakistani theme color palettes.
     */
    protected function resolvePartyColors(?string $partyName): array
    {
        $name = strtoupper(trim((string) $partyName));

        if (str_contains($name, 'PTI') || str_contains($name, 'INSAF')) {
            return [
                'primary' => '#006633',
                'secondary' => '#d90429',
                'gradient' => 'linear-gradient(135deg, #006633 0%, #d90429 100%)',
                'accent' => '#10b981',
                'text' => '#ffffff',
            ];
        }

        if (str_contains($name, 'PML') || str_contains($name, 'LEAGUE') || str_contains($name, 'NAWAZ')) {
            return [
                'primary' => '#00703c',
                'secondary' => '#e5a823',
                'gradient' => 'linear-gradient(135deg, #00703c 0%, #e5a823 100%)',
                'accent' => '#f59e0b',
                'text' => '#ffffff',
            ];
        }

        if (str_contains($name, 'PPP') || str_contains($name, 'PEOPLES')) {
            return [
                'primary' => '#0f172a',
                'secondary' => '#dc2626',
                'gradient' => 'linear-gradient(135deg, #0f172a 0%, #dc2626 50%, #16a34a 100%)',
                'accent' => '#dc2626',
                'text' => '#ffffff',
            ];
        }

        if (str_contains($name, 'JI') || str_contains($name, 'ISLAMI')) {
            return [
                'primary' => '#008080',
                'secondary' => '#0284c7',
                'gradient' => 'linear-gradient(135deg, #008080 0%, #0284c7 100%)',
                'accent' => '#38bdf8',
                'text' => '#ffffff',
            ];
        }

        // Default Independent / Neutral Green & Slate
        return [
            'primary' => '#006633',
            'secondary' => '#0284c7',
            'gradient' => 'linear-gradient(135deg, #006633 0%, #0284c7 100%)',
            'accent' => '#059669',
            'text' => '#ffffff',
        ];
    }

    /**
     * Authenticate field worker via Bearer token
     */
    protected function getAuthenticatedWorker(Request $request): ?CampaignWorker
    {
        $token = $request->bearerToken() ?: $request->input('token');
        if (!$token) {
            return null;
        }

        return CampaignWorker::where('api_token', hash('sha256', $token))
            ->where('is_active', true)
            ->first();
    }

    /**
     * Authenticate candidate via session, device bearer token, or candidate_code
     */
    protected function getAuthenticatedCandidate(Request $request): ?User
    {
        // 1. Session Auth
        if (auth()->check() && auth()->user()->isCandidate()) {
            return auth()->user();
        }

        // 2. Candidate Device Bearer Token
        $token = $request->bearerToken() ?: $request->input('token');
        if ($token) {
            $device = CandidateDevice::where('api_token', hash('sha256', $token))
                ->where('is_revoked', false)
                ->first();
            if ($device) {
                return $device->candidate;
            }
        }

        // 3. Fallback: candidate_code directly passed by mobile app
        if ($request->filled('candidate_code')) {
            return User::where('candidate_code', strtoupper(trim($request->input('candidate_code'))))
                ->where('role', 'candidate')
                ->where('status', 'active')
                ->first();
        }

        // 4. Fallback: candidate_id directly passed
        if ($request->filled('candidate_id')) {
            return User::where('id', $request->input('candidate_id'))
                ->where('role', 'candidate')
                ->where('status', 'active')
                ->first();
        }

        return null;
    }
}
