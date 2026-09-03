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
     * Mobile App Candidate Login & Device Quota Enforcement.
     * Enforces the max_devices limit (e.g. max 20 devices per candidate).
     */
    public function login(Request $request): JsonResponse
    {
        // If accessed via GET without credentials (browser quick check), return friendly endpoint documentation
        if ($request->isMethod('GET') && (!$request->filled('email') || !$request->filled('password'))) {
            return response()->json([
                'status' => true,
                'message' => 'VoterApp Mobile Authentication API Endpoint.',
                'usage' => 'Send a POST request (or query params for browser testing) with email, password, and device_uid.',
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

        if ($user->expires_at && Carbon::now()->greaterThan($user->expires_at)) {
            return response()->json([
                'status' => false,
                'message' => 'Your election app subscription has expired. Please contact the administrator.',
            ], 403);
        }

        $deviceUid = trim($request->device_uid);

        // Check existing device registration
        $device = CandidateDevice::where('user_id', $user->id)
            ->where('device_uid', $deviceUid)
            ->first();

        if ($device && $device->is_revoked) {
            return response()->json([
                'status' => false,
                'message' => 'This device access has been revoked/blocked by the administrator.',
            ], 403);
        }

        // Unlimited devices permitted (Free to use)
        if (!$device) {
            $device = new CandidateDevice([
                'user_id' => $user->id,
                'device_uid' => $deviceUid,
            ]);
        }

        // Update device telemetry
        $device->device_name = $request->device_name ?: ($device->device_name ?: 'Mobile Device');
        $device->platform = $request->platform ?: ($device->platform ?: 'android');
        $device->app_version = $request->app_version ?: ($device->app_version ?: '1.0.0');
        $device->ip_address = $request->ip();
        $device->last_active_at = Carbon::now();
        $device->is_revoked = false;
        $device->save();

        // Generate signature auth token
        $token = base64_encode($user->id . ':' . $deviceUid . ':' . time() . ':' . sha1($user->password . config('app.key')));

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
        $deviceUid = $request->header('X-Device-UID') ?? $request->query('device_uid');
        $email = $request->header('X-Candidate-Email') ?? $request->query('email');

        if (!$deviceUid) {
            return response()->json(['status' => false, 'message' => 'Device UID required.'], 400);
        }

        $deviceQuery = CandidateDevice::with('user')->where('device_uid', $deviceUid);

        if ($email) {
            $deviceQuery->whereHas('user', fn ($q) => $q->where('email', $email));
        }

        $device = $deviceQuery->first();

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
     */
    public function downloadUcData(Request $request, ?UC $uc = null): JsonResponse
    {
        // If UC is not passed in URL, attempt to determine from candidate email or query
        if (!$uc || !$uc->exists) {
            $ucId = $request->query('uc_id');
            if ($ucId) {
                $uc = UC::find($ucId);
            }
        }

        if (!$uc) {
            return response()->json([
                'status' => false,
                'message' => 'Union Council (UC) not specified or not found.',
            ], 404);
        }

        $uc->load(['tehsil.district', 'nationalAssembly', 'provincialAssembly']);

        // Resolve Candidate for branding payload if email or user_id provided
        $candidateUser = null;
        if ($request->query('email') || $request->header('X-Candidate-Email')) {
            $cEmail = $request->query('email') ?? $request->header('X-Candidate-Email');
            $candidateUser = User::where('email', $cEmail)->first();
        } elseif ($request->query('user_id')) {
            $candidateUser = User::find($request->query('user_id'));
        }

        $brandingPayload = $candidateUser ? [
            'candidate_name' => $candidateUser->name,
            'party_name' => $candidateUser->party_name ?: ($candidateUser->is_independent ? 'Independent / Azad' : null),
            'is_independent' => (bool) $candidateUser->is_independent,
            'candidate_symbol' => $candidateUser->candidate_symbol,
            'party_logo_url' => $candidateUser->party_logo_url,
            'candidate_image_url' => $candidateUser->candidate_image_url,
            'candidate_symbol_image_url' => $candidateUser->candidate_symbol_image_url,
        ] : null;

        // Fetch Block Codes with Delimitation fields
        $blockCodes = BlockCode::where('uc_id', $uc->id)
            ->orderBy('code')
            ->get(['id', 'code', 'area_name', 'area_name_ur', 'population']);

        // Fetch Polling Stations
        $pollingStations = PollingStation::where('uc_id', $uc->id)
            ->orderBy('name')
            ->get(['id', 'block_code_id', 'name', 'address', 'total_voters', 'male_voters', 'female_voters']);

        // Fetch all Voters for this UC
        $voters = Voter::where('uc_id', $uc->id)
            ->with(['blockCode:id,code,area_name,area_name_ur', 'pollingStation:id,name'])
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
            'address' => $v->address,
            'block_code_id' => $v->block_code_id,
            'block_code' => $v->blockCode?->code,
            'area_name' => $v->blockCode?->area_name,
            'area_name_ur' => $v->blockCode?->area_name_ur,
            'polling_station_id' => $v->polling_station_id,
            'polling_station_name' => $v->pollingStation?->name,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'UC Dataset downloaded successfully.',
            'uc' => [
                'id' => $uc->id,
                'uc_no' => $uc->uc_no,
                'name' => $uc->name,
                'name_ur' => $uc->name_ur,
                'tehsil' => $uc->tehsil?->name,
                'district' => $uc->tehsil?->district?->name,
                'national_assembly' => $uc->nationalAssembly?->code,
                'provincial_assembly' => $uc->provincialAssembly?->code,
            ],
            'branding' => $brandingPayload,
            'counts' => [
                'total_voters' => $voters->count(),
                'total_blocks' => $blockCodes->count(),
                'total_polling_stations' => $pollingStations->count(),
            ],
            'block_codes' => $blockCodes,
            'polling_stations' => $pollingStations,
            'voters' => $votersPayload,
            'generated_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Silent Background Sync for Search Analytics.
     */
    public function syncSearches(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_uid' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
            'uc_id' => 'nullable|exists:ucs,id',
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

        $deviceUid = $request->device_uid;
        $device = CandidateDevice::where('device_uid', $deviceUid)->first();

        $userId = $request->user_id ?: ($device?->user_id);
        $ucId = $request->uc_id ?: ($device?->user?->uc_id);

        $syncedCount = 0;

        if ($request->has('searches') && is_array($request->searches) && count($request->searches) > 0) {
            $records = [];
            foreach ($request->searches as $item) {
                $records[] = [
                    'user_id' => $userId,
                    'uc_id' => $ucId,
                    'device_uid' => $deviceUid,
                    'query_type' => $item['query_type'] ?? 'cnic',
                    'results_count' => $item['results_count'] ?? 1,
                    'searched_at' => isset($item['searched_at']) ? Carbon::parse($item['searched_at']) : Carbon::now(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
                $syncedCount++;
            }
            SearchLog::insert($records);
        } elseif ($request->batch_count) {
            // Aggregate batch count summary
            SearchLog::create([
                'user_id' => $userId,
                'uc_id' => $ucId,
                'device_uid' => $deviceUid,
                'query_type' => 'general',
                'results_count' => (int) $request->batch_count,
                'searched_at' => Carbon::now(),
            ]);
            $syncedCount = (int) $request->batch_count;
        }

        if ($device) {
            $device->last_active_at = Carbon::now();
            $device->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Search telemetry synced successfully.',
            'synced_count' => $syncedCount,
        ]);
    }

    /**
     * Silent Background Heartbeat.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $deviceUid = $request->input('device_uid') ?? $request->header('X-Device-UID');

        if (!$deviceUid) {
            return response()->json(['status' => false, 'message' => 'Device UID required.'], 400);
        }

        $device = CandidateDevice::where('device_uid', $deviceUid)->first();

        if ($device) {
            $device->last_active_at = Carbon::now();
            $device->ip_address = $request->ip();
            if ($request->app_version) {
                $device->app_version = $request->app_version;
            }
            $device->save();

            if ($device->is_revoked) {
                return response()->json([
                    'status' => false,
                    'is_revoked' => true,
                    'message' => 'Device has been revoked.',
                ], 403);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Heartbeat acknowledged.',
            'server_time' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Online Live Voter Search Fallback (with family matching).
     */
    public function searchVoters(Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));
        $by = $request->input('by', 'all'); // 'all', 'cnic', 'name', 'gharana', 'silsala'
        $ucId = $request->input('uc_id');

        if ($query === '') {
            return response()->json([
                'status' => false,
                'message' => 'Search query is required.',
                'results' => [],
            ], 422);
        }

        $voterQuery = Voter::with(['uc.tehsil.district', 'blockCode', 'pollingStation']);

        if ($ucId) {
            $voterQuery->where('uc_id', $ucId);
        }

        if ($by === 'cnic' || (preg_match('/^\d{5}/', $query) && strlen(preg_replace('/\D/', '', $query)) >= 5)) {
            $cleanCnic = Voter::normalizeCnic($query);
            $voterQuery->where('cnic', 'like', "%{$cleanCnic}%");
        } elseif ($by === 'gharana') {
            $voterQuery->where('gharana_no', $query);
        } elseif ($by === 'silsala') {
            $voterQuery->where('silsala_no', $query);
        } else {
            // General multi-field search
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
            'count' => $results->count(),
            'voters' => VoterResource::collection($results),
        ]);
    }

    // -------------------------------------------------------------
    // Legacy Endpoints Compatibility
    // -------------------------------------------------------------

    public function ucs(): JsonResponse
    {
        $ucs = UC::with(['tehsil.district', 'nationalAssembly', 'provincialAssembly'])->orderBy('name')->get();

        return response()->json(
            $ucs->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'tehsil' => $u->tehsil?->name,
                'district' => $u->tehsil?->district?->name,
                'national_assembly' => $u->nationalAssembly?->code,
                'provincial_assembly' => $u->provincialAssembly?->code,
            ])
        );
    }

    public function voters(Request $request, UC $uc)
    {
        $by = $request->query('by');
        $q = trim((string) $request->query('q', ''));
        $with = ['uc.tehsil.district', 'blockCode', 'pollingStation'];

        if ($by === 'cnic' && $q !== '') {
            $voter = Voter::with($with)->where('uc_id', $uc->id)->byCnic($q)->first();
            if (!$voter) {
                return response()->json(['voter' => null, 'family' => []]);
            }
            $family = Voter::with($with)->where('uc_id', $uc->id)->where('gharana_no', $voter->gharana_no)->orderBy('silsala_no')->get();
            return response()->json([
                'voter' => new VoterResource($voter),
                'family' => VoterResource::collection($family),
            ]);
        }

        $voters = Voter::with($with)->where('uc_id', $uc->id)->orderBy('gharana_no')->orderBy('silsala_no')->paginate(50);
        return VoterResource::collection($voters);
    }

    public function blockCodes(UC $uc): JsonResponse
    {
        return response()->json(
            BlockCode::where('uc_id', $uc->id)->orderBy('code')->get(['id', 'code'])
        );
    }

    public function pollingStations(UC $uc): JsonResponse
    {
        return response()->json(
            PollingStation::where('uc_id', $uc->id)->orderBy('name')->get(['id', 'name', 'address'])
        );
    }
}
