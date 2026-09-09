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
            <button type="button" class="btn btn-outline-warning text-dark btn-sm d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                <i class="bi bi-key-fill text-warning"></i>
                <span>Reset Password</span>
            </button>
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

                        @if ($candidate->candidate_code)
                            <span class="badge bg-dark text-white font-monospace px-2 py-1" style="letter-spacing: 0.5px;">
                                <i class="bi bi-key-fill text-warning me-1"></i>Campaign Code: {{ $candidate->candidate_code }}
                            </span>
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

                    @if ($candidate->party_slogan)
                        <div class="small fw-semibold text-secondary fst-italic mt-1">
                            <i class="bi bi-quote text-primary me-1"></i>{{ $candidate->party_slogan }}
                        </div>
                    @endif

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
                    @if ($candidate->leader_image && file_exists(public_path($candidate->leader_image)))
                        <div class="text-center">
                            <div class="small text-muted mb-1" style="font-size: 0.72rem;">SUPREME LEADER</div>
                            <img src="{{ asset($candidate->leader_image) }}" alt="Leader" class="rounded-circle border p-1 bg-white shadow-sm" style="width: 52px; height: 52px; object-fit: cover;">
                        </div>
                    @endif

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

    @if ($candidate->sale)
        <div class="card shadow-sm border-0 mb-4 bg-white">
            <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle p-2 bg-primary-subtle text-primary">
                        <i class="bi bi-receipt-cutoff fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">App Licensing & Commercial Sale Record</div>
                        <div class="small text-muted">
                            Channel / Party: <strong class="text-dark">{{ $candidate->sale->party->name ?? 'Direct Sale' }}</strong>
                            &bull; License Price: <strong class="text-dark font-mono">PKR {{ number_format($candidate->sale->sale_amount) }}</strong>
                            &bull; Paid: <strong class="text-success font-mono">PKR {{ number_format($candidate->sale->amount_paid) }}</strong>
                            @php $bal = $candidate->sale->sale_amount - $candidate->sale->amount_paid; @endphp
                            @if($bal > 0)
                                &bull; Remaining Balance: <strong class="text-danger font-mono">PKR {{ number_format($bal) }}</strong>
                            @endif
                            @if($candidate->sale->payment_date)
                                &bull; Date: <span class="text-secondary font-mono">{{ $candidate->sale->payment_date->format('d M Y') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($candidate->sale->payment_status === 'paid')
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i>Fully Paid</span>
                    @elseif($candidate->sale->payment_status === 'partial')
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2"><i class="bi bi-clock-history me-1"></i>Partial Payment</span>
                    @else
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2"><i class="bi bi-exclamation-circle-fill me-1"></i>Payment Pending</span>
                    @endif
                    <a href="{{ route('finance.sales') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-shield-lock me-1"></i>Finance Vault</a>
                </div>
            </div>
        </div>
    @endif

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

    <!-- 4. Field Campaign Operations & War Room Matrix -->
    <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-flag-fill me-2 text-danger"></i>Field Campaign Operations &amp; Canvassing Matrix
                </h6>
                <small class="text-muted">Door-to-door household surveys, worker performance, and election day voter turnout</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 font-mono">
                    <i class="bi bi-people-fill me-1"></i>{{ $campaignStats['total_workers'] }} Field Workers ({{ $campaignStats['active_workers'] }} Active)
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 font-mono">
                    <i class="bi bi-check2-circle me-1"></i>{{ $campaignStats['coverage_pct'] }}% UC Coverage
                </span>
            </div>
        </div>

        <div class="card-body p-4 bg-light">
            <!-- Campaign KPIs 5-Card Row -->
            <div class="row g-3 mb-4 text-center">
                <div class="col-6 col-md-4 col-xl">
                    <div class="card shadow-sm border-0 h-100 p-3 bg-white border-top border-3 border-success">
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Pakka Vote Bank</div>
                        <div class="fs-4 fw-bold text-success font-mono">{{ number_format($campaignStats['pakka_votes']) }}</div>
                        <small class="text-muted">Confirmed Supporters</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="card shadow-sm border-0 h-100 p-3 bg-white border-top border-3 border-warning">
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Kacha / Swing Votes</div>
                        <div class="fs-4 fw-bold text-warning font-mono">{{ number_format($campaignStats['kacha_votes']) }}</div>
                        <small class="text-muted">High Priority Follow-ups</small>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl">
                    <div class="card shadow-sm border-0 h-100 p-3 bg-white border-top border-3 border-danger">
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Mukhalif Votes</div>
                        <div class="fs-4 fw-bold text-danger font-mono">{{ number_format($campaignStats['mukhalif_votes']) }}</div>
                        <small class="text-muted">Opponent Leaning</small>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-xl">
                    <div class="card shadow-sm border-0 h-100 p-3 bg-white border-top border-3 border-primary">
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Surveyed Gharanas</div>
                        <div class="fs-4 fw-bold text-primary font-mono">{{ number_format($campaignStats['total_surveys']) }}</div>
                        <small class="text-muted">{{ $campaignStats['coverage_pct'] }}% of UC Gharanas</small>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl">
                    <div class="card shadow-sm border-0 h-100 p-3 bg-white border-top border-3 border-info">
                        <div class="text-muted small fw-semibold text-uppercase mb-1">Turnout Parchis</div>
                        <div class="fs-4 fw-bold text-info font-mono">{{ number_format($campaignStats['turnout_parchis']) }}</div>
                        <small class="text-muted">Election Day Slips Issued</small>
                    </div>
                </div>
            </div>

            <!-- Field Workers Roster Table -->
            <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                <div class="card-header bg-white py-2.5 d-flex justify-content-between align-items-center">
                    <span class="fw-bold small text-uppercase text-dark">
                        <i class="bi bi-person-lines-fill me-1 text-primary"></i>Field Staff Roster &amp; Assignment
                    </span>
                    <span class="badge bg-secondary-subtle text-dark border small">{{ count($campaignWorkers) }} Assigned Workers</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Worker Name</th>
                                <th>Phone Number</th>
                                <th>Assigned Census Block(s)</th>
                                <th class="text-center">Gharanas Visited</th>
                                <th class="text-center">Pakka Votes</th>
                                <th class="text-center">Kacha Votes</th>
                                <th>Last Active / Sync</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($campaignWorkers as $index => $w)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-bold text-dark">
                                        <i class="bi bi-person-badge text-secondary me-1"></i>{{ $w->name }}
                                    </td>
                                    <td class="font-mono">
                                        <a href="tel:{{ $w->phone }}" class="text-decoration-none text-dark">{{ $w->phone }}</a>
                                    </td>
                                    <td>
                                        @foreach(explode(',', $w->assigned_block_code) as $bCode)
                                            <span class="badge bg-light text-dark border font-mono mb-0.5">{{ trim($bCode) }}</span>
                                        @endforeach
                                    </td>
                                    <td class="text-center font-mono fw-bold text-primary">{{ $w->visited_count }}</td>
                                    <td class="text-center font-mono fw-bold text-success">{{ $w->pakka_count }}</td>
                                    <td class="text-center font-mono fw-bold text-warning">{{ $w->kacha_count }}</td>
                                    <td>
                                        @if ($w->last_sync_at)
                                            <span class="text-dark">{{ $w->last_sync_at->diffForHumans() }}</span>
                                            <small class="text-muted d-block font-mono" style="font-size: 0.7rem;">{{ $w->last_sync_at->format('d M, h:i A') }}</small>
                                        @else
                                            <span class="text-muted">No Sync Yet</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($w->is_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-3">
                                        <i class="bi bi-people text-secondary fs-4 d-block mb-1"></i>
                                        No campaign workers registered for this candidate yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIP Visit Requests & Canvassing Remarks -->
            @if ($vipVisitRequests->isNotEmpty())
                <div class="card shadow-sm border-0 overflow-hidden">
                    <div class="card-header bg-white py-2.5 d-flex justify-content-between align-items-center">
                        <span class="fw-bold small text-uppercase text-dark">
                            <i class="bi bi-star-fill text-warning me-1"></i>VIP Candidate Visit Requests &amp; Canvassing Remarks
                        </span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle small">{{ $campaignStats['vip_requests_count'] }} Requests</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Block Code</th>
                                    <th>Gharana #</th>
                                    <th>Influencer / Head</th>
                                    <th>Contact Phone</th>
                                    <th>Sentiment</th>
                                    <th>Field Remarks / Notes</th>
                                    <th>Survey Worker</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vipVisitRequests as $req)
                                    <tr>
                                        <td class="font-mono fw-bold">{{ $req->block_code }}</td>
                                        <td class="font-mono">Gharana #{{ $req->gharana_no }}</td>
                                        <td class="fw-bold text-dark">
                                            <i class="bi bi-person me-1 text-secondary"></i>{{ $req->influencer_name ?: 'Family Elder' }}
                                        </td>
                                        <td>
                                            @if ($req->influencer_phone)
                                                <a href="tel:{{ $req->influencer_phone }}" class="text-success fw-bold text-decoration-none font-mono">
                                                    <i class="bi bi-telephone-fill me-1"></i>{{ $req->influencer_phone }}
                                                </a>
                                            @else
                                                <span class="text-muted small">Not provided</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($req->sentiment === 'pakka')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Pakka</span>
                                            @elseif ($req->sentiment === 'kacha')
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Kacha</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Mukhalif</span>
                                            @endif
                                        </td>
                                        <td>{{ $req->notes ?: 'Requested personal candidate visit' }}</td>
                                        <td>{{ $req->worker?->name ?? 'Staff' }}</td>
                                        <td class="text-muted font-mono">{{ $req->created_at->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning-subtle py-3">
                <h6 class="modal-title fw-bold text-dark" id="resetPasswordModalLabel">
                    <i class="bi bi-key-fill text-warning me-2"></i>Reset Candidate Password
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('candidates.reset-password', $candidate) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div>
                            Resetting password for <strong>{{ $candidate->name }}</strong> (<code>{{ $candidate->email }}</code>).
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold small">New Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-mono" id="new_password" name="password" required minlength="6" placeholder="Enter new password (min 6 chars)">
                            <button type="button" class="btn btn-outline-secondary" id="btnGenPass" title="Generate Random Password">
                                <i class="bi bi-shuffle me-1"></i>Generate
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnCopyPass" title="Copy Password">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <small class="text-muted mt-1 d-block">You can click <strong>Generate</strong> to create a secure password and <strong>Copy</strong> to send to the candidate on WhatsApp.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold">
                        <i class="bi bi-check-circle me-1"></i>Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const genBtn = document.getElementById('btnGenPass');
    const copyBtn = document.getElementById('btnCopyPass');
    const passInput = document.getElementById('new_password');

    if (genBtn && passInput) {
        genBtn.addEventListener('click', function() {
            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
            let res = 'Vp@';
            for (let i = 0; i < 6; i++) {
                res += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            passInput.value = res;
        });
    }

    if (copyBtn && passInput) {
        copyBtn.addEventListener('click', function() {
            if (!passInput.value) return;
            navigator.clipboard.writeText(passInput.value).then(() => {
                const origHtml = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="bi bi-check text-success"></i>';
                setTimeout(() => copyBtn.innerHTML = origHtml, 1500);
            });
        });
    }
});
</script>
@endsection
