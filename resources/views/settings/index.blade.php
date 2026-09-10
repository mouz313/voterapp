@extends('layouts.app')

@section('title', 'Database Backup & System Settings')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">System Settings &amp; Database Backup</h4>
            <small class="text-muted">Manage full database exports, system backups, and maintenance routines</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 font-mono">
                <i class="bi bi-clock-history me-1"></i> PKT (UTC+5): {{ $server_time }}
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 font-mono">
                <i class="bi bi-database-check me-1"></i> DB: {{ $db_name }}
            </span>
            <span class="badge bg-light text-dark border px-2.5 py-1.5 font-mono">
                <i class="bi bi-hdd me-1"></i> Approx Size: {{ $db_size }} MB
            </span>
        </div>
    </div>

    <!-- 1. FULL DATABASE BACKUP HERO CARD -->
    <div class="card shadow-sm border-0 border-start border-4 border-success mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success text-white d-flex align-items-center justify-content-center" style="width: 58px; height: 58px; font-size: 1.8rem; box-shadow: 0 4px 14px rgba(0, 102, 51, 0.3);">
                        <i class="bi bi-cloud-arrow-down"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">Complete Database Backup (.sql)</h5>
                        <p class="text-muted small mb-0">
                            Exports all 19 database tables (Voters, Polling Stations, Block Codes, UCs, Assemblies, Candidates, Branding Images paths, Telemetry Logs) into a single standard SQL file.
                        </p>
                        <small class="text-success fw-semibold mt-1 d-block">
                            <i class="bi bi-shield-check me-1"></i> 100% compatible with phpMyAdmin, MySQL Workbench, and command line restore.
                        </small>
                    </div>
                </div>

                <div>
                    <a href="{{ route('settings.backup.download') }}" class="btn btn-success btn-lg px-4 py-2.5 shadow-sm fw-semibold" id="downloadBackupBtn" onclick="triggerBackupLoader()">
                        <i class="bi bi-download me-2"></i> Download Full Database Backup
                    </a>
                </div>
            </div>

            <!-- Current Database Counts Breakdown -->
            <hr class="my-3">
            <div class="row g-2 text-center text-md-start">
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Total Voters</small>
                        <span class="fs-6 fw-bold text-dark">{{ number_format($counts['voters']) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Union Councils</small>
                        <span class="fs-6 fw-bold text-dark">{{ number_format($counts['ucs']) }} UCs</span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Census Blocks</small>
                        <span class="fs-6 fw-bold text-dark">{{ number_format($counts['blocks']) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Polling Stations</small>
                        <span class="fs-6 fw-bold text-dark">{{ number_format($counts['stations']) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Candidates</small>
                        <span class="fs-6 fw-bold text-dark">{{ number_format($counts['candidates']) }}</span>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="p-2 rounded bg-light border">
                        <small class="text-muted d-block">Search Logs</small>
                        <span class="fs-6 fw-bold text-primary">{{ number_format($counts['search_logs']) }} ({{ number_format($counts['total_searches']) }} Q)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. WEB-BASED CRONJOBS & BACKGROUND AUTOMATIONS HUB -->
    <div class="card shadow-sm border-0 border-start border-4 border-primary mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Scheduled CronJobs &amp; System Automations (Web Console)
                </h6>
                <small class="text-muted">Monitor background tasks, inspect schedules in Pakistan Standard Time, and trigger jobs manually via web</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono">
                    <i class="bi bi-geo-alt me-1"></i>PKT (UTC+05:00)
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle font-mono">
                    <i class="bi bi-broadcast me-1"></i>Scheduler Active
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-matrix align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>CronJob / Automation Task</th>
                        <th>Schedule Frequency</th>
                        <th>Artisan Command</th>
                        <th>Status</th>
                        <th class="text-end">Manual Web Trigger</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cron_jobs as $job)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded p-2 bg-light border text-{{ $job['badge_color'] }}">
                                        <i class="bi {{ $job['icon'] }} fs-5"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark">{{ $job['name'] }}</span>
                                        <small class="text-muted d-block">{{ $job['description'] }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-mono">
                                    <i class="bi bi-calendar-event me-1 text-primary"></i>{{ $job['schedule'] }}
                                </span>
                            </td>
                            <td>
                                <code class="text-primary font-mono small">{{ $job['command'] }}</code>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle font-mono">
                                    <i class="bi bi-check-circle-fill me-1"></i>Active
                                </span>
                            </td>
                            <td class="text-end">
                                @if ($job['id'] === 'search-logs:clear')
                                    <div class="btn-group">
                                        <form method="POST" action="{{ route('settings.cron.run', 'search-logs:clear') }}" class="d-inline" onsubmit="return confirm('Prune search logs older than 30 days?');">
                                            @csrf
                                            <input type="hidden" name="days" value="30">
                                            <button type="submit" class="btn btn-sm btn-outline-info" title="Prune logs older than 30 days">
                                                <i class="bi bi-clock-history me-1"></i> Prune &gt;30d
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('settings.cron.run', 'search-logs:clear') }}" class="d-inline ms-1" onsubmit="return confirm('WARNING: Are you sure you want to completely CLEAR ALL search logs?');">
                                            @csrf
                                            <input type="hidden" name="all" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Clear all search logs immediately">
                                                <i class="bi bi-trash3 me-1"></i> Wipe All
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <form method="POST" action="{{ route('settings.cron.run', $job['id']) }}" class="d-inline" onsubmit="return confirm('Execute {{ $job['name'] }} now?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm" title="Run {{ $job['name'] }} immediately">
                                            <i class="bi bi-play-fill me-1"></i> Run Now
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-light py-2 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small">
                <div class="text-muted">
                    <i class="bi bi-terminal me-1 text-primary"></i><strong>Server Crontab Setup:</strong> Add this standard cron command on Linux VPS / cPanel / Windows Task Scheduler:
                </div>
                <div class="input-group input-group-sm" style="max-width: 480px;">
                    <input type="text" class="form-control font-mono bg-white" id="crontabCommand" value="* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('crontabCommand').value); toastr.info('Crontab command copied to clipboard!');">
                        <i class="bi bi-copy me-1"></i> Copy
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. FIREBASE PUSH NOTIFICATION TESTER -->
    <div class="card shadow-sm border-0 border-start border-4 border-warning mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-bell-fill me-2 text-warning"></i>Firebase Push Notification Tester (FCM Hub)
                </h6>
                <small class="text-muted">Test real-time push notification delivery to Candidate mobile apps and Field Worker devices</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-mono">
                    <i class="bi bi-fire me-1"></i>Project: {{ $fcm_config['project_id'] }}
                </span>
                @if ($fcm_config['has_server_key'])
                    <span class="badge bg-success-subtle text-success border border-success-subtle font-mono">
                        <i class="bi bi-check-circle-fill me-1"></i>FCM Key: Configured (Live)
                    </span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-mono" title="Set FIREBASE_SERVER_KEY in .env for live physical device dispatches">
                        <i class="bi bi-info-circle me-1"></i>FCM Key: Simulated (No Key in .env)
                    </span>
                @endif
            </div>
        </div>
        <div class="card-body p-4">
            <!-- FCM Metrics & Quick Stats -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="p-3 rounded bg-light border d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 46px; height: 46px; font-size: 1.25rem;">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block fw-semibold">Candidate Devices</small>
                            <span class="fs-5 fw-bold text-dark">{{ $fcm_stats['candidate_devices_count'] }} Active FCM Tokens</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="p-3 rounded bg-light border d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 46px; height: 46px; font-size: 1.25rem;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block fw-semibold">Field Worker Devices</small>
                            <span class="fs-5 fw-bold text-dark">{{ $fcm_stats['worker_devices_count'] }} Active FCM Tokens</span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="p-3 rounded bg-light border d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 46px; height: 46px; font-size: 1.25rem;">
                            <i class="bi bi-broadcast"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block fw-semibold">Total Push Endpoints</small>
                            <span class="fs-5 fw-bold text-dark">{{ $fcm_stats['total_active_tokens'] }} Registered</span>
                        </div>
                    </div>
                </div>
            </div>

            @if (!$fcm_config['has_server_key'])
                <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mb-4 border-info">
                    <i class="bi bi-info-circle-fill text-info fs-5 flex-shrink-0"></i>
                    <div>
                        <strong>Simulated Dispatch Mode:</strong> Notification packets are verified, structured, and logged in <code>storage/logs/laravel.log</code> without errors. To dispatch actual push alerts to Android/iOS smartphones, add <code>FIREBASE_SERVER_KEY=your_key</code> in your <code>.env</code> file.
                    </div>
                </div>
            @endif

            <!-- Test Notification Form -->
            <form method="POST" action="{{ route('settings.notification.test') }}" id="testNotificationForm">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold small text-dark">Target Recipient <span class="text-danger">*</span></label>
                        <select name="target_type" id="targetTypeSelect" class="form-select" required onchange="toggleTargetFields()">
                            <option value="broadcast_candidates">Broadcast to ALL Candidates ({{ $fcm_stats['candidate_devices_count'] }} devices)</option>
                            <option value="broadcast_workers">Broadcast to ALL Field Workers ({{ $fcm_stats['worker_devices_count'] }} devices)</option>
                            <option value="candidate">Specific Candidate Device</option>
                            <option value="worker">Specific Field Worker Device</option>
                            <option value="custom_token">Custom FCM Device Token</option>
                        </select>
                    </div>

                    <!-- Specific Candidate Dropdown -->
                    <div class="col-12 col-md-4 d-none" id="candidateSelectGroup">
                        <label class="form-label fw-semibold small text-dark">Select Candidate <span class="text-danger">*</span></label>
                        <select name="candidate_id" class="form-select">
                            <option value="">-- Choose Candidate --</option>
                            @foreach ($candidates as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->name }} ({{ $c->candidate_code }}) &bull; {{ $c->token_count ?? 0 }} active device(s)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Specific Worker Dropdown -->
                    <div class="col-12 col-md-4 d-none" id="workerSelectGroup">
                        <label class="form-label fw-semibold small text-dark">Select Field Worker <span class="text-danger">*</span></label>
                        <select name="worker_id" class="form-select">
                            <option value="">-- Choose Worker --</option>
                            @foreach ($workers as $w)
                                <option value="{{ $w->id }}">
                                    {{ $w->name }} (Block: {{ $w->assigned_block_code ?? 'N/A' }}) &bull; {{ $w->candidate->name ?? 'Candidate' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Custom Token Input -->
                    <div class="col-12 col-md-4 d-none" id="customTokenGroup">
                        <label class="form-label fw-semibold small text-dark">Paste FCM Device Token <span class="text-danger">*</span></label>
                        <input type="text" name="fcm_token" class="form-control font-mono small" placeholder="e.g. dZ8_abc123xyz...">
                    </div>

                    <div class="col-12 col-md-4" id="priorityGroup">
                        <label class="form-label fw-semibold small text-dark">Priority &amp; Alert Style</label>
                        <select name="priority" class="form-select">
                            <option value="high">High Priority (Wake Screen &amp; Sound)</option>
                            <option value="normal">Normal Priority (Background Sync)</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-dark">Notification Title <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-bell"></i></span>
                            <input type="text" name="title" class="form-control" value="🔔 Test Alert from VoterApp Admin" required maxlength="150">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-dark">Notification Message Body <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-chat-left-text"></i></span>
                            <input type="text" name="body" class="form-control" value="Yeh ek test push notification hai. VoterApp system aur device connection bilkul theek kaam kar raha hai." required maxlength="500">
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-warning px-4 py-2 fw-semibold text-dark shadow-sm" onclick="openConfirmNotificationModal()">
                            <i class="bi bi-send-fill me-1"></i> Send Test Notification
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. RECENT BACKUPS STORED ON SERVER -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Server Stored Backups Archive</h6>
            <span class="badge bg-light text-dark border">{{ count($backups) }} Backups Available</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-matrix align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Backup File Name</th>
                        <th>File Size</th>
                        <th>Creation Timestamp</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $b)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-filetype-sql fs-5 text-primary"></i>
                                    <div>
                                        <span class="fw-bold text-dark font-mono">{{ $b['name'] }}</span>
                                        <small class="text-muted d-block">Full SQL Dump</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-mono">{{ $b['size'] }}</span>
                            </td>
                            <td>
                                <span class="small text-muted">{{ $b['date'] }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('settings.backup.file', $b['name']) }}" class="btn btn-sm btn-outline-success me-1" title="Download this backup">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>
                                <form method="POST" action="{{ route('settings.backup.delete', $b['name']) }}" class="d-inline" onsubmit="return confirm('Delete this backup file from server?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete backup file">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                <i class="bi bi-archive fs-3 d-block text-secondary mb-1"></i>
                                No saved backup files yet. Click the <strong>Download Full Database Backup</strong> button above to create one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. DATA CLEANUP / RESET SECTION (COLLAPSIBLE) -->
    <div class="card shadow-sm border-0 border-start border-4 border-danger">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Maintenance &amp; Test Data Wipe</h6>
                <small class="text-muted">Use with extreme caution. Take a backup first before running any purge action.</small>
            </div>
            <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#purgeCollapse">
                Toggle Wipe Tools
            </button>
        </div>
        <div class="collapse" id="purgeCollapse">
            <div class="card-body bg-light">
                <div class="alert alert-warning small mb-3">
                    <i class="bi bi-shield-exclamation me-1"></i> <strong>Important:</strong> Deleting data is irreversible. Please ensure you have clicked the green <strong>Download Full Database Backup</strong> button above before performing any wipe.
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('settings.purge', 'voters') }}" onsubmit="return confirm('WARNING: Are you sure you want to delete ALL voters?');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete All Voters ({{ number_format($counts['voters']) }})</button>
                    </form>

                    <form method="POST" action="{{ route('settings.purge', 'polling-stations') }}" onsubmit="return confirm('WARNING: Delete ALL polling stations?');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete Polling Stations ({{ $counts['stations'] }})</button>
                    </form>

                    <form method="POST" action="{{ route('settings.purge', 'block-codes') }}" onsubmit="return confirm('WARNING: Delete ALL block codes?');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete Block Codes ({{ $counts['blocks'] }})</button>
                    </form>

                    <form method="POST" action="{{ route('settings.purge', 'locations') }}" onsubmit="return confirm('WARNING: Delete ALL locations (UCs, Tehsils, Districts) and their voters?');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete Locations ({{ $counts['ucs'] }} UCs)</button>
                    </form>

                    <form method="POST" action="{{ route('settings.purge', 'search-logs') }}" onsubmit="return confirm('WARNING: Delete ALL search telemetry logs?');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Delete Search Logs ({{ number_format($counts['search_logs'] ?? 0) }})</button>
                    </form>

                    <form method="POST" action="{{ route('settings.purge', 'all') }}" onsubmit="return confirm('CRITICAL ALERT: Are you completely sure you want to PURGE EVERYTHING? This will delete all voters, stations, block codes, and UCs.');">
                        @csrf
                        <input type="hidden" name="confirm" value="1">
                        <button class="btn btn-danger btn-sm"><i class="bi bi-exclamation-octagon-fill me-1"></i> Purge Entire System</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Test Push Notification Modal (UI-matched) -->
    <div class="modal fade" id="confirmTestNotificationModal" tabindex="-1" aria-labelledby="confirmTestNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning-subtle py-3 border-bottom border-warning-subtle">
                    <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="confirmTestNotificationModalLabel">
                        <span class="rounded-circle bg-warning text-dark d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.95rem;">
                            <i class="bi bi-bell-fill"></i>
                        </span>
                        Confirm Push Notification Dispatch
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <div class="rounded-circle bg-warning-subtle text-warning mx-auto d-flex align-items-center justify-content-center mb-2" style="width: 58px; height: 58px; font-size: 1.75rem;">
                            <i class="bi bi-broadcast text-dark"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Send Test Push Notification Now?</h6>
                        <p class="text-muted small mb-0">Yeh test alert Firebase ke zariye targeted devices par instant dispatch hoga.</p>
                    </div>

                    <div class="card bg-light border p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span class="text-muted small fw-semibold">Target Recipient:</span>
                            <span class="badge bg-primary-subtle text-primary fw-bold text-wrap text-end" id="modalPreviewTarget" style="max-width: 260px;">Broadcast</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span class="text-muted small fw-semibold">Priority:</span>
                            <span class="badge bg-secondary-subtle text-secondary" id="modalPreviewPriority">High Priority</span>
                        </div>
                        <div class="py-1 border-bottom">
                            <span class="text-muted small fw-semibold d-block">Title:</span>
                            <strong class="text-dark small" id="modalPreviewTitle">Notification Title</strong>
                        </div>
                        <div class="pt-1">
                            <span class="text-muted small fw-semibold d-block">Message Body:</span>
                            <p class="text-secondary small mb-0 fst-italic" id="modalPreviewBody">Message content preview...</p>
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 px-3 small d-flex align-items-center gap-2 mb-0">
                        <i class="bi bi-info-circle-fill text-dark fs-5 flex-shrink-0"></i>
                        <div>
                            Connected mobile devices par real-time notification show hogi.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-light border px-3 fw-semibold text-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold px-4 shadow-sm" id="btnConfirmSendNotification">
                        <i class="bi bi-send-fill me-1"></i> Send Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Loader & Test Notification Scripts -->
    <script>
        function triggerBackupLoader() {
            if (typeof toastr !== 'undefined') {
                toastr.info('Generating SQL database dump. Your download will start in a few seconds...');
            }
            window.VoterAppLoader?.show('Generating complete database SQL backup dump. Please wait…');
            // Hide overlay after download begins
            setTimeout(function() {
                window.VoterAppLoader?.hide();
            }, 6000);
        }

        function toggleTargetFields() {
            const targetType = document.getElementById('targetTypeSelect')?.value;
            const candGroup = document.getElementById('candidateSelectGroup');
            const workerGroup = document.getElementById('workerSelectGroup');
            const tokenGroup = document.getElementById('customTokenGroup');

            if (candGroup) candGroup.classList.toggle('d-none', targetType !== 'candidate');
            if (workerGroup) workerGroup.classList.toggle('d-none', targetType !== 'worker');
            if (tokenGroup) tokenGroup.classList.toggle('d-none', targetType !== 'custom_token');
        }

        function openConfirmNotificationModal() {
            const form = document.getElementById('testNotificationForm');
            if (!form) return;

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const targetSelect = document.getElementById('targetTypeSelect');
            const targetType = targetSelect ? targetSelect.value : '';
            let targetText = targetSelect ? targetSelect.options[targetSelect.selectedIndex].text : 'Broadcast';

            if (targetType === 'candidate') {
                const candSelect = form.querySelector('select[name="candidate_id"]');
                if (candSelect && candSelect.value) {
                    targetText = candSelect.options[candSelect.selectedIndex].text;
                } else {
                    if (candSelect) candSelect.reportValidity();
                    else form.reportValidity();
                    return;
                }
            } else if (targetType === 'worker') {
                const workerSelect = form.querySelector('select[name="worker_id"]');
                if (workerSelect && workerSelect.value) {
                    targetText = workerSelect.options[workerSelect.selectedIndex].text;
                } else {
                    if (workerSelect) workerSelect.reportValidity();
                    else form.reportValidity();
                    return;
                }
            } else if (targetType === 'custom_token') {
                const tokenInput = form.querySelector('input[name="fcm_token"]');
                if (tokenInput && tokenInput.value.trim()) {
                    targetText = 'Custom Token: ' + tokenInput.value.trim().substring(0, 16) + '...';
                } else {
                    if (tokenInput) tokenInput.reportValidity();
                    else form.reportValidity();
                    return;
                }
            }

            const titleInput = form.querySelector('input[name="title"]');
            const bodyInput = form.querySelector('input[name="body"]');
            const prioritySelect = form.querySelector('select[name="priority"]');

            document.getElementById('modalPreviewTarget').textContent = targetText;
            document.getElementById('modalPreviewPriority').textContent = prioritySelect?.options[prioritySelect.selectedIndex]?.text || 'High Priority';
            document.getElementById('modalPreviewTitle').textContent = titleInput?.value || '(Empty Title)';
            document.getElementById('modalPreviewBody').textContent = bodyInput?.value || '(Empty Body)';

            const modalEl = document.getElementById('confirmTestNotificationModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }

        document.getElementById('btnConfirmSendNotification')?.addEventListener('click', function () {
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sending...';
            document.getElementById('testNotificationForm')?.submit();
        });

        document.addEventListener('DOMContentLoaded', function () {
            toggleTargetFields();
        });
    </script>
@endsection