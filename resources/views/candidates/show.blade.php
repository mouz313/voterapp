@extends('layouts.app')

@section('title', $candidate->name . ' — Operational Performance Matrix')

@section('content')
<div class="container-fluid px-0">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('candidates.index') }}" class="text-decoration-none">Candidates</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">{{ $candidate->name }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('candidates.report', $candidate) }}" class="btn btn-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-file-earmark-pdf"></i>
                <span>Executive Performance Report</span>
            </a>
            <a href="{{ route('candidates.report', ['candidate' => $candidate->id, 'download' => 'pdf']) }}" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-download"></i>
                <span>Direct PDF</span>
            </a>
            <a href="{{ route('candidates.devices', $candidate) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-phone"></i>
                <span>Devices ({{ $activeDevices }} Active &bull; Unlimited)</span>
            </a>
            <a href="{{ route('candidates.edit', $candidate) }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-pencil-square"></i>
                <span>Edit</span>
            </a>
            <a href="{{ route('candidates.index') }}" class="btn btn-light btn-sm border shadow-sm">
                <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    </div>

    <!-- Candidate Profile & Branding Header -->
    <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <div class="card-body p-4 bg-white">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    @if ($candidate->candidate_image && file_exists(public_path($candidate->candidate_image)))
                        <img src="{{ asset($candidate->candidate_image) }}" alt="{{ $candidate->name }}" class="rounded-circle shadow border border-3 border-light" style="width: 84px; height: 84px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center border border-3 border-light shadow" style="width: 84px; height: 84px; font-size: 2rem;">
                            {{ strtoupper(substr($candidate->name, 0, 2)) }}
                        </div>
                    @endif
                </div>

                <div class="col">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h4 class="fw-bold mb-0 text-dark">{{ $candidate->name }}</h4>
                        @if ($candidate->status === 'active')
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Active License</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-slash-circle me-1"></i>Suspended</span>
                        @endif

                        @if ($candidate->is_independent)
                            <span class="badge bg-dark-subtle text-dark border px-2 py-1"><i class="bi bi-person me-1"></i>Independent (آزاد امیدوار)</span>
                        @elseif ($candidate->party_name)
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-flag-fill me-1"></i>{{ $candidate->party_name }}</span>
                        @endif

                        @if ($candidate->expires_at)
                            @php
                                $daysLeft = now()->diffInDays($candidate->expires_at, false);
                            @endphp
                            @if ($daysLeft > 7)
                                <span class="badge bg-light text-muted border font-mono"><i class="bi bi-calendar-check me-1"></i>Valid till {{ $candidate->expires_at->format('d M Y') }} ({{ $daysLeft }}d left)</span>
                            @elseif ($daysLeft >= 0)
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-mono"><i class="bi bi-exclamation-triangle me-1"></i>Expiring soon: {{ $candidate->expires_at->format('d M Y') }}</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-mono"><i class="bi bi-x-circle me-1"></i>Expired {{ abs($daysLeft) }}d ago</span>
                            @endif
                        @endif
                    </div>

                    <div class="text-muted small d-flex flex-wrap gap-3 mt-2">
                        <span><i class="bi bi-envelope me-1 text-primary"></i>{{ $candidate->email }}</span>
                        @if ($candidate->phone)
                            <span><i class="bi bi-telephone me-1 text-success"></i>{{ $candidate->phone }}</span>
                        @endif
                        <span><i class="bi bi-geo-alt me-1 text-danger"></i>UC: <strong class="text-dark">{{ $candidate->uc ? ($candidate->uc->name . ' (UC #' . ($candidate->uc->uc_no ?: $candidate->uc->id) . ')') : 'Unassigned' }}</strong></span>
                        <span><i class="bi bi-diagram-3 me-1 text-info"></i>Tehsil: <strong class="text-dark">{{ $candidate->uc?->tehsil?->name ?? 'N/A' }}</strong> ({{ $candidate->uc?->tehsil?->district?->name ?? 'N/A' }})</span>
                    </div>
                </div>

                <!-- Party & Symbol Branding Column -->
                <div class="col-auto d-flex align-items-center gap-3 border-start ps-4">
                    @if ($candidate->candidate_symbol_image && file_exists(public_path($candidate->candidate_symbol_image)))
                        <div class="text-center">
                            <div class="small text-muted mb-1" style="font-size: 0.72rem;">ELECTION SYMBOL</div>
                            <img src="{{ asset($candidate->candidate_symbol_image) }}" alt="Symbol" class="rounded border p-1 bg-white shadow-sm" style="width: 52px; height: 52px; object-fit: contain;">
                            @if ($candidate->candidate_symbol)
                                <div class="badge bg-light text-dark border mt-1 d-block font-mono" style="font-size: 0.7rem;">{{ $candidate->candidate_symbol }}</div>
                            @endif
                        </div>
                    @elseif ($candidate->candidate_symbol)
                        <div class="text-center">
                            <div class="small text-muted mb-1" style="font-size: 0.72rem;">ELECTION SYMBOL</div>
                            <span class="badge bg-light text-dark border fs-6 p-2 shadow-sm font-mono">{{ $candidate->candidate_symbol }}</span>
                        </div>
                    @endif

                    @if ($candidate->party_logo && file_exists(public_path($candidate->party_logo)))
                        <div class="text-center">
                            <div class="small text-muted mb-1" style="font-size: 0.72rem;">PARTY CREST</div>
                            <img src="{{ asset($candidate->party_logo) }}" alt="Party Logo" class="rounded border p-1 bg-white shadow-sm" style="width: 52px; height: 52px; object-fit: contain;">
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Operational KPI Metrics Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. UC Registered Voters -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">UC Total Voters</span>
                        <div class="rounded p-2 bg-primary-subtle text-primary">
                            <i class="bi bi-people-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="fs-4 fw-bold text-dark font-mono">{{ number_format($totalUcVoters) }}</div>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-geo-alt me-1"></i>{{ $candidate->uc ? $candidate->uc->name : 'N/A' }} Voter Base
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Field Searches Processed -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Total Searches Executed</span>
                        <div class="rounded p-2 bg-success-subtle text-success">
                            <i class="bi bi-search fs-5"></i>
                        </div>
                    </div>
                    <div class="fs-4 fw-bold text-success font-mono">{{ number_format($totalSearches) }}</div>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-speedometer2 me-1"></i>Field Query Sync Total
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Active Field Devices -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Field Devices</span>
                        <div class="rounded p-2 bg-info-subtle text-info">
                            <i class="bi bi-phone-fill fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="fs-4 fw-bold text-dark font-mono">{{ $activeDevices }}</div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle font-mono">Active (Unlimited)</span>
                    </div>
                    <div class="small text-muted mt-2 d-flex justify-content-between">
                        <span>{{ $registeredDevices }} Registered</span>
                        <span class="text-success fw-semibold"><i class="bi bi-infinity me-1"></i>No Device Cap</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Estimated Voter Reach -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Voter Reach / Penetration</span>
                        <div class="rounded p-2 bg-warning-subtle text-warning">
                            <i class="bi bi-graph-up-arrow fs-5"></i>
                        </div>
                    </div>
                    <div class="fs-4 fw-bold text-dark font-mono">{{ $reachPct }}%</div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $reachPct }}%;" aria-valuenow="{{ $reachPct }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="small text-muted mt-1">
                        Ratio of queries to total UC voters
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Performance & Delimitation Grid -->
    <div class="row g-3 mb-4">
        <!-- Search Query Distribution Matrix -->
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Field Search Query Breakdown
                    </h6>
                    <span class="badge bg-light text-dark border font-mono">{{ number_format($totalSearches) }} Total</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3 mb-3">
                        <!-- CNIC Queries -->
                        <div class="col-6 col-sm-3">
                            <div class="p-3 rounded bg-light border text-center h-100">
                                <i class="bi bi-person-vcard text-primary fs-4 d-block mb-1"></i>
                                <div class="text-muted small">CNIC Lookup</div>
                                <div class="fs-5 fw-bold text-dark font-mono mt-1">{{ number_format($cnicCount) }}</div>
                                <span class="badge bg-primary-subtle text-primary font-mono mt-1" style="font-size: 0.7rem;">
                                    {{ $totalSearches > 0 ? round(($cnicCount / $totalSearches) * 100) : 0 }}%
                                </span>
                            </div>
                        </div>

                        <!-- Name Queries -->
                        <div class="col-6 col-sm-3">
                            <div class="p-3 rounded bg-light border text-center h-100">
                                <i class="bi bi-fonts text-success fs-4 d-block mb-1"></i>
                                <div class="text-muted small">Name Lookup</div>
                                <div class="fs-5 fw-bold text-dark font-mono mt-1">{{ number_format($nameCount) }}</div>
                                <span class="badge bg-success-subtle text-success font-mono mt-1" style="font-size: 0.7rem;">
                                    {{ $totalSearches > 0 ? round(($nameCount / $totalSearches) * 100) : 0 }}%
                                </span>
                            </div>
                        </div>

                        <!-- Gharana No Queries -->
                        <div class="col-6 col-sm-3">
                            <div class="p-3 rounded bg-light border text-center h-100">
                                <i class="bi bi-house-door text-warning fs-4 d-block mb-1"></i>
                                <div class="text-muted small">Gharana No</div>
                                <div class="fs-5 fw-bold text-dark font-mono mt-1">{{ number_format($gharanaCount) }}</div>
                                <span class="badge bg-warning-subtle text-warning font-mono mt-1" style="font-size: 0.7rem;">
                                    {{ $totalSearches > 0 ? round(($gharanaCount / $totalSearches) * 100) : 0 }}%
                                </span>
                            </div>
                        </div>

                        <!-- Silsala No Queries -->
                        <div class="col-6 col-sm-3">
                            <div class="p-3 rounded bg-light border text-center h-100">
                                <i class="bi bi-hash text-danger fs-4 d-block mb-1"></i>
                                <div class="text-muted small">Silsala No</div>
                                <div class="fs-5 fw-bold text-dark font-mono mt-1">{{ number_format($silsalaCount) }}</div>
                                <span class="badge bg-danger-subtle text-danger font-mono mt-1" style="font-size: 0.7rem;">
                                    {{ $totalSearches > 0 ? round(($silsalaCount / $totalSearches) * 100) : 0 }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress bar comparison -->
                    <div class="mb-2">
                        <div class="small text-muted mb-1 d-flex justify-content-between">
                            <span>Query Type Proportion</span>
                            <span>Distribution Analysis</span>
                        </div>
                        <div class="progress" style="height: 12px;">
                            @php
                                $total = max(1, $totalSearches);
                                $cnicP = round(($cnicCount / $total) * 100);
                                $nameP = round(($nameCount / $total) * 100);
                                $gharanaP = round(($gharanaCount / $total) * 100);
                                $silsalaP = round(($silsalaCount / $total) * 100);
                            @endphp
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $cnicP }}%;" title="CNIC: {{ $cnicP }}%"></div>
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $nameP }}%;" title="Name: {{ $nameP }}%"></div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $gharanaP }}%;" title="Gharana: {{ $gharanaP }}%"></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $silsalaP }}%;" title="Silsala: {{ $silsalaP }}%"></div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-3 text-muted small mt-2">
                        <span><span class="badge bg-primary me-1">&nbsp;</span> CNIC ({{ $cnicP }}%)</span>
                        <span><span class="badge bg-success me-1">&nbsp;</span> Name ({{ $nameP }}%)</span>
                        <span><span class="badge bg-warning me-1">&nbsp;</span> Gharana ({{ $gharanaP }}%)</span>
                        <span><span class="badge bg-danger me-1">&nbsp;</span> Silsala ({{ $silsalaP }}%)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Electoral Delimitation & Infrastructure -->
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-compass-fill me-2 text-success"></i>Electoral Demographics & Territory
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="text-muted"><i class="bi bi-grid-1x2 me-2 text-primary"></i>Assigned Union Council</span>
                            <span class="fw-bold text-dark">{{ $candidate->uc ? ($candidate->uc->name . ' (UC #' . ($candidate->uc->uc_no ?: $candidate->uc->id) . ')') : 'Unassigned' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="text-muted"><i class="bi bi-diagram-3 me-2 text-info"></i>Tehsil / Sub-Division</span>
                            <span class="fw-semibold">{{ $candidate->uc?->tehsil?->name ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="text-muted"><i class="bi bi-geo-alt me-2 text-danger"></i>District</span>
                            <span class="fw-semibold">{{ $candidate->uc?->tehsil?->district?->name ?? 'N/A' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="text-muted"><i class="bi bi-flag me-2 text-primary"></i>National Assembly Seat</span>
                            <span class="badge bg-primary-subtle text-primary font-mono fw-bold">
                                {{ $candidate->uc?->nationalAssembly?->code ?? 'N/A' }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="text-muted"><i class="bi bi-diagram-2 me-2 text-success"></i>Provincial Assembly Seat</span>
                            <span class="badge bg-success-subtle text-success font-mono fw-bold">
                                {{ $candidate->uc?->provincialAssembly?->code ?? 'N/A' }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="text-muted"><i class="bi bi-house-door me-2 text-warning"></i>Total Polling Stations</span>
                            <span class="badge bg-light text-dark border font-mono">{{ $totalPollingStations }} Stations</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="text-muted"><i class="bi bi-collection me-2 text-secondary"></i>Census Block Codes</span>
                            <span class="badge bg-light text-dark border font-mono">{{ $totalBlockCodes }} Blocks</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Field Devices & Agent Activity Table -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-phone-vibrate me-1 text-primary"></i>Candidate Field Devices & Operator Telemetry
                </h6>
                <span class="badge bg-primary-subtle text-primary font-mono">{{ $candidate->devices->count() }} Registered</span>
            </div>
            <a href="{{ route('candidates.devices', $candidate) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-gear me-1"></i>Full Device Management
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-matrix align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Device Operator / Model</th>
                            <th>Device UID</th>
                            <th>OS / App Ver</th>
                            <th>Queries Processed</th>
                            <th>Last Active</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($candidate->devices as $dev)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded p-2 bg-light border text-muted">
                                            @if (str_contains(strtolower($dev->platform ?? ''), 'ios'))
                                                <i class="bi bi-apple"></i>
                                            @else
                                                <i class="bi bi-android2 text-success"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $dev->device_name ?: 'Android Handset' }}</div>
                                            <small class="text-muted">{{ $dev->device_model ?: 'Handheld Terminal' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-mono" style="font-size: 0.72rem;">
                                        {{ Str::limit($dev->device_uid, 18) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-dark text-capitalize font-mono">
                                        {{ $dev->platform ?: 'Android' }}
                                    </span>
                                    @if ($dev->app_version)
                                        <small class="text-muted d-block font-mono" style="font-size: 0.7rem;">v{{ $dev->app_version }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success font-mono fw-bold">
                                        <i class="bi bi-search me-1"></i>{{ number_format($dev->searches_count ?? 0) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($dev->last_active_at)
                                        <div class="small fw-semibold text-dark">{{ $dev->last_active_at->diffForHumans() }}</div>
                                        <small class="text-muted font-mono" style="font-size: 0.7rem;">{{ $dev->ip_address ?: 'Mobile Data' }}</small>
                                    @else
                                        <small class="text-muted">Never</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($dev->is_revoked)
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-x-circle me-1"></i>Revoked
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('candidates.devices.toggle', $dev) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $dev->is_revoked ? 'btn-outline-success' : 'btn-outline-danger' }} py-0 px-2" title="{{ $dev->is_revoked ? 'Unblock Device' : 'Revoke Device' }}">
                                            <i class="bi {{ $dev->is_revoked ? 'bi-shield-check' : 'bi-shield-slash' }}"></i>
                                            <span class="small">{{ $dev->is_revoked ? 'Unblock' : 'Revoke' }}</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-phone text-secondary fs-3 d-block mb-1"></i>
                                    No mobile devices have been paired with this candidate account yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
