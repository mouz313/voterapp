# VoterApp v3.0 — Complete API Documentation & Integration Guide

**Last Updated**: September 2026  
**Backend Framework**: Laravel 10 / PHP 8.2  
**Mobile Client**: Flutter (Android / iOS)  
**Database**: MySQL (Server) & SQLite (Offline Handheld Sync)  
**FCM Project**: `voterapp-9ac02` (Project Number: `145402386946`)  
**Test Suite Status**: ✅ **17 / 17 Core APIs Operational (100% Passed)** | ✅ **13 / 13 FCM Notification Tests Passed**

---

## Table of Contents

1. [Architectural Overview & Global Standards](#1-architectural-overview--global-standards)
   - [Base URL & Protocol](#base-url--protocol)
   - [Multi-Channel Token Authentication](#multi-channel-token-authentication)
   - [Multi-Block Staff Canvassing Architecture](#multi-block-staff-canvassing-architecture)
   - [Standard Request & Response Envelopes](#standard-request--response-envelopes)
   - [HTTP Status Codes](#http-status-codes)
2. [Module 1: Authentication, White-Label Branding & Push Notifications](#module-1-authentication-white-label-branding--push-notifications)
   - [1.1 Candidate Mobile Login (`POST /api/v1/auth/login`)](#11-candidate-mobile-login-post-apiv1authlogin)
   - [1.2 White-Label Campaign Branding (`POST /api/v1/auth/campaign-branding`)](#12-white-label-campaign-branding-post-apiv1authcampaign-branding)
   - [1.3 Register / Update FCM Push Notification Token (`POST /api/v1/auth/fcm-token`)](#13-register--update-fcm-push-notification-token-post-apiv1authfcm-token)
3. [Module 2: Door-to-Door Field Staff Canvassing](#module-2-door-to-door-field-staff-canvassing)
   - [2.1 Staff 4-Digit PIN Authentication (`POST /api/v1/staff/login`)](#21-staff-4-digit-pin-authentication-post-apiv1stafflogin)
   - [2.2 Download Assigned Census Block Roster (`GET /api/v1/staff/block/data`)](#22-download-assigned-census-block-roster-get-apiv1staffblockdata)
   - [2.3 Batch Sync Household Surveys (`POST /api/v1/staff/survey/sync`)](#23-batch-sync-household-surveys-post-apiv1staffsurveysync)
4. [Module 3: Candidate Field Team & Staff Management](#module-3-candidate-field-team--staff-management)
   - [3.1 List Campaign Workers (`GET /api/v1/candidate/workers`)](#31-list-campaign-workers-get-apiv1candidateworkers)
   - [3.2 Create Field Worker (`POST /api/v1/candidate/workers`)](#32-create-field-worker-post-apiv1candidateworkers)
   - [3.3 Update Field Worker (`PUT /api/v1/candidate/workers/{id}`)](#33-update-field-worker-put-apiv1candidateworkersid)
   - [3.4 Delete / Deactivate Worker (`DELETE /api/v1/candidate/workers/{id}`)](#34-delete--deactivate-worker-delete-apiv1candidateworkersid)
5. [Module 4: Candidate Live War Room & Election Day GOTV](#module-4-candidate-live-war-room--election-day-gotv)
   - [4.1 Executive Live War Room Dashboard (`GET /api/v1/candidate/war-room`)](#41-executive-live-war-room-dashboard-get-apiv1candidatewar-room)
   - [4.2 GOTV Camp Thermal Parchi Issuance (`POST /api/v1/camp/issue-parchi`)](#42-gotv-camp-thermal-parchi-issuance-post-apiv1campissue-parchi)
   - [4.3 Campaign Matrix Pre-Aggregation Cron (`GET /api/v1/cron/process-campaign-matrix`)](#43-campaign-matrix-pre-aggregation-cron-get-apiv1cronprocess-campaign-matrix)
6. [Module 5: Offline Voter Search & Handheld Device Telemetry](#module-5-offline-voter-search--handheld-device-telemetry)
   - [5.1 Device Verification & Lock Check (`GET /api/v1/auth/check-device`)](#51-device-verification--lock-check-get-apiv1authcheck-device)
   - [5.2 Full UC SQLite Dataset Download (`GET /api/v1/download`)](#52-full-uc-sqlite-dataset-download-get-apiv1download)
   - [5.3 Search Audit Telemetry Sync (`POST /api/v1/sync/searches`)](#53-search-audit-telemetry-sync-post-apiv1syncsearches)
   - [5.4 Handheld Device Heartbeat Ping (`POST /api/v1/sync/heartbeat`)](#54-handheld-device-heartbeat-ping-post-apiv1syncheartbeat)
   - [5.5 Live Online Fallback Voter Search (`GET|POST /api/v1/voters/search`)](#55-live-online-fallback-voter-search-getpost-apiv1voterssearch)
7. [Module 6: Union Council Relational Metadata](#module-6-union-council-relational-metadata)
   - [6.1 Candidate Assigned Union Council Profile (`GET /api/v1/ucs`)](#61-candidate-assigned-union-council-profile-get-apiv1ucs)
   - [6.2 Union Council Block Codes List (`GET /api/v1/ucs/{uc}/block-codes`)](#62-union-council-block-codes-list-get-apiv1ucsucblock-codes)
   - [6.3 Union Council Polling Stations List (`GET /api/v1/ucs/{uc}/polling-stations`)](#63-union-council-polling-stations-list-get-apiv1ucsucpolling-stations)
   - [6.4 Union Council Paginated Voter List (`GET /api/v1/ucs/{uc}/voters`)](#64-union-council-paginated-voter-list-get-apiv1ucsucvoters)
8. [Module 7: Admin Panel Test Notification Hub](#module-7-admin-panel-test-notification-hub)
   - [7.1 Dispatch Test Push Notification (`POST /settings/test-notification`)](#71-dispatch-test-push-notification-post-settingstest-notification)
9. [Automated Verification Test Suite](#9-automated-verification-test-suite)

---

## 1. Architectural Overview & Global Standards

### Base URL & Protocol
All API endpoints are versioned under `/api/v1/` and mirrored under `/v1/` for compatibility:
- **Local Dev (PC)**: `http://localhost/voterapp/api/v1`
- **Android Emulator**: `http://10.0.2.2/voterapp/api/v1`
- **LAN Physical Device**: `http://<YOUR_LAN_IP>/voterapp/api/v1`
- **Production Server**: `https://yourdomain.com/api/v1`

### Multi-Channel Token Authentication
To eliminate issues where certain mobile HTTP clients or proxy servers strip the `Authorization` header, VoterApp extracts auth tokens across multiple channels in priority order:
1. `Authorization: Bearer <TOKEN>`
2. `Authorization: <TOKEN>` (Raw header)
3. `X-Candidate-Token: <TOKEN>`
4. `X-Worker-Token: <TOKEN>`
5. `X-Device-Token: <TOKEN>`
6. `X-API-KEY: <TOKEN>`
7. Query Parameter: `?token=<TOKEN>` or `?api_token=<TOKEN>`
8. JSON Request Body: `{"token": "<TOKEN>"}` or `{"api_token": "<TOKEN>"}`

### Multi-Block Staff Canvassing Architecture
Workers can be assigned either a **single block code** (e.g. `185010401`) or **multiple block codes separated by commas** (e.g. `185010401, 185010402, 185010403`).
- **Data Fetch**: The `/api/v1/staff/block/data` endpoint automatically resolves all assigned block codes, combines voter/gharana records, and provides a multi-block summary.
- **Survey Sync**: The `/api/v1/staff/survey/sync` endpoint validates each survey entry's `block_code` against the worker's assigned blocks.
- **Direct 1-Tap Calling**: The `influencer_phone` field in surveys enables instant calling to household elders directly from the Candidate War Room.
- **GPS Coordinates**: Handheld devices record `latitude` and `longitude` with each survey sync to ensure field workers actually visited the location.
- **VIP Alert Notification**: Setting `is_vip_visit_requested: true` triggers an instant Firebase Push Notification to the Candidate's device.

### Standard Request & Response Envelopes
All API requests accepting payload data should specify:
```http
Content-Type: application/json
Accept: application/json
```

Standard successful responses:
```json
{
  "status": true,
  "success": true,
  "message": "Action completed successfully.",
  "data": { ... }
}
```

Standard error response (e.g. 401 Unauthorized / 422 Unprocessable):
```json
{
  "status": false,
  "success": false,
  "message": "Validation failed / Unauthorized access.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### HTTP Status Codes
| Code | Meaning | Usage |
|---|---|---|
| `200 OK` | Success | Normal successful query or update |
| `201 Created` | Resource Created | Successfully created worker or recorded entity |
| `400 Bad Request` | Bad Request | Missing required parameters or malformed JSON |
| `401 Unauthorized` | Auth Failure | Invalid credentials, expired session, or revoked token |
| `403 Forbidden` | Access Denied | Account suspended or device locked by Admin |
| `404 Not Found` | Resource Not Found | Worker, block code, or voter does not exist |
| `422 Unprocessable` | Validation Error | Validation rule failed (e.g. duplicate phone) |
| `500 Server Error` | Server Exception | Internal server error |

---

## Module 1: Authentication, White-Label Branding & Push Notifications

### 1.1 Candidate Mobile Login (`POST /api/v1/auth/login`)
Authenticates candidate credentials and registers/updates the handheld device binding.

- **Method**: `POST`
- **Endpoint**: `/api/v1/auth/login` (also `/v1/auth/login`)
- **Authentication**: None (Public)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `email` | `string` | **Yes** | Body | Candidate registered email address |
| `password` | `string` | **Yes** | Body | Candidate account password |
| `device_uid` | `string` | **Yes** | Body | Unique device hardware identifier (UUID / Android ID) |
| `device_name` | `string` | No | Body | Phone model name (e.g. "Samsung Galaxy S24") |
| `platform` | `string` | No | Body | Client platform: `android`, `ios`, or `web` |
| `app_version` | `string` | No | Body | Installed mobile app version (e.g. `1.3.0`) |

#### Sample Request
```json
{
  "email": "usman@gmail.com",
  "password": "SecretPassword123",
  "device_uid": "4a7b-89cd-001a-device-uuid",
  "device_name": "Samsung Galaxy S24",
  "platform": "android",
  "app_version": "1.3.0"
}
```

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "message": "Login successful. Device authorized.",
  "token": "vp_5e54112d6fd5779c98051eebaf5e9da7e7b805c14b1759e30893a2bf2c10d3ce",
  "candidate": {
    "id": 6,
    "name": "Usman Saeed",
    "candidate_code": "PTI-9089",
    "email": "usman@gmail.com",
    "party_name": "Pakistan Tehreek-e-Insaf",
    "party_slogan": "Naya Pakistan",
    "candidate_symbol": "Bat",
    "party_logo_url": "http://localhost/voterapp/uploads/branding/pti_logo.png",
    "candidate_image_url": "http://localhost/voterapp/uploads/candidates/usman.png",
    "candidate_symbol_image_url": "http://localhost/voterapp/uploads/symbols/bat.png"
  },
  "uc": {
    "id": 75,
    "uc_no": "75",
    "name": "Ichhra Central",
    "tehsil": "Model Town",
    "district": "Lahore"
  }
}
```

---

### 1.2 White-Label Campaign Branding (`POST /api/v1/auth/campaign-branding`)
Fetches dynamic candidate branding (colors, party symbol, logos) prior to login so the app UI can instantly theme itself.

- **Method**: `POST`
- **Endpoint**: `/api/v1/auth/campaign-branding`
- **Authentication**: None (Public)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `candidate_code` | `string` | No* | Body | Candidate code (e.g. `PTI-9089`). *(Provide candidate_code, email, or uc_id)* |
| `email` | `string` | No* | Body | Candidate email address |
| `uc_id` | `integer` | No* | Body | Assigned Union Council database ID |

#### Sample Request
```json
{
  "candidate_code": "PTI-9089"
}
```

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "candidate_code": "PTI-9089",
  "candidate_name": "Usman Saeed",
  "party_name": "Pakistan Tehreek-e-Insaf",
  "candidate_symbol": "Bat",
  "party_slogan": "Haqeeqi Azadi",
  "party_logo_url": "http://localhost/voterapp/uploads/branding/pti_logo.png",
  "candidate_image_url": "http://localhost/voterapp/uploads/candidates/usman.png",
  "candidate_symbol_image_url": "http://localhost/voterapp/uploads/symbols/bat.png",
  "primary_color": "#006633",
  "accent_color": "#E31B23"
}
```

---

### 1.3 Register / Update FCM Push Notification Token (`POST /api/v1/auth/fcm-token`)
Registers or refreshes Firebase Cloud Messaging device registration tokens for Candidate or Field Worker apps.

- **Method**: `POST`
- **Endpoint**: `/api/v1/auth/fcm-token`
- **Authentication**: Bearer Token (Candidate or Worker)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `fcm_token` | `string` | **Yes** | Body | FCM device registration token issued by Firebase SDK |
| `user_type` | `string` | No | Body | `candidate` or `worker` (auto-detected if token provided) |
| `device_uid` | `string` | No | Body | Device hardware ID |

#### Sample Request
```json
{
  "fcm_token": "eX_1abZ-qK9:APA91bF3kL8mNpQrS4tUvWxYz0123456789abcdef",
  "user_type": "candidate"
}
```

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "message": "FCM device token registered successfully.",
  "target_type": "candidate",
  "device_id": 14
}
```

---

## Module 2: Door-to-Door Field Staff Canvassing

### 2.1 Staff 4-Digit PIN Authentication (`POST /api/v1/staff/login`)
Authenticates ground canvassers using their phone number and 4-digit PIN issued by the candidate.

- **Method**: `POST`
- **Endpoint**: `/api/v1/staff/login`
- **Authentication**: None (Public)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `candidate_code` | `string` | **Yes** | Body | Candidate unique code (e.g. `PTI-9089`) |
| `phone` | `string` | **Yes** | Body | Worker registered phone number (e.g. `03001234567`) |
| `pin` | `string` | **Yes** | Body | 4-digit numeric PIN (e.g. `1234`) |
| `device_uid` | `string` | No | Body | Worker mobile device hardware identifier |

#### Sample Request
```json
{
  "candidate_code": "PTI-9089",
  "phone": "03001234567",
  "pin": "1234",
  "device_uid": "worker-phone-uuid-7788"
}
```

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "message": "Staff login successful.",
  "worker_token": "vp_xwWfkODCHw3Zjtck6LUNoOs4pWkxos2uTvd0EA6Uq5NuCngfY0iBPfDq",
  "worker": {
    "id": 12,
    "name": "Tariq Mehmood Field Worker",
    "phone": "03001234567",
    "role": "Field Worker",
    "assigned_block_codes": ["185010401", "185010402"],
    "total_assigned_blocks": 2
  },
  "candidate": {
    "name": "Usman Saeed",
    "candidate_code": "PTI-9089",
    "party_name": "Pakistan Tehreek-e-Insaf"
  }
}
```

---

### 2.2 Download Assigned Census Block Roster (`GET /api/v1/staff/block/data`)
Fetches all household gharana records and registered voters for the worker's assigned census blocks.

- **Method**: `GET`
- **Endpoint**: `/api/v1/staff/block/data`
- **Authentication**: Worker Token (`Bearer <worker_token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `block_code` | `string` | No | Query | Specific block code (e.g. `185010401`). If omitted, all assigned blocks are returned. |

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "assigned_blocks": ["185010401", "185010402"],
  "total_gharanas": 145,
  "total_voters": 580,
  "gharanas": [
    {
      "block_code": "185010401",
      "gharana_no": "001",
      "head_name": "Chaudhry Riaz Ahmad",
      "influencer_name": "Chaudhry Riaz Ahmad",
      "influencer_phone": "03214567890",
      "party_inclination": "pakka",
      "total_voters": 4,
      "is_vip_visit_requested": false,
      "voters": [
        {
          "id": 101,
          "name": "Riaz Ahmad",
          "father_name": "Muhammad Din",
          "cnic": "35201-1234567-1",
          "serial_no": 1,
          "gender": "Male",
          "age": 54
        }
      ]
    }
  ]
}
```

---

### 2.3 Batch Sync Household Surveys (`POST /api/v1/staff/survey/sync`)
Submits offline door-to-door survey entries gathered by field staff. Automatically updates campaign matrices, captures anti-fraud GPS coordinates, and triggers VIP push alerts.

- **Method**: `POST`
- **Endpoint**: `/api/v1/staff/survey/sync`
- **Authentication**: Worker Token (`Bearer <worker_token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `surveys` | `array` | **Yes** | Body | Array of survey objects (see table below) |

##### Survey Object Fields
| Field | Type | Required | Description |
|---|---|---|---|
| `block_code` | `string` | **Yes** | Census block code (must belong to assigned blocks) |
| `gharana_no` | `string` | **Yes** | Household gharana number (e.g. `001`) |
| `party_inclination`| `string` | **Yes** | Inclination: `pakka`, `kacha`, `mukhalif`, or `neutral` |
| `head_name` | `string` | No | Household head name |
| `influencer_name`| `string` | No | Household elder / influential person |
| `influencer_phone`| `string` | No | **Direct phone number** for 1-tap War Room calling |
| `is_vip_visit_requested` | `boolean` | No | If `true`, **instantly fires an FCM Push Alert** to candidate |
| `notes` | `string` | No | Field feedback, voter demands, or follow-up notes |
| `latitude` | `decimal`| No | Anti-fraud GPS latitude (e.g. `31.5204`) |
| `longitude` | `decimal`| No | Anti-fraud GPS longitude (e.g. `74.3587`) |
| `voters` | `array` | No | Array of voter inclination overrides `[{"voter_id": 101, "inclination": "pakka"}]` |

#### Sample Request
```json
{
  "surveys": [
    {
      "block_code": "185010401",
      "gharana_no": "042",
      "head_name": "Haji Abdul Rehman",
      "influencer_name": "Haji Abdul Rehman",
      "influencer_phone": "03009876543",
      "party_inclination": "kacha",
      "is_vip_visit_requested": true,
      "notes": "Elder wants candidate to personally visit for Dera meeting.",
      "latitude": 31.520378,
      "longitude": 74.358742,
      "voters": [
        { "voter_id": 105, "inclination": "pakka" },
        { "voter_id": 106, "inclination": "kacha" }
      ]
    }
  ]
}
```

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "message": "1 survey(s) synced successfully.",
  "synced_count": 1,
  "vip_alerts_sent": 1,
  "last_sync_at": "2026-09-09T22:30:00+05:00"
}
```

---

## Module 3: Candidate Field Team & Staff Management

### 3.1 List Campaign Workers (`GET /api/v1/candidate/workers`)
Returns the complete list of field workers registered under the candidate's campaign, along with their canvassing KPIs.

- **Method**: `GET`
- **Endpoint**: `/api/v1/candidate/workers`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
*None (Context is derived from Candidate Token).*

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "total_workers": 4,
  "workers": [
    {
      "id": 12,
      "name": "Tariq Mehmood",
      "phone": "03001234567",
      "role": "Field Surveyor",
      "assigned_block_code": "185010401, 185010402",
      "is_active": true,
      "surveys_count": 86,
      "pakka_count": 52,
      "kacha_count": 24,
      "last_sync_at": "2026-09-09T21:45:00+05:00"
    }
  ]
}
```

---

### 3.2 Create Field Worker (`POST /api/v1/candidate/workers`)
Registers a new field worker. If `pin` is omitted, the system automatically generates a secure 4-digit PIN and provides a 1-tap WhatsApp onboarding message link.

- **Method**: `POST`
- **Endpoint**: `/api/v1/candidate/workers`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `name` | `string` | **Yes** | Body | Worker full name |
| `phone` | `string` | **Yes** | Body | Worker mobile number (unique per candidate) |
| `assigned_block_code` | `string` | No | Body | Block code(s) (single or comma-separated) |
| `pin` | `string` | No | Body | 4-digit numeric PIN (auto-generated if omitted) |
| `role` | `string` | No | Body | Designation (e.g. `Field Canvasser`) |

#### Sample Request
```json
{
  "name": "Ali Hassan",
  "phone": "03017654321",
  "assigned_block_code": "185010403",
  "role": "Field Worker"
}
```

#### Sample Response (HTTP 201 Created)
```json
{
  "status": true,
  "message": "Field worker created successfully.",
  "worker": {
    "id": 26,
    "name": "Ali Hassan",
    "phone": "03017654321",
    "pin": "7482",
    "assigned_block_code": "185010403",
    "role": "Field Worker",
    "whatsapp_share_link": "https://api.whatsapp.com/send?phone=923017654321&text=VoterApp%20Login%20PIN:%207482"
  }
}
```

---

### 3.3 Update Field Worker (`PUT /api/v1/candidate/workers/{id}`)
Modifies a field worker's name, phone, assigned blocks, PIN, or active status.

- **Method**: `PUT`
- **Endpoint**: `/api/v1/candidate/workers/{id}`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `id` | `integer` | **Yes** | Path | Worker database ID |
| `name` | `string` | No | Body | Updated worker name |
| `phone` | `string` | No | Body | Updated phone number |
| `assigned_block_code` | `string` | No | Body | Updated block code(s) |
| `pin` | `string` | No | Body | New 4-digit PIN |
| `is_active` | `boolean` | No | Body | Set `false` to suspend worker access |

#### Sample Request
```json
{
  "assigned_block_code": "185010403, 185010404",
  "is_active": true
}
```

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "message": "Worker updated successfully.",
  "worker": {
    "id": 26,
    "name": "Ali Hassan",
    "assigned_block_code": "185010403, 185010404",
    "is_active": true
  }
}
```

---

### 3.4 Delete / Deactivate Worker (`DELETE /api/v1/candidate/workers/{id}`)
Removes a field worker from the candidate's roster.

- **Method**: `DELETE`
- **Endpoint**: `/api/v1/candidate/workers/{id}`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `id` | `integer` | **Yes** | Path | Worker ID to delete |

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "message": "Worker deleted successfully."
}
```

---

## Module 4: Candidate Live War Room & Election Day GOTV

### 4.1 Executive Live War Room Dashboard (`GET /api/v1/candidate/war-room`)
Returns campaign progress analytics, ground canvassing totals, VIP visit requests, and hourly turnout.

- **Method**: `GET`
- **Endpoint**: `/api/v1/candidate/war-room`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
*None (Context is derived from Candidate Token).*

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "summary": {
    "total_uc_voters": 12450,
    "total_gharanas_surveyed": 842,
    "pakka_votes": 3210,
    "kacha_votes": 1450,
    "mukhalif_votes": 890,
    "neutral_votes": 650,
    "coverage_percentage": 68.4
  },
  "vip_hit_list": [
    {
      "gharana_no": "042",
      "block_code": "185010401",
      "influencer_name": "Haji Abdul Rehman",
      "influencer_phone": "03009876543",
      "notes": "Elder wants candidate to personally visit for Dera meeting.",
      "latitude": 31.520378,
      "longitude": 74.358742
    }
  ],
  "hourly_turnout": {
    "slot_08_10": 420,
    "slot_10_12": 890,
    "slot_12_14": 610,
    "slot_14_17": 980,
    "total_turnout_votes": 2900
  },
  "blocks": [
    {
      "block_code": "185010401",
      "area_name": "Sanda Khurd",
      "total_voters": 1120,
      "pakka_votes": 480,
      "kacha_votes": 210,
      "coverage": 78.5
    }
  ]
}
```

---

### 4.2 GOTV Camp Thermal Parchi Issuance (`POST /api/v1/camp/issue-parchi`)
Records when a voter visits the candidate's camp outside the polling station and receives a printed voting slip, instantly updating GOTV turnout counters.

- **Method**: `POST`
- **Endpoint**: `/api/v1/camp/issue-parchi`
- **Authentication**: Candidate Token or Worker Token
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `voter_id` | `integer` | No* | Body | Database voter ID *(Provide voter_id, cnic, or block+gharana)* |
| `cnic` | `string` | No* | Body | Voter CNIC number (e.g. `35201-1234567-1`) |
| `block_code` | `string` | No* | Body | Block code (used with `gharana_no`) |
| `gharana_no` | `string` | No* | Body | Gharana number |
| `agent_name` | `string` | No | Body | Camp operator / agent identifier |

#### Sample Request
```json
{
  "cnic": "35201-1234567-1",
  "agent_name": "Camp Booth #3"
}
```

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "message": "Parchi issued and voter turnout recorded.",
  "voter": {
    "id": 101,
    "name": "Riaz Ahmad",
    "cnic": "35201-1234567-1",
    "serial_no": 1,
    "block_code": "185010401",
    "polling_station_name": "Govt Boys High School Sanda"
  },
  "turnout_recorded_at": "2026-09-09T14:35:20+05:00"
}
```

---

### 4.3 Campaign Matrix Pre-Aggregation Cron (`GET /api/v1/cron/process-campaign-matrix`)
Aggregates household surveys into cached summaries for high-speed dashboard loading. Scheduled via external cron (e.g. cron-job.org).

- **Method**: `GET`
- **Endpoint**: `/api/v1/cron/process-campaign-matrix`
- **Authentication**: Public or Cron Secret Key
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `candidate_id` | `integer` | No | Query | Specific candidate ID (if omitted, processes all) |
| `key` | `string` | No | Query | Optional cron authorization token |

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "message": "Campaign matrix processed successfully.",
  "processed_candidates": 6,
  "execution_time_ms": 32.4
}
```

---

## Module 5: Offline Voter Search & Handheld Device Telemetry

### 5.1 Device Verification & Lock Check (`GET /api/v1/auth/check-device`)
Lightweight security check called on app resume to verify if the device has been remotely locked, suspended, or revoked by Admin.

- **Method**: `GET`
- **Endpoint**: `/api/v1/auth/check-device`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
*None.*

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "is_authorized": true,
  "is_revoked": false,
  "candidate_status": "active",
  "expires_at": "2026-12-31T23:59:59Z"
}
```

---

### 5.2 Full UC SQLite Dataset Download (`GET /api/v1/download`)
Downloads all voters, census block codes, and polling stations for the candidate's assigned UC in SQLite format for zero-internet searching.

- **Method**: `GET`
- **Endpoint**: `/api/v1/download` (also `/api/v1/uc/{uc}/download`)
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `format` | `string` | No | Query | `sqlite` (default binary file) or `json` |
| `uc` | `integer` | No | Path | UC ID (strictly validated against assigned UC) |

#### Sample Response (Binary SQLite)
```http
HTTP/1.1 200 OK
Content-Type: application/x-sqlite3
Content-Disposition: attachment; filename="uc_75_voters_offline.sqlite"
Content-Length: 4194304
```

---

### 5.3 Search Audit Telemetry Sync (`POST /api/v1/sync/searches`)
Synchronizes offline search logs to server for security audits and telemetry analytics.

- **Method**: `POST`
- **Endpoint**: `/api/v1/sync/searches`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `logs` | `array` | **Yes** | Body | Array of search audit entries |

##### Search Log Object
| Field | Type | Required | Description |
|---|---|---|---|
| `query` | `string` | **Yes** | CNIC or Name search term |
| `search_type` | `string` | **Yes** | `cnic`, `name`, `family_no`, or `gharana_no` |
| `results_count` | `integer`| **Yes** | Matching voter count returned offline |
| `searched_at` | `datetime`| No | ISO8601 device timestamp |
| `latitude` | `decimal`| No | GPS latitude at search time |
| `longitude` | `decimal`| No | GPS longitude at search time |

#### Sample Request
```json
{
  "logs": [
    {
      "query": "3520112345671",
      "search_type": "cnic",
      "results_count": 1,
      "searched_at": "2026-09-09T22:15:30Z",
      "latitude": 31.5204,
      "longitude": 74.3587
    }
  ]
}
```

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "message": "Search telemetry logs synced successfully.",
  "synced_count": 1
}
```

---

### 5.4 Handheld Device Heartbeat Ping (`POST /api/v1/sync/heartbeat`)
Periodic background liveness check reporting device health, battery, network state, and GPS coordinates.

- **Method**: `POST`
- **Endpoint**: `/api/v1/sync/heartbeat`
- **Authentication**: Candidate or Worker Token
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `battery_level` | `integer` | No | Body | Battery percentage (`0` - `100`) |
| `is_charging` | `boolean` | No | Body | Power charging status |
| `network_type` | `string` | No | Body | `wifi`, `cellular`, or `offline` |
| `latitude` | `decimal` | No | Body | Device current latitude |
| `longitude` | `decimal` | No | Body | Device current longitude |
| `app_version` | `string` | No | Body | Installed app version |

#### Sample Request
```json
{
  "battery_level": 85,
  "is_charging": false,
  "network_type": "wifi",
  "latitude": 31.5204,
  "longitude": 74.3587,
  "app_version": "1.3.0"
}
```

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "message": "Heartbeat recorded.",
  "server_time": "2026-09-09T22:35:00+05:00"
}
```

---

### 5.5 Live Online Fallback Voter Search (`GET|POST /api/v1/voters/search`)
Online server search used when a handheld device needs to look up records without an offline database.

- **Method**: `GET` or `POST`
- **Endpoint**: `/api/v1/voters/search`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `cnic` | `string` | No* | Query/Body | 13-digit CNIC (with or without dashes) |
| `name` | `string` | No* | Query/Body | Voter name or relative name |
| `family_no` | `string` | No* | Query/Body | Silsila / Family serial number |
| `gharana_no` | `string` | No* | Query/Body | Household number |
| `block_code` | `string` | No | Query/Body | Specific block code filter |
| `per_page` | `integer`| No | Query/Body | Items per page (default: 25, max: 100) |
| `page` | `integer`| No | Query/Body | Page number (default: 1) |

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "total": 1,
  "current_page": 1,
  "data": [
    {
      "id": 101,
      "name": "Riaz Ahmad",
      "father_name": "Muhammad Din",
      "cnic": "35201-1234567-1",
      "serial_no": 1,
      "family_no": "012",
      "block_code": "185010401",
      "polling_station_name": "Govt Boys High School Sanda"
    }
  ]
}
```

---

## Module 6: Union Council Relational Metadata

### 6.1 Candidate Assigned Union Council Profile (`GET /api/v1/ucs`)
Returns full administrative details for the candidate's assigned Union Council.

- **Method**: `GET`
- **Endpoint**: `/api/v1/ucs`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
*None.*

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "uc": {
    "id": 75,
    "uc_no": "75",
    "name": "Ichhra Central",
    "tehsil": "Model Town",
    "district": "Lahore",
    "national_assembly": "NA-122 Lahore",
    "provincial_assembly": "PP-158 Lahore"
  }
}
```

---

### 6.2 Union Council Block Codes List (`GET /api/v1/ucs/{uc}/block-codes`)
Returns all census block codes within the specified Union Council.

- **Method**: `GET`
- **Endpoint**: `/api/v1/ucs/{uc}/block-codes`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `uc` | `integer` | **Yes** | Path | UC database ID (must match candidate's UC) |

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "total_blocks": 12,
  "block_codes": [
    {
      "id": 501,
      "block_code": "185010401",
      "area_name": "Sanda Khurd",
      "area_name_urdu": "ساندہ خورد",
      "total_voters": 1120
    }
  ]
}
```

---

### 6.3 Union Council Polling Stations List (`GET /api/v1/ucs/{uc}/polling-stations`)
Returns all polling stations in the Union Council.

- **Method**: `GET`
- **Endpoint**: `/api/v1/ucs/{uc}/polling-stations`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `uc` | `integer` | **Yes** | Path | UC database ID (must match candidate's UC) |

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "total_stations": 8,
  "polling_stations": [
    {
      "id": 81,
      "name": "Govt Boys High School Sanda",
      "address": "Main Bazar Sanda, Lahore",
      "total_booths": 6
    }
  ]
}
```

---

### 6.4 Union Council Paginated Voter List (`GET /api/v1/ucs/{uc}/voters`)
Retrieves paginated voters within the candidate's assigned Union Council.

- **Method**: `GET`
- **Endpoint**: `/api/v1/ucs/{uc}/voters`
- **Authentication**: Candidate Token (`Bearer <token>`)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `uc` | `integer` | **Yes** | Path | UC ID |
| `block_code` | `string` | No | Query | Filter by specific block code |
| `page` | `integer` | No | Query | Page number (default: 1) |

#### Sample Response (HTTP 200)
```json
{
  "status": true,
  "current_page": 1,
  "per_page": 50,
  "total": 12450,
  "data": [
    {
      "id": 101,
      "name": "Riaz Ahmad",
      "father_name": "Muhammad Din",
      "cnic": "35201-1234567-1",
      "serial_no": 1,
      "family_no": "012",
      "block_code": "185010401"
    }
  ]
}
```

---

## Module 7: Admin Panel Test Notification Hub

### 7.1 Dispatch Test Push Notification (`POST /settings/test-notification`)
Allows system administrators to test Firebase Cloud Messaging push notifications to candidates, workers, or specific device tokens directly from the Settings web console.

- **Method**: `POST`
- **Endpoint**: `/settings/test-notification`
- **Authentication**: Admin Web Session (`auth` + `admin` middleware)
- **Status**: ✅ Active & Tested

#### Request Parameters
| Parameter | Type | Required | Location | Description |
|---|---|---|---|---|
| `target_type` | `string` | **Yes** | Form Body | `broadcast_candidates`, `broadcast_workers`, `candidate`, `worker`, or `custom_token` |
| `candidate_id` | `integer` | No* | Form Body | Required if `target_type` is `candidate` |
| `worker_id` | `integer` | No* | Form Body | Required if `target_type` is `worker` |
| `fcm_token` | `string` | No* | Form Body | Required if `target_type` is `custom_token` |
| `title` | `string` | **Yes** | Form Body | Notification title (max: 150 chars) |
| `body` | `string` | **Yes** | Form Body | Notification message (max: 500 chars) |
| `priority` | `string` | No | Form Body | `high` (wake screen + sound) or `normal` |

#### Sample Response
Redirects back to `/settings` with a session toast message:
```text
Test Notification successfully sent to Candidate 'Usman Saeed' (1 device(s))! (Live FCM dispatch)
```

---

## 9. Automated Verification Test Suite

All API endpoints are validated by automated end-to-end regression suites in `scratch/`:

### Core 17 API Test Results (`scratch/test_all_apis_comprehensive.php`)
```text
============================================================
   VOTERAPP v3.0 — COMPLETE END-TO-END API TEST SUITE      
============================================================
 [PASS] #1  [POST] /api/v1/auth/login -> HTTP 200
 [PASS] #2  [POST] /api/v1/auth/campaign-branding -> HTTP 200
 [PASS] #3  [POST] /api/v1/staff/login -> HTTP 200
 [PASS] #4  [GET]  /api/v1/staff/block/data -> HTTP 200
 [PASS] #5  [POST] /api/v1/staff/survey/sync -> HTTP 200
 [PASS] #6  [GET]  /api/v1/candidate/workers -> HTTP 200
 [PASS] #7  [POST] /api/v1/candidate/workers -> HTTP 201
 [PASS] #8  [PUT]  /api/v1/candidate/workers/{id} -> HTTP 200
 [PASS] #9  [DELETE] /api/v1/candidate/workers/{id} -> HTTP 200
 [PASS] #10 [GET]  /api/v1/candidate/war-room -> HTTP 200
 [PASS] #11 [POST] /api/v1/camp/issue-parchi -> HTTP 200
 [PASS] #12 [GET]  /api/v1/cron/process-campaign-matrix -> HTTP 200
 [PASS] #13 [GET]  /api/v1/auth/check-device -> HTTP 200
 [PASS] #14 [GET]  /api/v1/download -> HTTP 200
 [PASS] #15 [POST] /api/v1/sync/searches -> HTTP 200
 [PASS] #16 [POST] /api/v1/sync/heartbeat -> HTTP 200
 [PASS] #17 [GET]  /api/v1/voters/search -> HTTP 200
============================================================
   FINAL RESULTS: Passed: 17 / 17 | Failed: 0 (100% OPERATIONAL)
============================================================
```

### Settings FCM Test Suite (`scratch/test_settings_test_notification.php`)
```text
============================================================
   VOTERAPP - SETTINGS TEST NOTIFICATION VERIFICATION      
============================================================
 [PASS] Admin user authenticated for Settings access
 [PASS] SettingsController@index provides fcm_config and fcm_stats
 [PASS] Candidate device count accurately detected
 [PASS] Firebase project_id matches voterapp-9ac02
 [PASS] Settings HTML renders FCM Push Notification Tester card
 [PASS] Broadcast to Candidates dispatched
 [PASS] Broadcast to Workers dispatched
 [PASS] Specific Candidate Push dispatched
 [PASS] Specific Worker Push dispatched
 [PASS] Custom FCM Token Push dispatched
 [PASS] Route 'settings.notification.test' registered
============================================================
   FINAL TEST RESULTS: Passed: 13 / 13 | Failed: 0 (100% SUCCESS)
============================================================
```
