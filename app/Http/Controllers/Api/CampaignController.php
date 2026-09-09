<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlockCode;
use App\Models\CampaignWorker;
use App\Models\CandidateDevice;
use App\Models\GharanaSurvey;
use App\Models\User;
use App\Models\Voter;
use App\Services\FirebaseNotificationService;
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
            'candidate_code' => 'nullable|string',
            'pin' => 'required|string|min:4|max:10',
            'device_uid' => 'nullable|string|max:100',
        ]);

        $candidate = null;
        $worker = null;

        if (!empty($validated['candidate_code'])) {
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
        } else {
            $workers = CampaignWorker::where('is_active', true)->with('candidate')->get();
            $worker = $workers->first(fn ($w) => Hash::check(trim($validated['pin']), $w->pin));
            if ($worker && $worker->candidate) {
                $candidate = $worker->candidate;
                if ($candidate->status !== 'active') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Candidate license is currently inactive.',
                    ], 403);
                }
            }
        }

        if (!$worker || !$candidate) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid 4-digit PIN.',
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
     * 2.5 Register / Update FCM Push Notification Device Token
     * Supports both Candidate Handheld and Field Worker phones.
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string|max:1000',
        ]);

        $fcmToken = trim($request->input('fcm_token'));
        $worker = $this->getAuthenticatedWorker($request);
        $candidate = $this->getAuthenticatedCandidate($request);

        if (!$worker && !$candidate) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'Unauthenticated device or invalid token.',
            ], 401);
        }

        if ($worker) {
            $worker->update(['fcm_token' => $fcmToken]);
            return response()->json([
                'success' => true,
                'status' => true,
                'role' => 'worker',
                'message' => 'Worker FCM device token registered successfully.',
            ]);
        }

        // For Candidate: update active device matching device_uid or latest active device
        $deviceUid = $request->header('X-Device-UID') ?: $request->input('device_uid');
        $deviceQuery = CandidateDevice::where('user_id', $candidate->id);
        if ($deviceUid) {
            $device = $deviceQuery->where('device_uid', $deviceUid)->first();
        } else {
            $device = $deviceQuery->latest('last_active_at')->first();
        }

        if ($device) {
            $device->update(['fcm_token' => $fcmToken]);
        }

        return response()->json([
            'success' => true,
            'status' => true,
            'role' => 'candidate',
            'message' => 'Candidate device FCM token registered successfully.',
        ]);
    }

    /**
     * 3. Download Assigned Block Code Data for Offline Door-to-Door Walk
     * Supports single or multiple assigned block codes (e.g. "185010401, 185010402")
     * and optional query parameter ?block_code=... or ?block_codes=...
     */
    public function staffBlockData(Request $request): JsonResponse
    {
        $worker = $this->getAuthenticatedWorker($request);
        if (!$worker) {
            return response()->json(['success' => false, 'status' => false, 'message' => 'Unauthenticated staff member.'], 401);
        }

        // Determine target block codes:
        // 1. From query parameter if worker / mobile app requests a specific block (?block_code=... or ?block_codes=...)
        // 2. From worker's assigned_block_code (which could be single or comma-separated)
        $requestedCodes = $request->query('block_codes') ?? $request->query('block_code');
        $rawCodes = $requestedCodes ?: $worker->assigned_block_code;

        $blockCodes = array_values(array_filter(array_map('trim', explode(',', (string) $rawCodes))));

        if (empty($blockCodes)) {
            return response()->json([
                'success' => false,
                'status' => false,
                'message' => 'No block codes assigned to this staff member.',
            ], 404);
        }

        // Find BlockCode models
        $blockModels = BlockCode::whereIn('code', $blockCodes)->get();
        $blockCodeIds = $blockModels->pluck('id')->toArray();
        $blockIdToCodeMap = $blockModels->pluck('code', 'id')->toArray();

        // Fetch voters across these block codes
        $voters = Voter::whereIn('block_code_id', $blockCodeIds)
            ->select('id', 'block_code_id', 'name', 'father_name', 'cnic', 'gharana_no', 'silsala_no', 'age', 'address')
            ->orderBy('block_code_id')
            ->orderBy('gharana_no')
            ->orderBy('silsala_no')
            ->get();

        // Fetch existing survey outcomes across these block codes under this candidate
        $surveys = GharanaSurvey::where('candidate_id', $worker->candidate_id)
            ->whereIn('block_code', $blockCodes)
            ->get()
            ->keyBy(fn ($s) => $s->block_code . '_' . $s->gharana_no);

        // Group voters by block_code + gharana_no
        $grouped = $voters->groupBy(fn ($v) => ($blockIdToCodeMap[$v->block_code_id] ?? 'unknown') . '_' . $v->gharana_no);

        $gharanaList = [];
        $blocksSummary = [];

        foreach ($blockModels as $bModel) {
            $blocksSummary[$bModel->code] = [
                'code' => $bModel->code,
                'area_name' => $bModel->area_name,
                'total_voters' => 0,
                'total_gharanas' => 0,
            ];
        }

        foreach ($grouped as $key => $familyVoters) {
            $firstVoter = $familyVoters->first();
            $bCode = $blockIdToCodeMap[$firstVoter->block_code_id] ?? 'unknown';
            $gharanaNo = (int) $firstVoter->gharana_no;

            $surveyKey = $bCode . '_' . $gharanaNo;
            $existing = $surveys->get($surveyKey);

            if (isset($blocksSummary[$bCode])) {
                $blocksSummary[$bCode]['total_voters'] += $familyVoters->count();
                $blocksSummary[$bCode]['total_gharanas'] += 1;
            }

            $gharanaList[] = [
                'block_code' => $bCode,
                'gharana_no' => $gharanaNo,
                'voter_count' => $familyVoters->count(),
                'head_name' => $firstVoter->name ?? 'Family',
                'voters' => $familyVoters->values(),
                'sentiment' => $existing ? $existing->sentiment : 'unassigned',
                'notes' => $existing ? $existing->notes : null,
                'influencer_name' => $existing ? $existing->influencer_name : null,
                'is_vip_visit_requested' => $existing ? (bool) $existing->is_vip_visit_requested : false,
                'is_visited' => $existing && $existing->visited_at !== null,
                'visited_at' => $existing ? $existing->visited_at : null,
                'visited_by' => $existing && $existing->worker ? $existing->worker->name : null,
            ];
        }

        $primaryBlockCode = $blockCodes[0] ?? $worker->assigned_block_code;

        return response()->json([
            'success' => true,
            'status' => true,
            'is_multi_block' => count($blockCodes) > 1,
            'block_code' => $primaryBlockCode,
            'assigned_block_code' => $worker->assigned_block_code,
            'block_codes' => $blockCodes,
            'blocks_summary' => array_values($blocksSummary),
            'total_blocks' => count($blockCodes),
            'total_gharanas' => count($gharanaList),
            'total_voters' => $voters->count(),
            'gharanas' => $gharanaList,
            'data' => $gharanaList,
        ]);
    }

    /**
     * 4. Batch Upload Surveyed Households (Silent Background Sync)
     */
    public function staffSurveySync(Request $request): JsonResponse
    {
        $worker = $this->getAuthenticatedWorker($request);
        if (!$worker) {
            return response()->json(['success' => false, 'status' => false, 'message' => 'Unauthenticated staff member.'], 401);
        }

        $surveysInput = $request->input('surveys');
        if (!is_array($surveysInput) || empty($surveysInput)) {
            return response()->json(['success' => false, 'status' => false, 'message' => 'The surveys field is required.'], 422);
        }

        // Normalize survey entries for aliases
        $normalized = [];
        foreach ($surveysInput as $s) {
            $sentiment = $s['sentiment'] ?? $s['party_inclination'] ?? 'unassigned';
            if ($sentiment === 'neutral') $sentiment = 'unassigned';
            $s['sentiment'] = $sentiment;
            $s['gharana_no'] = (int) ($s['gharana_no'] ?? 0);
            $normalized[] = $s;
        }

        $request->merge(['surveys' => $normalized]);

        $request->validate([
            'surveys' => 'required|array',
            'surveys.*.gharana_no' => 'required|integer',
            'surveys.*.block_code' => 'nullable|string|max:50',
            'surveys.*.sentiment' => 'required|in:pakka,kacha,mukhalif,unassigned',
            'surveys.*.notes' => 'nullable|string|max:500',
            'surveys.*.influencer_name' => 'nullable|string|max:150',
            'surveys.*.influencer_phone' => 'nullable|string|max:30',
            'surveys.*.latitude' => 'nullable|numeric',
            'surveys.*.longitude' => 'nullable|numeric',
            'surveys.*.is_vip_visit_requested' => 'nullable|boolean',
            'surveys.*.visited_at' => 'nullable|date',
        ]);

        $assignedCodes = array_values(array_filter(array_map('trim', explode(',', (string) $worker->assigned_block_code))));
        $primaryBlockCode = $assignedCodes[0] ?? $worker->assigned_block_code;
        $candidateId = $worker->candidate_id;
        $processed = 0;

        // Cache block models
        $blockCache = BlockCode::whereIn('code', $assignedCodes)->get()->keyBy('code');

        foreach ($request->input('surveys') as $item) {
            $gharanaNo = (int) $item['gharana_no'];
            $itemBlockCode = !empty($item['block_code']) ? trim($item['block_code']) : $primaryBlockCode;

            $block = $blockCache->get($itemBlockCode) ?? BlockCode::where('code', $itemBlockCode)->first();
            $blockCodeId = $block ? $block->id : null;

            // Count actual voters in this gharana if not explicitly provided
            $voterCount = !empty($item['voter_count']) ? (int) $item['voter_count'] : (
                $blockCodeId ? Voter::where('block_code_id', $blockCodeId)->where('gharana_no', $gharanaNo)->count() : 1
            );
            if ($voterCount <= 0) {
                $voterCount = 1;
            }

            $isVipRequested = !empty($item['is_vip_visit_requested']);

            GharanaSurvey::updateOrCreate(
                [
                    'candidate_id' => $candidateId,
                    'block_code' => $itemBlockCode,
                    'gharana_no' => $gharanaNo,
                ],
                [
                    'sentiment' => $item['sentiment'],
                    'notes' => $item['notes'] ?? null,
                    'influencer_name' => $item['influencer_name'] ?? null,
                    'influencer_phone' => $item['influencer_phone'] ?? null,
                    'voter_count' => $voterCount,
                    'is_vip_visit_requested' => $isVipRequested,
                    'visited_by_worker_id' => $worker->id,
                    'visited_at' => !empty($item['visited_at']) ? $item['visited_at'] : now(),
                    'latitude' => $item['latitude'] ?? null,
                    'longitude' => $item['longitude'] ?? null,
                ]
            );

            // Auto-trigger FCM push alert to Candidate if VIP visit is requested
            if ($isVipRequested) {
                try {
                    $influencer = !empty($item['influencer_name']) ? $item['influencer_name'] : 'Family Head';
                    $phone = !empty($item['influencer_phone']) ? $item['influencer_phone'] : 'N/A';
                    app(FirebaseNotificationService::class)->sendToCandidate(
                        $candidateId,
                        "🚨 VIP Daurah Darkhwast — Gharana #{$gharanaNo}",
                        "Worker {$worker->name} ne Block {$itemBlockCode} ke Gharana #{$gharanaNo} ({$influencer}) ke liye candidate visit ki request ki hai.",
                        [
                            'type' => 'vip_visit_request',
                            'block_code' => (string) $itemBlockCode,
                            'gharana_no' => (string) $gharanaNo,
                            'influencer_name' => (string) $influencer,
                            'influencer_phone' => (string) $phone,
                            'notes' => (string) ($item['notes'] ?? ''),
                        ]
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("[FCM VIP ALERT TRIGGER ERROR] " . $e->getMessage());
                }
            }

            $processed++;
        }

        $worker->update(['last_sync_at' => now()]);

        return response()->json([
            'success' => true,
            'status' => true,
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
            return response()->json(['success' => false, 'status' => false, 'message' => 'Unauthenticated candidate.'], 401);
        }

        // Handle POST: Create new worker
        if ($request->isMethod('post')) {
            $validated = $request->validate([
                'name' => 'required|string|max:150',
                'phone' => 'nullable|string|max:50',
                'assigned_block_code' => 'required|string|max:255',
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

            $cleanPhone = preg_replace('/[^0-9]/', '', (string) ($validated['phone'] ?? ''));
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '92' . substr($cleanPhone, 1);
            }

            $waMsg = "Salam {$worker->name}!\n\nYou have been assigned as a Field Worker for {$candidate->name}'s election campaign.\n\n📱 *VoterApp Login Details:*\n🔹 Candidate Code: *{$candidate->candidate_code}*\n🔹 Worker PIN: *{$pin}*\n🔹 Assigned Block: *{$worker->assigned_block_code}*\n\nPlease open the VoterApp mobile app, enter Candidate Code & PIN on the Field Worker tab, and start the door-to-door survey.";

            $waUrl = !empty($cleanPhone)
                ? "https://wa.me/{$cleanPhone}?text=" . rawurlencode($waMsg)
                : "https://wa.me/?text=" . rawurlencode($waMsg);

            return response()->json([
                'success' => true,
                'status' => true,
                'message' => "Worker [{$worker->name}] created successfully.",
                'worker' => $worker,
                'data' => $worker,
                'plain_pin' => $pin,
                'candidate_code' => $candidate->candidate_code,
                'whatsapp_message' => $waMsg,
                'whatsapp_share_url' => $waUrl,
                'phone' => $worker->phone,
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
                $cleanPhone = preg_replace('/[^0-9]/', '', (string) ($w->phone ?? ''));
                if (str_starts_with($cleanPhone, '0')) {
                    $cleanPhone = '92' . substr($cleanPhone, 1);
                }
                $listMsg = "Salam {$w->name}!\n\n📱 *VoterApp Login Details:*\n🔹 Candidate Code: *{$candidate->candidate_code}*\n🔹 Assigned Block: *{$w->assigned_block_code}*\n\nPlease open VoterApp mobile app to continue.";
                return [
                    'id' => $w->id,
                    'name' => $w->name,
                    'phone' => $w->phone,
                    'pin' => $w->pin && strlen($w->pin) <= 10 && !str_starts_with($w->pin, '$') ? $w->pin : '****',
                    'assigned_block_code' => $w->assigned_block_code,
                    'device_uid' => $w->device_uid,
                    'is_active' => (bool) $w->is_active,
                    'visited_count' => (int) $w->visited_count,
                    'pakka_count' => (int) $w->pakka_count,
                    'last_sync_at' => $w->last_sync_at ? $w->last_sync_at->diffForHumans() : 'Never',
                    'is_idle' => $w->last_sync_at ? $w->last_sync_at->diffInHours(now()) >= 3 : true,
                    'whatsapp_text' => $listMsg,
                    'whatsapp_url' => !empty($cleanPhone) ? "https://wa.me/{$cleanPhone}?text=" . rawurlencode($listMsg) : "https://wa.me/?text=" . rawurlencode($listMsg),
                ];
            });

        return response()->json([
            'success' => true,
            'status' => true,
            'workers' => $workers,
            'data' => $workers,
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'candidate_code' => $candidate->candidate_code,
                'party_name' => $candidate->party_name,
            ],
        ]);
    }

    /**
     * 6. Candidate Self-Service: Update Worker
     */
    public function updateWorker(Request $request, $id): JsonResponse
    {
        $candidate = $this->getAuthenticatedCandidate($request);
        if (!$candidate) {
            return response()->json(['success' => false, 'status' => false, 'message' => 'Unauthenticated candidate.'], 401);
        }

        $worker = CampaignWorker::where('candidate_id', $candidate->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:150',
            'phone' => 'nullable|string|max:50',
            'assigned_block_code' => 'sometimes|string|max:255',
            'pin' => 'sometimes|string|min:4|max:10',
            'is_active' => 'sometimes|boolean',
        ]);

        $worker->update($validated);

        return response()->json([
            'success' => true,
            'status' => true,
            'message' => "Worker [{$worker->name}] updated successfully.",
            'worker' => $worker,
            'data' => $worker,
        ]);
    }

    /**
     * 7. Candidate Self-Service: Delete / Deactivate Worker
     */
    public function deleteWorker(Request $request, $id): JsonResponse
    {
        $candidate = $this->getAuthenticatedCandidate($request);
        if (!$candidate) {
            return response()->json(['success' => false, 'status' => false, 'message' => 'Unauthenticated candidate.'], 401);
        }

        $worker = CampaignWorker::where('candidate_id', $candidate->id)->findOrFail($id);
        $worker->delete();

        return response()->json([
            'success' => true,
            'status' => true,
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
            return response()->json(['success' => false, 'status' => false, 'message' => 'Unauthenticated candidate.'], 401);
        }

        $ucId = $candidate->uc_id;

        // Total Voters and Block Codes in this candidate's UC
        $blockCodeModels = BlockCode::where('uc_id', $ucId)->get();
        $blockCodeIds = $blockCodeModels->pluck('id');
        $totalVotersCount = Voter::whereIn('block_code_id', $blockCodeIds)->count();

        // Count true distinct households across all blocks in this UC
        $totalGharanasCount = \Illuminate\Support\Facades\DB::table('voters')
            ->whereIn('block_code_id', $blockCodeIds)
            ->select('block_code_id', 'gharana_no')
            ->distinct()
            ->get()
            ->count();

        if ($totalGharanasCount === 0 && $totalVotersCount > 0) {
            $totalGharanasCount = Voter::whereIn('block_code_id', $blockCodeIds)->distinct('gharana_no')->count('gharana_no');
        }

        // Aggregated Survey Data for this Candidate
        $surveys = GharanaSurvey::where('candidate_id', $candidate->id)->get();
        
        // Count visited gharanas (either visited_at is set, or sentiment is marked)
        $visitedGharanasCount = $surveys->filter(function ($s) {
            return $s->visited_at !== null || in_array($s->sentiment, ['pakka', 'kacha', 'mukhalif']);
        })->count();

        // Coverage percentage (Safely capped at 100.0% to prevent Flutter progress bar assertions)
        $rawCoverage = $totalGharanasCount > 0 ? round(($visitedGharanasCount / $totalGharanasCount) * 100, 1) : 0.0;
        $coveragePct = min(100.0, max(0.0, $rawCoverage));
        $coverageRatio = round($coveragePct / 100, 3);

        $pakkaVotes = (int) $surveys->where('sentiment', 'pakka')->sum('voter_count');
        $kachaVotes = (int) $surveys->where('sentiment', 'kacha')->sum('voter_count');
        $mukhalifVotes = (int) $surveys->where('sentiment', 'mukhalif')->sum('voter_count');
        $surveyedVoters = $pakkaVotes + $kachaVotes + $mukhalifVotes;
        $unassignedVoters = max(0, $totalVotersCount - $surveyedVoters);

        // Turnout on election day (Parchi issued)
        $turnoutCount = (int) $surveys->whereNotNull('parchi_issued_at')->sum('voter_count');

        // Worker Leaderboard with dual key naming
        $workers = CampaignWorker::where('candidate_id', $candidate->id)
            ->withCount([
                'surveys as visited_count' => fn ($q) => $q->whereNotNull('visited_at'),
                'surveys as pakka_count' => fn ($q) => $q->where('sentiment', 'pakka'),
            ])
            ->latest()
            ->get()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'name' => $w->name,
                    'phone' => $w->phone,
                    'block_code' => $w->assigned_block_code,
                    'assigned_block_code' => $w->assigned_block_code,
                    'visited_count' => (int) $w->visited_count,
                    'pakka_count' => (int) $w->pakka_count,
                    'is_idle' => $w->last_sync_at ? $w->last_sync_at->diffInHours(now()) >= 3 : true,
                    'last_sync' => $w->last_sync_at ? $w->last_sync_at->diffForHumans() : 'Never',
                    'last_sync_at' => $w->last_sync_at ? $w->last_sync_at->diffForHumans() : 'Never',
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
                    'gharana_no' => (int) $s->gharana_no,
                    'voter_count' => (int) $s->voter_count,
                    'influencer_name' => $s->influencer_name,
                    'influencer_phone' => $s->influencer_phone,
                    'notes' => $s->notes,
                    'sentiment' => $s->sentiment,
                    'is_vip_visit_requested' => (bool) $s->is_vip_visit_requested,
                    'latitude' => $s->latitude ? (float) $s->latitude : null,
                    'longitude' => $s->longitude ? (float) $s->longitude : null,
                ];
            });

        // Hourly Turnout breakdown on Election Day
        $parchiSurveys = $surveys->whereNotNull('parchi_issued_at');
        $slot0810 = 0; // 08:00 - 09:59
        $slot1012 = 0; // 10:00 - 11:59
        $slot1214 = 0; // 12:00 - 13:59
        $slot1417 = 0; // 14:00 - 17:00

        foreach ($parchiSurveys as $ps) {
            $h = (int) \Carbon\Carbon::parse($ps->parchi_issued_at)->format('G');
            $vc = (int) ($ps->voter_count ?: 1);
            if ($h >= 8 && $h < 10) {
                $slot0810 += $vc;
            } elseif ($h >= 10 && $h < 12) {
                $slot1012 += $vc;
            } elseif ($h >= 12 && $h < 14) {
                $slot1214 += $vc;
            } elseif ($h >= 14 && $h <= 17) {
                $slot1417 += $vc;
            }
        }

        $hourlyTurnout = [
            'slot_08_10' => $slot0810,
            'slot_10_12' => $slot1012,
            'slot_12_14' => $slot1214,
            'slot_14_17' => $slot1417,
            'total_turnout' => $turnoutCount,
            'breakdown' => [
                ['slot' => '08:00 AM - 10:00 AM', 'votes' => $slot0810, 'key' => '08_10'],
                ['slot' => '10:00 AM - 12:00 PM', 'votes' => $slot1012, 'key' => '10_12'],
                ['slot' => '12:00 PM - 02:00 PM', 'votes' => $slot1214, 'key' => '12_14'],
                ['slot' => '02:00 PM - 05:00 PM', 'votes' => $slot1417, 'key' => '14_17'],
            ],
        ];

        $summary = [
            'total_voters' => $totalVotersCount,
            'total_gharanas' => $totalGharanasCount,
            'visited_gharanas' => $visitedGharanasCount,
            'coverage_pct' => $coveragePct,
            'coverage_percentage' => $coveragePct,
            'coverage_ratio' => $coverageRatio,
            'pakka_votes' => $pakkaVotes,
            'kacha_votes' => $kachaVotes,
            'mukhalif_votes' => $mukhalifVotes,
            'unassigned_votes' => $unassignedVoters,
            'surveyed_votes' => $surveyedVoters,
            'turnout_voted' => $turnoutCount,
            'hourly_turnout' => $hourlyTurnout,
            // camelCase variants for Flutter/Dart models
            'totalVoters' => $totalVotersCount,
            'totalGharanas' => $totalGharanasCount,
            'visitedGharanas' => $visitedGharanasCount,
            'coveragePct' => $coveragePct,
            'pakkaVotes' => $pakkaVotes,
            'kachaVotes' => $kachaVotes,
            'mukhalifVotes' => $mukhalifVotes,
            'unassignedVotes' => $unassignedVoters,
            'turnoutVoted' => $turnoutCount,
            'hourlyTurnout' => $hourlyTurnout,
        ];

        $dataPayload = array_merge($summary, [
            'uc_name' => $candidate->uc ? $candidate->uc->name : 'Assigned UC',
            'summary' => $summary,
            'campaign_metrics' => $summary,
            'workers' => $workers,
            'worker_leaderboard' => $workers,
            'vip_hit_list' => $vipHitList,
            'vip_visit_hitlist' => $vipHitList,
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'candidate_code' => $candidate->candidate_code,
                'party_name' => $candidate->party_name,
            ],
            'server_time' => now()->toIso8601String(),
        ]);

        return response()->json(array_merge([
            'success' => true,
            'status' => true,
            'message' => 'War Room metrics loaded successfully.',
            'data' => $dataPayload,
        ], $dataPayload));
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
            'status' => true,
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
        $secret = config('app.cron_secret') ?: env('CRON_SECRET', 'voterapp_cron_secret_2026');
        $provided = $request->header('X-Cron-Secret') ?: $request->query('secret');
        if (empty($secret) || $provided !== $secret) {
            return response()->json(['status' => false, 'success' => false, 'error' => 'Unauthorized'], 401);
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
            'status' => true,
            'success' => true,
            'message' => "Processed campaign matrix for {$processed} active candidates.",
            'candidates_processed' => $processed,
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
     * Extract token from any possible mobile header, query, or body field
     */
    protected function extractTokenFromRequest(Request $request): ?string
    {
        // 1. Standard Laravel bearer token
        $token = $request->bearerToken();

        // 2. Raw Authorization header (strip 'Bearer ' or 'bearer ' if present, or take raw)
        if (!$token) {
            $authHeader = $request->header('Authorization') 
                ?? $request->header('authorization') 
                ?? $request->server('HTTP_AUTHORIZATION')
                ?? $request->server('REDIRECT_HTTP_AUTHORIZATION');

            if ($authHeader) {
                $token = preg_replace('/^\s*Bearer\s+/i', '', trim($authHeader));
            }
        }

        // 3. Custom Headers commonly used in mobile clients
        if (!$token) {
            $token = $request->header('X-Device-Token')
                ?? $request->header('X-Candidate-Token')
                ?? $request->header('X-Worker-Token')
                ?? $request->header('X-API-KEY')
                ?? $request->header('X-Auth-Token')
                ?? $request->header('token')
                ?? $request->header('api-token')
                ?? $request->header('device-token');
        }

        // 4. Query string or Request body parameters
        if (!$token) {
            $token = $request->input('token')
                ?? $request->input('api_token')
                ?? $request->input('device_token')
                ?? $request->input('session_token')
                ?? $request->query('token')
                ?? $request->query('api_token')
                ?? $request->query('device_token');
        }

        if ($token && is_string($token)) {
            $token = trim($token, " \t\n\r\0\x0B\"'");
            return $token !== '' ? $token : null;
        }

        return null;
    }

    /**
     * Authenticate field worker via Bearer token, custom header, or input token
     */
    protected function getAuthenticatedWorker(Request $request): ?CampaignWorker
    {
        $token = $this->extractTokenFromRequest($request);
        if (!$token) {
            return null;
        }

        return CampaignWorker::where(function ($q) use ($token) {
            $q->where('api_token', $token)
              ->orWhere('api_token', hash('sha256', $token));
        })
        ->where('is_active', true)
        ->first();
    }

    /**
     * Authenticate candidate via session, device bearer token, worker token, candidate_code, or candidate_id
     */
    protected function getAuthenticatedCandidate(Request $request): ?User
    {
        // 1. Session Auth (Web or WebView)
        if (auth()->check() && auth()->user()->isCandidate()) {
            return auth()->user();
        }

        // 2. Check request attributes (if CandidateAuthMiddleware already resolved it)
        if ($request->attributes->has('candidate_user')) {
            $user = $request->attributes->get('candidate_user');
            if ($user && $user->status === 'active') {
                return $user;
            }
        }
        if ($request->user() && method_exists($request->user(), 'isCandidate') && $request->user()->isCandidate() && $request->user()->status === 'active') {
            return $request->user();
        }

        // 3. Extract Token from all possible sources
        $token = $this->extractTokenFromRequest($request);

        if ($token) {
            // 3a. Search CandidateDevice by api_token (raw or sha256)
            $device = CandidateDevice::where(function ($q) use ($token) {
                    $q->where('api_token', $token)
                      ->orWhere('api_token', hash('sha256', $token));
                })
                ->where('is_revoked', false)
                ->first();

            if ($device && $device->user_id) {
                $candidate = User::find($device->user_id);
                if ($candidate && $candidate->status === 'active') {
                    $device->update(['last_active_at' => now(), 'ip_address' => $request->ip()]);
                    return $candidate;
                }
            }

            // 3b. Search CampaignWorker by api_token (if staff token is passed to candidate screen)
            $worker = CampaignWorker::where(function ($q) use ($token) {
                    $q->where('api_token', $token)
                      ->orWhere('api_token', hash('sha256', $token));
                })
                ->where('is_active', true)
                ->first();

            if ($worker && $worker->candidate_id) {
                $candidate = User::find($worker->candidate_id);
                if ($candidate && $candidate->status === 'active') {
                    return $candidate;
                }
            }

            // 3c. Direct candidate user token check (if token matches candidate_code directly)
            $candidateByToken = User::where('role', 'candidate')
                ->where('status', 'active')
                ->where(function ($q) use ($token) {
                    $q->where('candidate_code', strtoupper($token))
                      ->orWhere('candidate_code', $token);
                })
                ->first();
            if ($candidateByToken) {
                return $candidateByToken;
            }
        }

        // 4. Candidate Device UID fallback (from header, query, or body)
        $deviceUid = $request->header('X-Device-UID') 
            ?? $request->header('device-uid') 
            ?? $request->header('device_uid') 
            ?? $request->input('device_uid');

        if ($deviceUid) {
            $device = CandidateDevice::where('device_uid', trim($deviceUid))
                ->where('is_revoked', false)
                ->first();

            if ($device && $device->user_id) {
                $candidate = User::find($device->user_id);
                if ($candidate && $candidate->status === 'active') {
                    return $candidate;
                }
            }
        }

        // 5. Fallback: candidate_code from headers, query, or body
        $rawCode = $request->header('X-Candidate-Code')
            ?? $request->header('candidate-code')
            ?? $request->header('candidate_code')
            ?? $request->input('candidate_code')
            ?? $request->input('candidateCode')
            ?? $request->input('code');

        if ($rawCode && is_string($rawCode)) {
            $cleanCode = strtoupper(trim($rawCode));
            $candidate = User::where('role', 'candidate')
                ->where('status', 'active')
                ->where(function ($q) use ($cleanCode, $rawCode) {
                    $q->where('candidate_code', $cleanCode)
                      ->orWhere('candidate_code', trim($rawCode));
                })
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        // 6. Fallback: candidate_id / user_id from headers, query, or body
        $candidateId = $request->header('X-Candidate-Id')
            ?? $request->header('candidate-id')
            ?? $request->header('candidate_id')
            ?? $request->input('candidate_id')
            ?? $request->input('candidateId')
            ?? $request->input('user_id');

        if ($candidateId && is_numeric($candidateId)) {
            $candidate = User::where('id', $candidateId)
                ->where('role', 'candidate')
                ->where('status', 'active')
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        // 7. Log auth failure for debugging mobile app integration
        \Illuminate\Support\Facades\Log::warning('Candidate authentication failed for endpoint', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'headers' => [
                'authorization' => $request->header('Authorization') ? 'PRESENT' : 'NONE',
                'x-device-token' => $request->header('X-Device-Token'),
                'x-candidate-token' => $request->header('X-Candidate-Token'),
                'x-candidate-code' => $request->header('X-Candidate-Code'),
                'x-candidate-id' => $request->header('X-Candidate-Id'),
                'x-device-uid' => $request->header('X-Device-UID'),
            ],
            'query' => $request->query(),
            'post' => $request->except(['password', 'pin']),
        ]);

        return null;
    }
}
