@extends('layouts.app')

@section('title', 'Election Command Matrix')

@section('content')
    <!-- Dashboard Header -->
    <div class="d-flex align-items-start align-items-sm-center justify-content-between flex-column flex-sm-row gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Election Command &amp; Operations Matrix</h4>
            <small class="text-muted d-block mt-0.5">Real-time status of Electoral Delimitations, Candidate App Subscriptions, Active Mobile Devices &amp; Voter Turnout</small>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                <i class="bi bi-broadcast me-1"></i> Live System Online
            </span>
            <span class="badge bg-light text-dark border px-2 py-1">
                <i class="bi bi-clock me-1"></i> {{ date('d M Y, h:i A') }}
            </span>
        </div>
    </div>

    <!-- 1. Top Electoral KPIs Matrix -->
    <div class="row g-2 g-sm-3 mb-3">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Total Voters</span>
                    <div class="stat-icon-wrap bg-success-subtle text-success"><i class="bi bi-people-fill"></i></div>
                </div>
                <div class="stat-value text-dark">{{ number_format($stats['voters']) }}</div>
                <small class="text-muted">Across all imported UCs</small>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Union Councils</span>
                    <div class="stat-icon-wrap bg-primary-subtle text-primary"><i class="bi bi-grid-1x2-fill"></i></div>
                </div>
                <div class="stat-value text-dark">{{ $stats['ucs'] }} <small class="fs-6 text-muted">UCs</small></div>
                <small class="text-muted">{{ $stats['tehsils'] }} Tehsils Scoped</small>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-dark">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Census Blocks</span>
                    <div class="stat-icon-wrap bg-secondary-subtle text-dark"><i class="bi bi-collection-fill"></i></div>
                </div>
                <div class="stat-value text-dark">{{ number_format($stats['block_codes']) }}</div>
                <small class="text-muted">ECP Census Block Codes</small>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Polling Stations</span>
                    <div class="stat-icon-wrap bg-info-subtle text-info"><i class="bi bi-house-door-fill"></i></div>
                </div>
                <div class="stat-value text-dark">{{ $stats['polling_stations'] }}</div>
                <small class="text-muted">Booth Locations</small>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Candidates</span>
                    <div class="stat-icon-wrap bg-warning-subtle text-warning"><i class="bi bi-person-badge-fill"></i></div>
                </div>
                <div class="stat-value text-dark">{{ $stats['candidates'] }}</div>
                <small class="text-muted">Licensed UC Accounts</small>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-emerald" style="border-left-color: #10b981 !important;">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Active Devices</span>
                    <div class="stat-icon-wrap bg-success text-white"><i class="bi bi-phone"></i></div>
                </div>
                <div class="stat-value text-success">{{ $stats['active_devices'] }}</div>
                <small class="text-muted">Live Polling Phones</small>
            </div>
        </div>
    </div>

    <!-- 2. Tehsil Progress & Candidate License Matrices -->
    <div class="row g-3 mb-3">
        <!-- Tehsil & Delimitation Coverage Matrix -->
        <div class="col-12 col-xl-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <span class="fw-bold text-dark text-truncate">
                        <i class="bi bi-diagram-3 me-1 text-success"></i> Tehsil Delimitation &amp; Voter Coverage
                    </span>
                    <a href="{{ route('tehsils.index') }}" class="small text-decoration-none flex-shrink-0">All Tehsils &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-matrix align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tehsil</th>
                                <th>District</th>
                                <th class="text-center text-nowrap">Total UCs</th>
                                <th class="text-nowrap">Block Codes Status</th>
                                <th class="text-nowrap">Voters Registered</th>
                                <th class="text-nowrap">Coverage Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tehsilMatrix as $tm)
                                <tr>
                                    <td class="fw-bold text-dark text-nowrap">{{ $tm['name'] }}</td>
                                    <td><small class="text-muted text-nowrap">{{ $tm['district'] }}</small></td>
                                    <td class="text-center">
                                        <a href="{{ route('ucs.index', ['tehsil_id' => $tm['id']]) }}" class="badge bg-light text-dark border text-decoration-none">
                                            {{ $tm['ucs_count'] }} UCs
                                        </a>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="small">
                                            <span class="text-success fw-semibold">{{ $tm['blocks_with_data'] }}</span> / {{ $tm['total_blocks'] }} blocks
                                        </div>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="fw-bold text-dark font-mono">{{ number_format($tm['total_voters']) }}</span>
                                    </td>
                                    <td style="min-width: 120px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="matrix-progress flex-grow-1">
                                                <div class="bg-success h-100" style="width: {{ $tm['coverage_pct'] }}%"></div>
                                            </div>
                                            <span class="small fw-bold {{ $tm['coverage_pct'] == 100 ? 'text-success' : 'text-muted' }} font-mono">
                                                {{ $tm['coverage_pct'] }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No Tehsils configured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Candidate License & Device Quota Matrix -->
        <div class="col-12 col-xl-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <span class="fw-bold text-dark text-truncate">
                        <i class="bi bi-person-badge me-1 text-primary"></i> Candidate Connected Handsets
                    </span>
                    <a href="{{ route('candidates.index') }}" class="small text-decoration-none flex-shrink-0">Manage All &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-matrix align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Candidate</th>
                                <th>Assigned UC</th>
                                <th class="text-nowrap">Handsets</th>
                                <th class="text-end text-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($candidateMatrix as $cm)
                                <tr>
                                    <td>
                                        <a href="{{ route('candidates.show', $cm['id']) }}" class="fw-bold text-dark text-decoration-none hover-primary text-truncate d-block" style="max-width: 160px;">
                                            {{ $cm['name'] }}
                                        </a>
                                        <small class="text-muted d-block text-truncate" style="max-width: 160px;">{{ $cm['email'] }}</small>
                                    </td>
                                    <td>
                                        <small class="text-dark fw-semibold text-truncate d-block" style="max-width: 150px;">{{ $cm['uc_name'] }}</small>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle font-mono px-2 py-1">
                                            <i class="bi bi-phone me-1"></i>{{ $cm['active_devices'] }} <span class="d-none d-sm-inline">Active</span>
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <div class="d-inline-flex gap-1">
                                            <a href="{{ route('candidates.show', $cm['id']) }}" class="btn btn-sm btn-outline-primary py-0.5 px-2" title="Performance Matrix">
                                                <i class="bi bi-speedometer2"></i>
                                            </a>
                                            <a href="{{ route('candidates.devices', $cm['id']) }}" class="btn btn-sm btn-outline-secondary py-0.5 px-2" title="View Active Devices">
                                                <i class="bi bi-phone"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No active candidate accounts registered yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Field Search Telemetry & Infrastructure Matrix -->
    <div class="row g-3">
        <!-- Live Search Activity & Field Telemetry -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100" id="live-search-telemetry-card">
                <div class="card-header bg-white d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 py-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-bold text-dark text-truncate">
                            <i class="bi bi-search me-1 text-info"></i> Live Field Search Telemetry
                        </span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1" id="telemetry-live-indicator" style="font-size: 0.72rem;">
                            <span class="spinner-grow spinner-grow-sm text-success" style="width: 0.45rem; height: 0.45rem;" role="status"></span>
                            <span>Live Sync</span>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-auto ms-sm-0">
                        <span class="badge bg-primary text-white font-mono" id="total-searches-badge">
                            {{ number_format($stats['total_searches']) }} Queries
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" id="refresh-telemetry-btn" title="Refresh Telemetry">
                            <i class="bi bi-arrow-clockwise" id="refresh-icon"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-3" id="search-telemetry-container">
                    @include('dashboard.partials.search_telemetry')
                </div>
                <div class="card-footer bg-light py-1.5 px-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-1 text-muted" style="font-size: 0.75rem;">
                    <span><i class="bi bi-arrow-repeat me-1"></i>Auto-sync every 8s</span>
                    <span>Last updated: <span id="telemetry-last-sync" class="fw-semibold">{{ now()->format('h:i:s A') }}</span></span>
                </div>
            </div>
        </div>

        <!-- Electoral Assemblies & Polling Infrastructure -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2 fw-bold text-dark text-truncate">
                    <i class="bi bi-shield-shaded me-1 text-success"></i> Constituencies &amp; Infrastructure
                </div>
                <div class="card-body p-3">
                    <div class="list-group list-group-flush small">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 gap-2">
                            <span class="text-truncate"><i class="bi bi-flag me-2 text-primary"></i>National Assembly (NA) Seats</span>
                            <span class="badge bg-primary fs-6 flex-shrink-0 font-mono">{{ $stats['national_assemblies'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 gap-2">
                            <span class="text-truncate"><i class="bi bi-diagram-2 me-2 text-success"></i>Provincial Assembly Seats</span>
                            <span class="badge bg-success fs-6 flex-shrink-0 font-mono">{{ $stats['provincial_assemblies'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 gap-2">
                            <span class="text-truncate"><i class="bi bi-building me-2 text-secondary"></i>Districts Registered</span>
                            <span class="badge bg-dark flex-shrink-0 font-mono">{{ \App\Models\District::count() }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 gap-2">
                            <span class="text-truncate"><i class="bi bi-geo-alt-fill me-2 text-info"></i>Voters with Polling Booths</span>
                            <span class="badge bg-success font-mono flex-shrink-0">{{ number_format($pollingStats['assigned_voters']) }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 gap-2">
                            <span class="text-truncate"><i class="bi bi-collection-fill me-2 text-warning"></i>Voters with Block Codes</span>
                            <span class="badge bg-primary font-mono flex-shrink-0">{{ number_format($pollingStats['voters_with_block']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const telemetryUrl = "{{ route('dashboard.search-telemetry') }}";
        const container = document.getElementById('search-telemetry-container');
        const totalBadge = document.getElementById('total-searches-badge');
        const lastSyncSpan = document.getElementById('telemetry-last-sync');
        const refreshBtn = document.getElementById('refresh-telemetry-btn');
        const refreshIcon = document.getElementById('refresh-icon');

        let isFetching = false;

        async function fetchTelemetry() {
            if (isFetching) return;
            isFetching = true;
            if (refreshIcon) refreshIcon.classList.add('spin-animation');

            try {
                const response = await fetch(telemetryUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.html && container) {
                        container.innerHTML = data.html;
                    }
                    if (data.total_searches && totalBadge) {
                        totalBadge.textContent = data.total_searches + ' Queries';
                    }
                    if (data.timestamp && lastSyncSpan) {
                        lastSyncSpan.textContent = data.timestamp;
                    }
                }
            } catch (err) {
                console.error('Failed to sync search telemetry:', err);
            } finally {
                isFetching = false;
                if (refreshIcon) refreshIcon.classList.remove('spin-animation');
            }
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                fetchTelemetry();
            });
        }

        // Auto-refresh every 8 seconds
        setInterval(fetchTelemetry, 8000);
    });
    </script>
    <style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .spin-animation {
        display: inline-block;
        animation: spin 0.8s linear infinite;
    }
    </style>
    @endpush
@endsection
