@extends('layouts.app')

@section('title', 'Election Command Matrix')

@section('content')
    <!-- Dashboard Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Election Command & Operations Matrix</h4>
            <small class="text-muted">Real-time status of Electoral Delimitations, Candidate App Subscriptions, Active Mobile Devices & Voter Turnout</small>
        </div>
        <div class="d-flex items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                <i class="bi bi-broadcast me-1"></i> Live System Online
            </span>
            <span class="badge bg-light text-dark border px-2 py-1">
                <i class="bi bi-clock me-1"></i> {{ date('d M Y, h:i A') }}
            </span>
        </div>
    </div>

    <!-- 1. Top Electoral KPIs Matrix -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-3 h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="stat-label">Total Voters</span>
                    <div class="stat-icon-wrap bg-success-subtle text-success"><i class="bi bi-people-fill"></i></div>
                </div>
                <div class="stat-value text-dark">{{ number_format($stats['voters']) }}</div>
                <small class="text-muted">Across all imported UCs</small>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-3 h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="stat-label">Union Councils</span>
                    <div class="stat-icon-wrap bg-primary-subtle text-primary"><i class="bi bi-grid-1x2-fill"></i></div>
                </div>
                <div class="stat-value text-dark">{{ $stats['ucs'] }} <small class="fs-6 text-muted">UCs</small></div>
                <small class="text-muted">{{ $stats['tehsils'] }} Tehsils Scoped</small>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-3 h-100 border-start border-4 border-dark">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="stat-label">Census Blocks</span>
                    <div class="stat-icon-wrap bg-secondary-subtle text-dark"><i class="bi bi-collection-fill"></i></div>
                </div>
                <div class="stat-value text-dark">{{ number_format($stats['block_codes']) }}</div>
                <small class="text-muted">ECP Census Block Codes</small>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-3 h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="stat-label">Polling Stations</span>
                    <div class="stat-icon-wrap bg-info-subtle text-info"><i class="bi bi-house-door-fill"></i></div>
                </div>
                <div class="stat-value text-dark">{{ $stats['polling_stations'] }}</div>
                <small class="text-muted">Booth Locations</small>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-3 h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="stat-label">Active Candidates</span>
                    <div class="stat-icon-wrap bg-warning-subtle text-warning"><i class="bi bi-person-badge-fill"></i></div>
                </div>
                <div class="stat-value text-dark">{{ $stats['candidates'] }}</div>
                <small class="text-muted">Licensed UC Accounts</small>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-3 h-100 border-start border-4 border-emerald" style="border-left-color: #10b981 !important;">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="stat-label">Active Devices</span>
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
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold text-dark"><i class="bi bi-diagram-3 me-1 text-success"></i> Tehsil Delimitation & Voter Coverage Matrix</span>
                    <a href="{{ route('tehsils.index') }}" class="small text-decoration-none">All Tehsils &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-matrix align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Tehsil</th>
                                <th>District</th>
                                <th class="text-center">Total UCs</th>
                                <th>Block Codes Status</th>
                                <th>Voters Registered</th>
                                <th>Coverage Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tehsilMatrix as $tm)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $tm['name'] }}</td>
                                    <td><small class="text-muted">{{ $tm['district'] }}</small></td>
                                    <td class="text-center">
                                        <a href="{{ route('ucs.index', ['tehsil_id' => $tm['id']]) }}" class="badge bg-light text-dark border text-decoration-none">
                                            {{ $tm['ucs_count'] }} UCs
                                        </a>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <span class="text-success fw-semibold">{{ $tm['blocks_with_data'] }}</span> / {{ $tm['total_blocks'] }} blocks
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark">{{ number_format($tm['total_voters']) }}</span>
                                    </td>
                                    <td style="min-width: 140px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="matrix-progress flex-grow-1">
                                                <div class="bg-success h-100" style="width: {{ $tm['coverage_pct'] }}%"></div>
                                            </div>
                                            <span class="small fw-bold {{ $tm['coverage_pct'] == 100 ? 'text-success' : 'text-muted' }}">
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
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold text-dark"><i class="bi bi-person-badge me-1 text-primary"></i> Candidate App Quotas (Max 20 Devices)</span>
                    <a href="{{ route('candidates.index') }}" class="small text-decoration-none">Manage All &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-matrix align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Candidate / Account</th>
                                <th>Assigned UC</th>
                                <th>Device Quota</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($candidateMatrix as $cm)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $cm['name'] }}</div>
                                        <small class="text-muted d-block">{{ $cm['email'] }}</small>
                                    </td>
                                    <td>
                                        <small class="text-dark fw-semibold">{{ $cm['uc_name'] }}</small>
                                    </td>
                                    <td style="min-width: 120px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="matrix-progress flex-grow-1">
                                                @php
                                                    $barColor = $cm['utilization_pct'] >= 100 ? 'bg-danger' : ($cm['utilization_pct'] >= 75 ? 'bg-warning' : 'bg-success');
                                                @endphp
                                                <div class="{{ $barColor }} h-100" style="width: {{ min(100, $cm['utilization_pct']) }}%"></div>
                                            </div>
                                            <span class="badge bg-light text-dark border font-mono">
                                                {{ $cm['active_devices'] }}/{{ $cm['max_devices'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('candidates.devices', $cm['id']) }}" class="btn btn-sm btn-outline-primary" title="View Active Devices">
                                            <i class="bi bi-phone"></i>
                                        </a>
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
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold text-dark"><i class="bi bi-search me-1 text-info"></i> Live Field Search Telemetry & Query Breakdown</span>
                    <span class="badge bg-primary text-white font-mono">{{ number_format($stats['total_searches']) }} Total Queries Logged</span>
                </div>
                <div class="card-body p-3">
                    <!-- Query Distribution Pills -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <div class="p-2 rounded bg-light border flex-grow-1">
                            <div class="small text-muted">CNIC Searches</div>
                            <div class="fs-6 fw-bold text-dark">{{ $searchBreakdown['cnic'] ?? 0 }}</div>
                        </div>
                        <div class="p-2 rounded bg-light border flex-grow-1">
                            <div class="small text-muted">Name Searches</div>
                            <div class="fs-6 fw-bold text-dark">{{ $searchBreakdown['name'] ?? 0 }}</div>
                        </div>
                        <div class="p-2 rounded bg-light border flex-grow-1">
                            <div class="small text-muted">Gharana No Queries</div>
                            <div class="fs-6 fw-bold text-dark">{{ $searchBreakdown['gharana'] ?? 0 }}</div>
                        </div>
                        <div class="p-2 rounded bg-light border flex-grow-1">
                            <div class="small text-muted">Silsala No Queries</div>
                            <div class="fs-6 fw-bold text-dark">{{ $searchBreakdown['silsala'] ?? 0 }}</div>
                        </div>
                    </div>

                    <!-- Recent Searches Table -->
                    <div class="table-responsive">
                        <table class="table table-matrix align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Candidate / User</th>
                                    <th>Union Council</th>
                                    <th>Query Type</th>
                                    <th>Results Returned</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentSearches as $rs)
                                    <tr>
                                        <td><small class="text-muted">{{ $rs->searched_at ? $rs->searched_at->diffForHumans() : '-' }}</small></td>
                                        <td class="fw-semibold">{{ $rs->user->name ?? 'Mobile Agent' }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $rs->uc->name ?? 'General' }}</span></td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-dark text-uppercase font-mono">{{ $rs->query_type }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success">{{ $rs->results_count }} Voters</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-2">No recent field search logs recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Electoral Assemblies & Polling Infrastructure -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2 fw-bold text-dark">
                    <i class="bi bi-shield-shaded me-1 text-success"></i> Constituencies & Infrastructure
                </div>
                <div class="card-body p-3">
                    <div class="list-group list-group-flush small">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span><i class="bi bi-flag me-2 text-primary"></i>National Assembly (NA) Seats</span>
                            <span class="badge bg-primary fs-6">{{ $stats['national_assemblies'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span><i class="bi bi-diagram-2 me-2 text-success"></i>Provincial Assembly (PP/PS/PK) Seats</span>
                            <span class="badge bg-success fs-6">{{ $stats['provincial_assemblies'] }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span><i class="bi bi-building me-2 text-secondary"></i>Districts Registered</span>
                            <span class="badge bg-dark">{{ \App\Models\District::count() }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span><i class="bi bi-geo-alt-fill me-2 text-info"></i>Voters Assigned to Polling Booths</span>
                            <span class="badge bg-success font-mono">{{ number_format($pollingStats['assigned_voters']) }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span><i class="bi bi-collection-fill me-2 text-warning"></i>Voters Mapped to Block Codes</span>
                            <span class="badge bg-primary font-mono">{{ number_format($pollingStats['voters_with_block']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
