@extends('layouts.app')

@section('title', 'Mobile REST APIs & Documentation')

@section('content')
    <!-- Top Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Mobile REST APIs & Documentation (v1)</h4>
            <small class="text-muted">Offline-First Voter Verification System &bull; Multi-Device Polling Agent Authorization &bull; Telemetry Sync</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 font-mono">
                <i class="bi bi-broadcast me-1"></i> Base URL: {{ $baseUrl }}
            </span>
            <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1')">
                <i class="bi bi-clipboard me-1"></i> Copy Base URL
            </button>
        </div>
    </div>

    <!-- Quick Navigation Pills -->
    <div class="card shadow-sm mb-3">
        <div class="card-body p-2 d-flex flex-wrap gap-1 align-items-center">
            <span class="small fw-bold text-muted me-2 ps-2"><i class="bi bi-compass me-1"></i> Jump to:</span>
            <a href="#api-auth-login" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">1. Candidate Login</a>
            <a href="#api-campaign-branding" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">2. Campaign Branding</a>
            <a href="#api-fcm-token" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">3. FCM Push Token</a>
            <a href="#api-staff-login" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">4. Staff PIN Login</a>
            <a href="#api-staff-block-data" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">5. Staff Block Data</a>
            <a href="#api-staff-survey-sync" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">6. Survey Sync &amp; GPS</a>
            <a href="#api-candidate-workers" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">7. Workers Management</a>
            <a href="#api-candidate-war-room" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">8. War Room &amp; Turnout</a>
            <a href="#api-camp-parchi" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">9. Camp Parchi</a>
            <a href="#api-cron-matrix" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">10. Matrix Cron</a>
            <a href="#api-check-device" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">11. Check Device</a>
            <a href="#api-download" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">12. Download UC Dataset</a>
            <a href="#api-sync-searches" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">13. Sync Searches</a>
            <a href="#api-sync-heartbeat" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">14. Device Heartbeat</a>
            <a href="#api-voter-search" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">15. Online Search</a>
            <a href="#api-raw-endpoints" class="badge bg-light text-dark border text-decoration-none py-1.5 px-2">16. Raw Endpoints</a>
        </div>
    </div>

    <!-- API LISTING CONTAINER -->
    <div class="row g-3">
        <div class="col-12">

            <!-- ========================================== -->
            <!-- 1. CANDIDATE LOGIN & DEVICE AUTHORIZATION -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-auth-login">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-success">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success font-monospace px-2.5 py-1.5">POST</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/auth/login</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/auth/login</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/auth/login')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Authenticates candidate worker credentials, registers the physical mobile device UID (unlimited devices allowed per candidate UC account), and returns authorization token, candidate profile, party branding (party logo, candidate photo, election symbol), and UC details.
                    </p>

                    <div class="row g-3">
                        <!-- Request Details -->
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-box-arrow-in-right me-1 text-primary"></i> Request Headers &amp; Payload</h6>
                            <div class="table-responsive mb-2">
                                <table class="table table-sm table-bordered small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Field</th>
                                            <th>Type</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>email</code></td>
                                            <td>string</td>
                                            <td><span class="badge bg-danger-subtle text-danger">Yes</span></td>
                                            <td>Candidate account email</td>
                                        </tr>
                                        <tr>
                                            <td><code>password</code></td>
                                            <td>string</td>
                                            <td><span class="badge bg-danger-subtle text-danger">Yes</span></td>
                                            <td>Candidate account password</td>
                                        </tr>
                                        <tr>
                                            <td><code>device_uid</code></td>
                                            <td>string</td>
                                            <td><span class="badge bg-danger-subtle text-danger">Yes</span></td>
                                            <td>Hardware unique UUID / Android ID</td>
                                        </tr>
                                        <tr>
                                            <td><code>device_name</code></td>
                                            <td>string</td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td>
                                            <td>Device model (e.g. Samsung Galaxy S23)</td>
                                        </tr>
                                        <tr>
                                            <td><code>platform</code></td>
                                            <td>string</td>
                                            <td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td>
                                            <td><code>android</code> or <code>ios</code></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="position-relative">
                                <div class="d-flex justify-content-between align-items-center bg-dark text-white px-3 py-1.5 rounded-top small">
                                    <span class="font-mono text-warning">Request Body (JSON / Form-Data)</span>
                                    <button class="btn btn-link btn-sm text-white p-0 text-decoration-none" onclick="copyElementText('req-login-json')"><i class="bi bi-clipboard"></i> Copy</button>
                                </div>
                                <pre class="bg-dark text-light p-3 rounded-bottom font-mono small mb-0 overflow-auto" id="req-login-json">{
  "email": "{{ $sampleCandidate->email ?? 'candidate@election.com' }}",
  "password": "yourpassword",
  "device_uid": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
  "device_name": "Infinix Hot 30",
  "platform": "android",
  "app_version": "1.0.0"
}</pre>
                            </div>
                        </div>

                        <!-- Response Details -->
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-box-arrow-up-right me-1 text-success"></i> Success Response (200 OK)</h6>
                            <div class="position-relative">
                                <div class="d-flex justify-content-between align-items-center bg-dark text-white px-3 py-1.5 rounded-top small">
                                    <span class="font-mono text-success">HTTP 200 OK Response</span>
                                    <button class="btn btn-link btn-sm text-white p-0 text-decoration-none" onclick="copyElementText('res-login-json')"><i class="bi bi-clipboard"></i> Copy</button>
                                </div>
                                <pre class="bg-dark text-light p-3 rounded-bottom font-mono small mb-0 overflow-auto" id="res-login-json" style="max-height: 290px;">{
  "status": true,
  "message": "Login successful. Device authorized.",
  "token": "MjozYTRiNWM2ZC03ZThmLTkwYWI6MTc4ODQzNDExMDo3MzI3...",
  "candidate": {
    "id": {{ $sampleCandidate->id ?? 1 }},
    "name": "{{ $sampleCandidate->name ?? 'Candidate Name' }}",
    "email": "{{ $sampleCandidate->email ?? 'candidate@election.com' }}",
    "phone": "{{ $sampleCandidate->phone ?? '0300-1234567' }}",
    "max_devices": null,
    "active_devices": 1
  },
  "branding": {
    "party_name": "{{ $sampleCandidate->party_name ?? 'PTI' }}",
    "is_independent": false,
    "candidate_symbol": "{{ $sampleCandidate->candidate_symbol ?? 'Bat' }}",
    "party_logo_url": "{{ $baseUrl }}/uploads/branding/party_sample.png",
    "candidate_image_url": "{{ $baseUrl }}/uploads/branding/candidate_sample.png",
    "candidate_symbol_image_url": "{{ $baseUrl }}/uploads/branding/symbol_sample.png"
  },
  "uc": {
    "id": {{ $sampleUc->id ?? 1 }},
    "uc_no": "{{ $sampleUc->uc_no ?? '1' }}",
    "name": "{{ $sampleUc->name ?? 'UC Name' }}",
    "tehsil": "{{ $sampleUc->tehsil->name ?? 'Tehsil' }}",
    "district": "{{ $sampleUc->tehsil->district->name ?? 'District' }}"
  },
  "server_time": "{{ now()->toIso8601String() }}"
}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 2. CAMPAIGN BRANDING (WHITE-LABEL THEME)  -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-campaign-branding">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-info">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success font-monospace px-2.5 py-1.5">POST</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/auth/campaign-branding</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/auth/campaign-branding</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/auth/campaign-branding')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Returns candidate party symbol, logos, slogan, and primary/accent hex colors for instant app UI morphing before or during login.
                    </p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-box-arrow-in-right me-1 text-primary"></i> Request Payload</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light"><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>candidate_code</code></td><td>string</td><td><span class="badge bg-success-subtle text-success">Recommended</span></td><td>Unique code (e.g. <code>PTI-9089</code>)</td></tr>
                                    <tr><td><code>email</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td>Candidate email address</td></tr>
                                    <tr><td><code>uc_id</code></td><td>integer</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td>Assigned Union Council database ID</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-braces me-1 text-success"></i> Response Sample</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "candidate_code": "PTI-9089",
  "party_name": "Pakistan Tehreek-e-Insaf",
  "candidate_symbol": "Bat",
  "party_slogan": "Haqeeqi Azadi",
  "primary_color": "#006633",
  "accent_color": "#E31B23"
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 3. FCM PUSH NOTIFICATION TOKEN REGISTRATION -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-fcm-token">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-warning">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success font-monospace px-2.5 py-1.5">POST</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/auth/fcm-token</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/auth/fcm-token</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/auth/fcm-token')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Registers or refreshes Firebase Cloud Messaging device registration tokens for Candidate or Field Worker devices. Project ID: <code>voterapp-9ac02</code>.
                    </p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-box-arrow-in-right me-1 text-primary"></i> Request Payload &amp; Headers</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light"><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>Authorization</code></td><td>header</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td><code>Bearer &lt;token&gt;</code> (Candidate or Worker)</td></tr>
                                    <tr><td><code>fcm_token</code></td><td>string</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td>Firebase FCM device token from mobile SDK</td></tr>
                                    <tr><td><code>user_type</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td><code>candidate</code> or <code>worker</code></td></tr>
                                    <tr><td><code>device_uid</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td>Hardware device identifier</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-braces me-1 text-success"></i> Response Sample</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "message": "FCM device token registered successfully.",
  "target_type": "candidate",
  "device_id": 14
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 4. STAFF 4-DIGIT PIN AUTHENTICATION       -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-staff-login">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-success">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success font-monospace px-2.5 py-1.5">POST</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/staff/login</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/staff/login</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/staff/login')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Authenticates field canvassers with Candidate Code, 11-digit phone, and 4-digit PIN. Returns worker token and assigned census blocks list.
                    </p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-box-arrow-in-right me-1 text-primary"></i> Request Payload</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light"><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>candidate_code</code></td><td>string</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td>Candidate unique code (e.g. <code>PTI-9089</code>)</td></tr>
                                    <tr><td><code>phone</code></td><td>string</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td>Registered worker phone (e.g. <code>03001234567</code>)</td></tr>
                                    <tr><td><code>pin</code></td><td>string</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td>4-digit numeric PIN (e.g. <code>1234</code>)</td></tr>
                                    <tr><td><code>device_uid</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td>Worker phone hardware identifier</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-braces me-1 text-success"></i> Response Sample</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "message": "Staff login successful.",
  "worker_token": "vp_xwWfkODCHw3Zjtck6LUNoOs4pWkxos2u...",
  "worker": {
    "id": 12,
    "name": "Tariq Mehmood",
    "assigned_block_codes": ["185010401", "185010402"],
    "total_assigned_blocks": 2
  }
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 5. STAFF BLOCK DATA DOWNLOAD               -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-staff-block-data">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-primary">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary font-monospace px-2.5 py-1.5">GET</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/staff/block/data</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/staff/block/data</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/staff/block/data')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Downloads gharanas and registered voters for worker's assigned census blocks (supports multi-block comma separated assignments).
                    </p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-card-checklist me-1 text-primary"></i> Query / Header Parameters</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light"><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>Authorization</code></td><td>header</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td><code>Bearer &lt;worker_token&gt;</code></td></tr>
                                    <tr><td><code>block_code</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td>Specific block code (if worker has multiple)</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-braces me-1 text-success"></i> Response Sample</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "assigned_blocks": ["185010401", "185010402"],
  "total_gharanas": 145,
  "total_voters": 580,
  "gharanas": [
    {
      "block_code": "185010401",
      "gharana_no": "001",
      "head_name": "Chaudhry Riaz Ahmad",
      "influencer_phone": "03214567890",
      "party_inclination": "pakka",
      "total_voters": 4
    }
  ]
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 6. STAFF SURVEY BATCH SYNC & GPS          -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-staff-survey-sync">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-danger">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success font-monospace px-2.5 py-1.5">POST</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/staff/survey/sync</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/staff/survey/sync</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/staff/survey/sync')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Submits offline household surveys. Records <strong>Anti-Fraud GPS coordinates</strong>, stores <strong>influencer phone</strong> for 1-tap War Room calling, and triggers <strong>Instant Firebase Push Notifications</strong> if VIP visit is flagged.
                    </p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-box-arrow-in-right me-1 text-primary"></i> Survey Object Parameters</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light"><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>block_code</code></td><td>string</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td>Census block code</td></tr>
                                    <tr><td><code>gharana_no</code></td><td>string</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td>Household number (e.g. <code>042</code>)</td></tr>
                                    <tr><td><code>party_inclination</code></td><td>string</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td><code>pakka</code>, <code>kacha</code>, <code>mukhalif</code>, <code>neutral</code></td></tr>
                                    <tr><td><code>influencer_phone</code></td><td>string</td><td><span class="badge bg-success-subtle text-success">Recommended</span></td><td>Elder phone for 1-tap War Room direct call</td></tr>
                                    <tr><td><code>is_vip_visit_requested</code></td><td>boolean</td><td><span class="badge bg-warning-subtle text-dark">Alert Trigger</span></td><td>If <code>true</code>, fires instant FCM alert to Candidate</td></tr>
                                    <tr><td><code>latitude</code> / <code>longitude</code></td><td>decimal</td><td><span class="badge bg-info-subtle text-info">Anti-Fraud</span></td><td>Ground GPS coordinates (e.g. 31.5204, 74.3587)</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-braces me-1 text-success"></i> Response Sample</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "message": "1 survey(s) synced successfully.",
  "synced_count": 1,
  "vip_alerts_sent": 1,
  "last_sync_at": "2026-09-09T22:30:00+05:00"
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 7. CANDIDATE WORKERS CRUD MANAGEMENT       -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-candidate-workers">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-info">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary font-monospace px-2.5 py-1.5">GET / POST / PUT / DELETE</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/candidate/workers</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/candidate/workers</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/candidate/workers')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Self-service worker management for candidates. Create field workers with auto-generated 4-digit PINs and instant 1-tap WhatsApp sharing links.
                    </p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-box-arrow-in-right me-1 text-primary"></i> Create Worker Parameters (POST)</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light"><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>name</code></td><td>string</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td>Worker full name</td></tr>
                                    <tr><td><code>phone</code></td><td>string</td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td>11-digit phone (e.g. <code>03017654321</code>)</td></tr>
                                    <tr><td><code>assigned_block_code</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td>Block code(s) (e.g. <code>185010401, 185010402</code>)</td></tr>
                                    <tr><td><code>pin</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Auto-Gen</span></td><td>4-digit PIN (auto-generated if omitted)</td></tr>
                                    <tr><td><code>role</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td>Designation (e.g. <code>Field Worker</code>)</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-braces me-1 text-success"></i> Response Sample (POST 201)</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "message": "Field worker created successfully.",
  "worker": {
    "id": 26,
    "name": "Ali Hassan",
    "phone": "03017654321",
    "pin": "7482",
    "assigned_block_code": "185010403",
    "whatsapp_share_link": "https://api.whatsapp.com/send?phone=923017654321&text=..."
  }
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 8. CANDIDATE EXECUTIVE LIVE WAR ROOM       -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-candidate-war-room">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-success">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary font-monospace px-2.5 py-1.5">GET</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/candidate/war-room</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/candidate/war-room</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/candidate/war-room')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Executive mobile dashboard for candidate. Aggregates Pakka/Kacha/Mukhalif votes, coverage percentage, pending VIP visit requests, and <strong>Hourly Turnout Breakdown</strong> across 4 time slots.
                    </p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-card-checklist me-1 text-primary"></i> Request Headers</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light"><tr><th>Header</th><th>Required</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>Authorization</code></td><td><span class="badge bg-danger-subtle text-danger">Yes</span></td><td><code>Bearer &lt;candidate_token&gt;</code></td></tr>
                                </tbody>
                            </table>
                            <div class="p-2 rounded bg-light border small mt-2">
                                <i class="bi bi-clock me-1 text-primary"></i> <strong>Hourly Turnout Slots:</strong>
                                <code>slot_08_10</code> (08:00 - 10:00), <code>slot_10_12</code> (10:00 - 12:00), <code>slot_12_14</code> (12:00 - 14:00), <code>slot_14_17</code> (14:00 - 17:00).
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-braces me-1 text-success"></i> Response Sample</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "summary": {
    "pakka_votes": 3210,
    "kacha_votes": 1450,
    "mukhalif_votes": 890,
    "coverage_percentage": 68.4
  },
  "hourly_turnout": {
    "slot_08_10": 420,
    "slot_10_12": 890,
    "slot_12_14": 610,
    "slot_14_17": 980,
    "total_turnout_votes": 2900
  },
  "vip_hit_list": [
    {
      "gharana_no": "042",
      "influencer_phone": "03009876543"
    }
  ]
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 9. GOTV CAMP ISSUE THERMAL PARCHI          -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-camp-parchi">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-warning">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success font-monospace px-2.5 py-1.5">POST</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/camp/issue-parchi</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/camp/issue-parchi</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/camp/issue-parchi')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Records election day voter turnout when a thermal slip is printed at the candidate's GOTV polling camp.
                    </p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-box-arrow-in-right me-1 text-primary"></i> Request Payload</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light"><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>cnic</code></td><td>string</td><td><span class="badge bg-success-subtle text-success">Recommended</span></td><td>13-digit CNIC (e.g. <code>35201-1234567-1</code>)</td></tr>
                                    <tr><td><code>voter_id</code></td><td>integer</td><td><span class="badge bg-secondary-subtle text-secondary">Alternative</span></td><td>Database voter ID</td></tr>
                                    <tr><td><code>block_code</code> + <code>gharana_no</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Alternative</span></td><td>Block &amp; household number</td></tr>
                                    <tr><td><code>agent_name</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td>Camp booth operator name</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-braces me-1 text-success"></i> Response Sample</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "message": "Parchi issued and voter turnout recorded.",
  "turnout_recorded_at": "2026-09-09T14:35:20+05:00"
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 10. CAMPAIGN MATRIX BACKGROUND CRON        -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-cron-matrix">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-secondary">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary font-monospace px-2.5 py-1.5">GET</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/cron/process-campaign-matrix</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/cron/process-campaign-matrix</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/cron/process-campaign-matrix')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Lightweight automated cron job invoked periodically (e.g. cron-job.org) to pre-aggregate campaign statistics for instant caching.
                    </p>
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-card-checklist me-1 text-primary"></i> Query Parameters</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light"><tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>candidate_id</code></td><td>integer</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td>Specific candidate ID (processes all if omitted)</td></tr>
                                    <tr><td><code>key</code></td><td>string</td><td><span class="badge bg-secondary-subtle text-secondary">Optional</span></td><td>Cron secret key authorization</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-braces me-1 text-success"></i> Response Sample</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "message": "Campaign matrix processed successfully.",
  "processed_candidates": 6,
  "execution_time_ms": 32.4
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 11. CHECK DEVICE AUTHORIZATION STATUS     -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-check-device">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-primary">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary font-monospace px-2.5 py-1.5">GET</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/auth/check-device</code>
                        <span class="badge bg-light text-muted border d-none d-md-inline">or /v1/auth/check-device</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/auth/check-device')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Lightweight device authorization check. Mobile app calls this upon opening or resuming to verify whether the admin has revoked/blocked this device.
                    </p>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-card-checklist me-1 text-primary"></i> Query / Header Parameters</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Location</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>Authorization</code></td>
                                        <td>Header</td>
                                        <td><span class="badge bg-success-subtle text-success">Recommended</span></td>
                                        <td><code>Bearer &lt;token&gt;</code> returned during login</td>
                                    </tr>
                                    <tr>
                                        <td><code>device_uid</code></td>
                                        <td>Query Param</td>
                                        <td><span class="badge bg-warning-subtle text-dark">Alternative</span></td>
                                        <td><code>?device_uid=&lt;hardware_id&gt;</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-shield-check me-1 text-success"></i> Response Samples</h6>
                            <div class="position-relative">
                                <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "is_authorized": true,
  "device_name": "Samsung Galaxy S23",
  "last_active_at": "{{ now()->toIso8601String() }}"
}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 3. DOWNLOAD COMPLETE UC OFFLINE DATASET -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-download">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-info">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary font-monospace px-2.5 py-1.5">GET</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/download</code>
                        <span class="badge bg-light text-dark border">and /api/v1/uc/{uc_id}/download</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/download')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        <strong>Core Offline Engine:</strong> Downloads all voters, block codes (with English &amp; Urdu area names and population), and polling stations for the candidate's assigned UC into a single JSON payload. The mobile app saves this directly into local SQLite for 100% offline search without internet.
                    </p>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-hdd-network me-1 text-info"></i> Query Parameters</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Parameter</th>
                                        <th>Type</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>Authorization</code></td>
                                        <td>Header</td>
                                        <td><span class="badge bg-danger-subtle text-danger">Required</span></td>
                                        <td><code>Bearer &lt;token&gt;</code> received from <code>/auth/login</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>uc_id</code></td>
                                        <td>integer</td>
                                        <td><span class="badge bg-secondary-subtle text-dark">Optional</span></td>
                                        <td>Must match candidate's assigned UC (Cross-UC access returns <code>403 Forbidden</code>)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-file-earmark-code me-1 text-success"></i> Sample Dataset Response (200 OK)</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto" style="max-height: 250px;">{
  "status": true,
  "message": "UC Dataset downloaded successfully.",
  "uc": {
    "id": 75,
    "uc_no": "1",
    "name": "Shadi Pura",
    "name_ur": "شادی پورہ",
    "tehsil": "Shalimar",
    "district": "Lahore"
  },
  "total_voters": 14250,
  "block_codes": [
    {
      "id": 1,
      "code": "260250202",
      "area_name": "Band Road Shadi Pura Gulzar Madina Moti Masjid",
      "area_name_ur": "بند روڈ شادی پورہ گلزار مدینہ موتی مسجد",
      "population": 2857
    }
  ],
  "polling_stations": [
    {
      "id": 10,
      "name": "Govt High School Shadi Pura (Male Booth)",
      "total_voters": 1850
    }
  ],
  "voters": [
    {
      "id": 101,
      "silsala_no": "1",
      "gharana_no": "12",
      "name": "Muhammad Ali",
      "father_name": "Muhammad Aslam",
      "cnic": "3520112345671",
      "formatted_cnic": "35201-1234567-1",
      "age": 42,
      "address": "Street 4, Shadi Pura",
      "block_code": "260250202",
      "area_name": "Band Road Shadi Pura Gulzar Madina Moti Masjid",
      "area_name_ur": "بند روڈ شادی پورہ گلزار مدینہ موتی مسجد",
      "polling_station_name": "Govt High School Shadi Pura"
    }
  ]
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 4. SILENT SEARCH TELEMETRY SYNC -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-sync-searches">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-warning">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success font-monospace px-2.5 py-1.5">POST</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/sync/searches</code>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/sync/searches')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        When the polling worker's device connects to internet, the app silently uploads search activity telemetry logs (CNIC query, Name query, Silsala query) to populate the Admin Dashboard Live Telemetry Matrix.
                    </p>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-upload me-1 text-warning"></i> Payload Format (Batch Sync)</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "device_uid": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
  "searches": [
    {
      "query_type": "cnic",
      "results_count": 1,
      "searched_at": "2026-09-03 14:22:10"
    },
    {
      "query_type": "name",
      "results_count": 5,
      "searched_at": "2026-09-03 14:25:34"
    },
    {
      "query_type": "gharana",
      "results_count": 8,
      "searched_at": "2026-09-03 14:29:12"
    }
  ]
}</pre>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2"><i class="bi bi-check2-circle me-1 text-success"></i> Response (200 OK)</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "message": "Searches telemetry synced successfully.",
  "synced_count": 3
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 5. SILENT DEVICE HEARTBEAT -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-sync-heartbeat">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-secondary">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success font-monospace px-2.5 py-1.5">POST</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/sync/heartbeat</code>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/sync/heartbeat')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Periodic background ping (every 15-30 minutes when online) to update the device's last active timestamp on the Admin Operations Matrix.
                    </p>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "device_uid": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
  "battery_level": 78,
  "app_version": "1.0.0"
}</pre>
                        </div>
                        <div class="col-lg-6">
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto">{
  "status": true,
  "message": "Heartbeat acknowledged.",
  "server_time": "{{ now()->toIso8601String() }}"
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 6. LIVE ONLINE VOTER SEARCH FALLBACK -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-voter-search">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2 border-start border-4 border-dark">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-dark font-monospace px-2.5 py-1.5">POST / GET</span>
                        <code class="fs-6 fw-bold text-dark font-mono">/api/v1/voters/search</code>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/v1/voters/search')">
                        <i class="bi bi-copy me-1"></i> Copy Endpoint
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Live online voter lookup fallback when worker needs to query the server database directly.
                    </p>

                    <div class="row g-3">
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2">Search Parameters</h6>
                            <table class="table table-sm table-bordered small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Field</th>
                                        <th>Required</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>q</code> or <code>cnic</code></td>
                                        <td><span class="badge bg-danger-subtle text-danger">Yes</span></td>
                                        <td>13-digit CNIC (with/without dashes) or Name or Gharana #</td>
                                    </tr>
                                    <tr>
                                        <td><code>uc_id</code></td>
                                        <td><span class="badge bg-secondary-subtle text-dark">Optional</span></td>
                                        <td>Filter specifically for a Union Council</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <h6 class="fw-bold text-dark small mb-2">Search Results Response</h6>
                            <pre class="bg-dark text-light p-3 rounded font-mono small mb-0 overflow-auto" style="max-height: 200px;">{
  "status": true,
  "count": 1,
  "voters": [
    {
      "id": 105,
      "silsala_no": "24",
      "gharana_no": "8",
      "name": "Muhammad Tariq",
      "father_name": "Abdul Rashid",
      "cnic": "3520112345678",
      "formatted_cnic": "35201-1234567-8",
      "age": 38,
      "address": "Street 2, Shadi Pura",
      "block_code": "260250202",
      "area_name": "Band Road Shadi Pura Gulzar Madina Moti Masjid",
      "polling_station_name": "Govt High School Shadi Pura"
    }
  ]
}</pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 7. RAW / DIRECT CONSTITUENCY RESOURCES -->
            <!-- ========================================== -->
            <div class="card shadow-sm mb-3" id="api-raw-endpoints">
                <div class="card-header bg-white py-3 fw-bold text-dark border-start border-4 border-secondary">
                    <i class="bi bi-link-45deg me-1 text-primary"></i> Direct Constituency REST Resources
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-matrix align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>HTTP Method</th>
                                    <th>Endpoint</th>
                                    <th>Description</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-primary font-mono">GET</span></td>
                                    <td><code>/api/ucs</code></td>
                                    <td>List all Union Councils (UCs) with Tehsils &amp; Districts</td>
                                    <td class="text-end">
                                        <a href="{{ url('/api/ucs') }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i> View JSON</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary font-mono">GET</span></td>
                                    <td><code>/api/ucs/{uc_id}/block-codes</code></td>
                                    <td>List Census Block Codes &amp; Electoral Areas for a specific UC</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/ucs/{{ $sampleUc->id ?? 1 }}/block-codes')"><i class="bi bi-copy me-1"></i> Copy</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary font-mono">GET</span></td>
                                    <td><code>/api/ucs/{uc_id}/polling-stations</code></td>
                                    <td>List Polling Stations and booth locations for a specific UC</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/ucs/{{ $sampleUc->id ?? 1 }}/polling-stations')"><i class="bi bi-copy me-1"></i> Copy</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary font-mono">GET</span></td>
                                    <td><code>/api/ucs/{uc_id}/voters</code></td>
                                    <td>Paginated voters list for a specific UC</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $baseUrl }}/api/ucs/{{ $sampleUc->id ?? 1 }}/voters')"><i class="bi bi-copy me-1"></i> Copy</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Copy Helper Script -->
    <script>
        function copyText(text) {
            navigator.clipboard.writeText(text).then(function() {
                if (typeof toastr !== 'undefined') {
                    toastr.success('Copied to clipboard: ' + text);
                } else {
                    alert('Copied: ' + text);
                }
            });
        }

        function copyElementText(id) {
            const el = document.getElementById(id);
            if (el) {
                copyText(el.innerText);
            }
        }
    </script>
@endsection
