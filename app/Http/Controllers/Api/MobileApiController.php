<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VoterResource;
use App\Models\BlockCode;
use App\Models\CandidateDevice;
use App\Models\PollingStation;
use App\Models\SearchLog;
use App\Models\UC;
use App\Models\User;
use App\Models\Voter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MobileApiController extends Controller
{
    /**
     * Mobile App Candidate Login & Verifiable Device Session Authorization.
     * Generates a unique server-verified API session token bound to this device.
     */
    public function login(Request $request): JsonResponse
    {
        // If accessed via GET without credentials (browser quick check), return friendly endpoint documentation
        if ($request->isMethod('GET') && (!$request->filled('email') || !$request->filled('password'))) {
            return response()->json([
                'status' => true,
                'message' => 'VoterApp Mobile Authentication API Endpoint.',
                'usage' => 'Send a POST request (or query params for testing) with email, password, and device_uid to authenticate.',
                'endpoints' => [
                    'POST /api/v1/auth/login',
                    'POST /v1/auth/login',
                ],
                'required_fields' => [
                    'email' => 'string (Candidate account login email)',
                    'password' => 'string (Candidate account password)',
                    'device_uid' => 'string (Unique device identifier UUID/Android ID)',
                ],
                'optional_fields' => [
                    'device_name' => 'string (e.g. Samsung Galaxy A54)',
                    'platform' => 'string (android or ios)',
                    'app_version' => 'string (e.g. 1.0.0)',
                ],
                'sample_post_payload' => [
                    'email' => 'usman@gmail.com',
                    'password' => 'yourpassword',
                    'device_uid' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
                    'device_name' => 'Infinix Hot 30',
                    'platform' => 'android',
                ],
            ]);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_uid' => 'required|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'platform' => 'nullable|string|in:android,ios',
            'app_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::with(['uc.tehsil.district', 'uc.nationalAssembly', 'uc.provincialAssembly'])
            ->where('email', $request->email)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if ($user->role === 'candidate' && $user->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'This candidate account is currently suspended. Please contact the administrator.',
            ], 403);
        }

        if ($user->role === 'candidate' && $user->expires_at && $user->expires_at->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'This candidate account subscription expired on ' . $user->expires_at->format('Y-m-d') . '. Access restricted.',
            ], 403);
        }

        if ($user->role === 'candidate' && empty($user->candidate_code)) {
            $user->candidate_code = \App\Models\User::generateUniqueCandidateCode($user->party_name);
            $user->save();
        }

        $deviceUid = trim($request->device_uid);

        // Check existing device registration
        $device = CandidateDevice::where('user_id', $user->id)
            ->where('device_uid', $deviceUid)
            ->first();

        if ($device && $device->is_revoked) {
            return response()->json([
                'status' => false,
                'is_revoked' => true,
                'message' => 'This device access has been revoked/blocked by the administrator.',
            ], 403);
        }

        if (!$device) {
            $device = new CandidateDevice([
                'user_id' => $user->id,
                'device_uid' => $deviceUid,
            ]);
        }

        // Generate cryptographically secure verifiable session token
        $token = 'vp_' . bin2hex(random_bytes(32));

        // Update device telemetry and token
        $device->api_token = $token;
        $device->device_name = $request->device_name ?: ($device->device_name ?: 'Mobile Device');
        $device->platform = $request->platform ?: ($device->platform ?: 'android');
        $device->app_version = $request->app_version ?: ($device->app_version ?: '1.0.0');
        $device->fcm_token = $request->fcm_token ?: $device->fcm_token;
        $device->ip_address = $request->ip();
        $device->last_active_at = Carbon::now();
        $device->is_revoked = false;
        $device->save();

        $activeDevicesCount = CandidateDevice::where('user_id', $user->id)->where('is_revoked', false)->count();

        $branding = [
            'party_name' => $user->party_name ?: ($user->is_independent ? 'Independent / Azad' : null),
            'is_independent' => (bool) $user->is_independent,
            'candidate_symbol' => $user->candidate_symbol,
            'party_logo_url' => $user->party_logo_url,
            'candidate_image_url' => $user->candidate_image_url,
            'candidate_symbol_image_url' => $user->candidate_symbol_image_url,
        ];

        return response()->json([
            'status' => true,
            'message' => 'Login successful. Device authorized.',
            'token' => $token,
            'candidate' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'candidate_code' => $user->candidate_code,
                'max_devices' => null,
                'active_devices' => $activeDevicesCount,
            ],
            'branding' => $branding,
            'uc' => $user->uc ? [
                'id' => $user->uc->id,
                'name' => $user->uc->name,
                'tehsil' => $user->uc->tehsil?->name,
                'district' => $user->uc->tehsil?->district?->name,
                'national_assembly' => $user->uc->nationalAssembly ? [
                    'id' => $user->uc->nationalAssembly->id,
                    'code' => $user->uc->nationalAssembly->code,
                    'name' => $user->uc->nationalAssembly->name,
                ] : null,
                'provincial_assembly' => $user->uc->provincialAssembly ? [
                    'id' => $user->uc->provincialAssembly->id,
                    'code' => $user->uc->provincialAssembly->code,
                    'name' => $user->uc->provincialAssembly->name,
                ] : null,
            ] : null,
            'server_time' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Check Device authorization status (Verify not revoked).
     */
    public function checkDevice(Request $request): JsonResponse
    {
        $device = $request->attributes->get('candidate_device');

        if (!$device) {
            $deviceUid = $request->header('X-Device-UID') ?? $request->query('device_uid');
            if ($deviceUid) {
                $device = CandidateDevice::with('user.uc')->where('device_uid', $deviceUid)->first();
            }
        }

        if (!$device) {
            return response()->json(['status' => false, 'message' => 'Device not registered.'], 404);
        }

        if ($device->is_revoked || ($device->user && $device->user->status !== 'active')) {
            return response()->json([
                'status' => false,
                'is_authorized' => false,
                'message' => 'Device or account has been revoked/suspended.',
            ], 403);
        }

        $device->last_active_at = Carbon::now();
        $device->ip_address = $request->ip();
        $device->save();

        $user = $device->user;
        $branding = $user ? [
            'party_name' => $user->party_name ?: ($user->is_independent ? 'Independent / Azad' : null),
            'is_independent' => (bool) $user->is_independent,
            'candidate_symbol' => $user->candidate_symbol,
            'party_logo_url' => $user->party_logo_url,
            'candidate_image_url' => $user->candidate_image_url,
            'candidate_symbol_image_url' => $user->candidate_symbol_image_url,
        ] : null;

        return response()->json([
            'status' => true,
            'is_authorized' => true,
            'device_name' => $device->device_name,
            'last_active_at' => $device->last_active_at->toIso8601String(),
            'branding' => $branding,
        ]);
    }

    /**
     * Download complete UC data payload for 100% Offline SQLite database cache.
     * Strictly scopes data download to the authenticated candidate's assigned UC.
     */
    public function downloadUcData(Request $request, ?UC $uc = null): JsonResponse
    {
        $candidate = $request->user();

        if (!$candidate || !$candidate->uc_id) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: No Union Council (UC) is assigned to this candidate account.',
            ], 403);
        }

        $assignedUcId = (int) $candidate->uc_id;

        // Security check: If client passes a UC ID in the URL or query string, verify it matches assigned UC
        $requestedUcId = $uc?->id ?? $request->query('uc_id');
        if ($requestedUcId && (int) $requestedUcId !== $assignedUcId) {
            return response()->json([
                'status' => false,
                'message' => "Forbidden: You are only authorized to access your assigned Union Council (UC ID: {$assignedUcId}). Cross-UC access is strictly denied.",
            ], 403);
        }

        $targetUc = UC::with(['tehsil.district', 'nationalAssembly', 'provincialAssembly'])->find($assignedUcId);

        if (!$targetUc) {
            return response()->json([
                'status' => false,
                'message' => 'Assigned Union Council not found in database.',
            ], 404);
        }

        $brandingPayload = [
            'candidate_name' => $candidate->name,
            'party_name' => $candidate->party_name ?: ($candidate->is_independent ? 'Independent / Azad' : null),
            'is_independent' => (bool) $candidate->is_independent,
            'candidate_symbol' => $candidate->candidate_symbol,
            'party_logo_url' => $candidate->party_logo_url,
            'candidate_image_url' => $candidate->candidate_image_url,
            'candidate_symbol_image_url' => $candidate->candidate_symbol_image_url,
        ];

        // Fetch Block Codes strictly for this candidate's UC
        $blockCodes = BlockCode::where('uc_id', $targetUc->id)
            ->orderBy('code')
            ->get(['id', 'code', 'area_name', 'area_name_ur', 'population']);

        // Fetch Polling Stations strictly for this candidate's UC
        $pollingStations = PollingStation::where('uc_id', $targetUc->id)
            ->withCount('voters')
            ->orderByRaw('CAST(station_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get(['id', 'block_code_id', 'station_no', 'name', 'gender', 'address', 'male_booths', 'female_booths', 'total_booths']);

        $pollingStationsPayload = $pollingStations->map(fn ($ps) => [
            'id' => $ps->id,
            'station_no' => $ps->station_no,
            'block_code_id' => $ps->block_code_id,
            'name' => $ps->name,
            'gender' => $ps->gender,
            'gender_ur' => $ps->gender_label_ur,
            'address' => $ps->address,
            'male_booths' => $ps->male_booths,
            'female_booths' => $ps->female_booths,
            'total_booths' => $ps->total_booths,
            'total_voters' => $ps->voters_count ?? 0,
        ]);

        // Fetch all Voters strictly for this candidate's UC
        $voters = Voter::where('uc_id', $targetUc->id)
            ->with(['blockCode:id,code,area_name,area_name_ur', 'pollingStation:id,station_no,name,gender,address'])
            ->orderBy('gharana_no')
            ->orderBy('silsala_no')
            ->get();

        $votersPayload = $voters->map(fn ($v) => [
            'id' => $v->id,
            'silsala_no' => $v->silsala_no,
            'gharana_no' => $v->gharana_no,
            'name' => $v->name,
            'father_name' => $v->father_name,
            'cnic' => $v->cnic,
            'formatted_cnic' => $v->formatted_cnic,
            'age' => $v->age,
            'gender' => $v->gender,
            'gender_ur' => $v->gender_label_ur,
            'address' => $v->address,
            'block_code_id' => $v->block_code_id,
            'block_code' => $v->blockCode?->code,
            'area_name' => $v->blockCode?->area_name,
            'area_name_ur' => $v->blockCode?->area_name_ur,
            'polling_station_id' => $v->polling_station_id,
            'polling_station_name' => $v->pollingStation?->name,
            'polling_station_gender' => $v->pollingStation?->gender,
            'polling_station_no' => $v->pollingStation?->station_no,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'UC Dataset downloaded successfully.',
            'uc' => [
                'id' => $targetUc->id,
                'uc_no' => $targetUc->uc_no,
                'name' => $targetUc->name,
                'name_ur' => $targetUc->name_ur,
                'tehsil' => $targetUc->tehsil?->name,
                'district' => $targetUc->tehsil?->district?->name,
                'national_assembly' => $targetUc->nationalAssembly?->code,
                'provincial_assembly' => $targetUc->provincialAssembly?->code,
            ],
            'branding' => $brandingPayload,
            'counts' => [
                'total_voters' => $voters->count(),
                'total_blocks' => $blockCodes->count(),
                'total_polling_stations' => $pollingStations->count(),
            ],
            'block_codes' => $blockCodes,
            'polling_stations' => $pollingStationsPayload,
            'voters' => $votersPayload,
            'data' => [
                'block_codes' => $blockCodes,
                'polling_stations' => $pollingStationsPayload,
                'voters' => $votersPayload,
            ],
            'dataset' => [
                'block_codes' => $blockCodes,
                'polling_stations' => $pollingStationsPayload,
                'voters' => $votersPayload,
            ],
            'generated_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Silent Background Sync for Search Analytics.
     * Enforces authenticated candidate user, assigned UC, and device.
     */
    public function syncSearches(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'searches' => 'nullable|array',
            'searches.*.query_type' => 'nullable|string|in:cnic,name,gharana,silsala,general',
            'searches.*.results_count' => 'nullable|integer|min:0',
            'searches.*.searched_at' => 'nullable|date',
            'batch_count' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $candidate = $request->user();
        $device = $request->attributes->get('candidate_device');

        $userId = $candidate->id;
        $ucId = $candidate->uc_id;
        $deviceUid = $device ? $device->device_uid : ($request->input('device_uid') ?? 'unknown');

        $additionalCount = 0;
        $cnicAdd = 0;
        $nameAdd = 0;
        $gharanaAdd = 0;
        $silsalaAdd = 0;
        $lastQueryType = 'general';

        if ($request->has('searches') && is_array($request->searches) && count($request->searches) > 0) {
            foreach ($request->searches as $item) {
                $qType = strtolower(trim($item['query_type'] ?? 'cnic'));
                $rCount = (int) ($item['results_count'] ?? 1);
                if ($rCount <= 0) {
                    $rCount = 1;
                }

                $additionalCount += $rCount;
                $lastQueryType = $qType;

                if (str_contains($qType, 'cnic')) {
                    $cnicAdd += $rCount;
                } elseif (str_contains($qType, 'name')) {
                    $nameAdd += $rCount;
                } elseif (str_contains($qType, 'gharana')) {
                    $gharanaAdd += $rCount;
                } elseif (str_contains($qType, 'silsala')) {
                    $silsalaAdd += $rCount;
                } else {
                    $cnicAdd += $rCount;
                }
            }
        } elseif ($request->batch_count) {
            $bCount = (int) $request->batch_count;
            if ($bCount > 0) {
                $additionalCount = $bCount;
                $lastQueryType = 'general';
                $cnicAdd = $bCount;
            }
        }

        if ($additionalCount > 0) {
            // Keep exactly one row per device/candidate and increment the counters
            $log = SearchLog::firstOrNew([
                'user_id' => $userId,
                'device_uid' => $deviceUid,
            ]);

            $log->uc_id = $ucId;
            $log->query_type = $lastQueryType;
            $log->results_count = ((int) $log->results_count) + $additionalCount;
            $log->cnic_count = ((int) $log->cnic_count) + $cnicAdd;
            $log->name_count = ((int) $log->name_count) + $nameAdd;
            $log->gharana_count = ((int) $log->gharana_count) + $gharanaAdd;
            $log->silsala_count = ((int) $log->silsala_count) + $silsalaAdd;
            $log->searched_at = Carbon::now();
            $log->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Search telemetry synced successfully.',
            'synced_count' => $additionalCount,
        ]);
    }

    /**
     * Silent Background Heartbeat.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $device = $request->attributes->get('candidate_device');

        if ($device) {
            $device->last_active_at = Carbon::now();
            $device->ip_address = $request->ip();
            if ($request->input('app_version')) {
                $device->app_version = $request->input('app_version');
            }
            $device->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Heartbeat acknowledged.',
            'server_time' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Online Live Voter Search Fallback.
     * Strictly scopes search to the authenticated candidate's assigned UC.
     */
    public function searchVoters(Request $request): JsonResponse
    {
        $candidate = $request->user();

        if (!$candidate || !$candidate->uc_id) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: No Union Council (UC) is assigned to this candidate account.',
            ], 403);
        }

        $assignedUcId = (int) $candidate->uc_id;

        // Security check: Reject cross-UC queries
        if ($request->filled('uc_id') && (int) $request->input('uc_id') !== $assignedUcId) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: You are only authorized to search voters within your assigned Union Council.',
            ], 403);
        }

        $query = trim((string) $request->input('q', ''));
        $by = $request->input('by', 'all');

        if ($query === '') {
            return response()->json([
                'status' => false,
                'message' => 'Search query is required.',
                'results' => [],
            ], 422);
        }

        $voterQuery = Voter::with(['uc.tehsil.district', 'blockCode', 'pollingStation'])
            ->where('uc_id', $assignedUcId);

        if ($by === 'cnic' || (preg_match('/^\d{5}/', $query) && strlen(preg_replace('/\D/', '', $query)) >= 5)) {
            $cleanCnic = Voter::normalizeCnic($query);
            $voterQuery->where('cnic', 'like', "%{$cleanCnic}%");
        } elseif ($by === 'gharana') {
            $voterQuery->where('gharana_no', $query);
        } elseif ($by === 'silsala') {
            $voterQuery->where('silsala_no', $query);
        } else {
            $cleanCnic = Voter::normalizeCnic($query);
            $voterQuery->where(function ($sub) use ($query, $cleanCnic) {
                if ($cleanCnic !== '') {
                    $sub->where('cnic', 'like', "%{$cleanCnic}%");
                }
                $sub->orWhere('name', 'like', "%{$query}%")
                    ->orWhere('father_name', 'like', "%{$query}%")
                    ->orWhere('gharana_no', $query);
            });
        }

        $results = $voterQuery->orderBy('gharana_no')->orderBy('silsala_no')->take(50)->get();

        return response()->json([
            'status' => true,
            'success' => true,
            'count' => $results->count(),
            'voters' => VoterResource::collection($results),
            'results' => VoterResource::collection($results),
            'data' => VoterResource::collection($results),
        ]);
    }

    // -------------------------------------------------------------
    // Legacy Endpoints Compatibility (Secured with Candidate Auth)
    // -------------------------------------------------------------

    public function ucs(Request $request): JsonResponse
    {
        $candidate = $request->user();
        $query = UC::with(['tehsil.district', 'nationalAssembly', 'provincialAssembly']);

        if ($candidate && $candidate->uc_id) {
            $query->where('id', $candidate->uc_id);
        }

        $ucs = $query->orderBy('name')->get();
        $formatted = $ucs->map(fn ($u) => [
            'id' => $u->id,
            'uc_no' => (string) ($u->uc_no ?? $u->id),
            'name' => $u->name,
            'tehsil' => $u->tehsil?->name,
            'district' => $u->tehsil?->district?->name,
            'national_assembly' => $u->nationalAssembly ? ($u->nationalAssembly->code . ' ' . $u->nationalAssembly->name) : null,
            'provincial_assembly' => $u->provincialAssembly ? ($u->provincialAssembly->code . ' ' . $u->provincialAssembly->name) : null,
        ]);

        return response()->json([
            'status' => true,
            'success' => true,
            'uc' => $formatted->first(),
            'ucs' => $formatted,
            'data' => $formatted,
        ]);
    }

    public function voters(Request $request, UC $uc)
    {
        $candidate = $request->user();

        if ($candidate && $candidate->uc_id && (int) $uc->id !== (int) $candidate->uc_id) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: You are only authorized to access voters for your assigned Union Council.',
            ], 403);
        }

        $by = $request->query('by');
        $q = trim((string) $request->query('q', ''));
        $with = ['uc.tehsil.district', 'blockCode', 'pollingStation'];

        if ($by === 'cnic' && $q !== '') {
            $voter = Voter::with($with)->where('uc_id', $uc->id)->byCnic($q)->first();
            if (!$voter) {
                return response()->json(['status' => true, 'voter' => null, 'family' => []]);
            }
            $family = Voter::with($with)->where('uc_id', $uc->id)->where('gharana_no', $voter->gharana_no)->orderBy('silsala_no')->get();
            return response()->json([
                'status' => true,
                'voter' => new VoterResource($voter),
                'family' => VoterResource::collection($family),
            ]);
        }

        $query = Voter::with($with)->where('uc_id', $uc->id);
        if ($request->filled('block_code')) {
            $bCode = trim($request->query('block_code'));
            $query->whereHas('blockCode', fn($q) => $q->where('code', $bCode));
        }

        $voters = $query->orderBy('gharana_no')->orderBy('silsala_no')->paginate(50);
        return response()->json([
            'status' => true,
            'success' => true,
            'current_page' => $voters->currentPage(),
            'per_page' => $voters->perPage(),
            'total' => $voters->total(),
            'data' => VoterResource::collection($voters),
        ]);
    }

    public function blockCodes(Request $request, UC $uc): JsonResponse
    {
        $candidate = $request->user();

        if ($candidate && $candidate->uc_id && (int) $uc->id !== (int) $candidate->uc_id) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: Cross-UC access denied.',
            ], 403);
        }

        $blocks = BlockCode::where('uc_id', $uc->id)->orderBy('code')->get();
        $formatted = $blocks->map(fn($b) => [
            'id' => $b->id,
            'block_code' => $b->code,
            'code' => $b->code,
            'area_name' => $b->area_name,
            'area_name_urdu' => $b->area_name_ur,
            'total_voters' => $b->population ?? 0,
        ]);

        return response()->json([
            'status' => true,
            'success' => true,
            'total_blocks' => $blocks->count(),
            'block_codes' => $formatted,
            'data' => $formatted,
        ]);
    }

    public function pollingStations(Request $request, UC $uc): JsonResponse
    {
        $candidate = $request->user();

        if ($candidate && $candidate->uc_id && (int) $uc->id !== (int) $candidate->uc_id) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden: Cross-UC access denied.',
            ], 403);
        }

        $stations = PollingStation::where('uc_id', $uc->id)
            ->orderByRaw('CAST(station_no AS UNSIGNED) ASC')
            ->orderBy('name')
            ->get();

        $formatted = $stations->map(fn ($ps) => [
            'id' => $ps->id,
            'station_no' => $ps->station_no,
            'name' => $ps->name,
            'gender' => $ps->gender,
            'gender_ur' => $ps->gender_label_ur,
            'address' => $ps->address,
            'male_booths' => $ps->male_booths,
            'female_booths' => $ps->female_booths,
            'total_booths' => $ps->total_booths,
        ]);

        return response()->json([
            'status' => true,
            'success' => true,
            'total_stations' => $stations->count(),
            'polling_stations' => $formatted,
            'data' => $formatted,
        ]);
    }
}
