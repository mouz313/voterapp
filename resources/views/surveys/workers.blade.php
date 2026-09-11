@extends('layouts.app')

@section('title', 'Campaign Field Workers & Polling Agents')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="page-title mb-0">Campaign Field Workers & Polling Camp Staff</h4>
        <small class="text-muted">Monitor mobile field staff, assigned block codes, door-to-door survey progress & live sync telemetry</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('surveys.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Sentiment Matrix
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('campaign-workers.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="candidate_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Candidates</option>
                    @foreach($candidates as $c)
                        <option value="{{ $c->id }}" {{ request('candidate_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->party_name ?? 'Independent' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Worker Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active Staff Only</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive Staff</option>
                </select>
            </div>

            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Search by Worker Name, Phone, Block Code..." value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    @if(request()->hasAny(['candidate_id', 'status', 'search']))
                        <a href="{{ route('campaign-workers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Workers Table -->
<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Worker Profile</th>
                    <th>Candidate & UC</th>
                    <th>Assigned Block Codes</th>
                    <th class="text-center">Gharanas Surveyed</th>
                    <th class="text-center">Pakka Secured</th>
                    <th class="text-center">VIP Requests</th>
                    <th>Last Active / Sync</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($workers as $w)
                    @php
                        $cleanPhone = preg_replace('/[^0-9]/', '', (string)($w->phone ?? ''));
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '92' . substr($cleanPhone, 1);
                        }
                        $isIdle = $w->last_sync_at ? $w->last_sync_at->diffInHours(now()) >= 3 : true;
                    @endphp
                    <tr>
                        <!-- Worker Profile -->
                        <td>
                            <div class="fw-bold text-dark">{{ $w->name }}</div>
                            <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                <span><i class="bi bi-telephone me-1"></i>{{ $w->phone ?: 'No Phone' }}</span>
                                @if(!empty($cleanPhone))
                                    <a href="https://wa.me/{{ $cleanPhone }}?text={{ rawurlencode('Salam ' . $w->name . '! Regarding VoterApp field operations update.') }}" 
                                       target="_blank" 
                                       class="badge bg-success text-white text-decoration-none">
                                        <i class="bi bi-whatsapp me-1"></i>WhatsApp
                                    </a>
                                @endif
                            </div>
                        </td>

                        <!-- Candidate & UC -->
                        <td>
                            <div class="fw-semibold text-dark">{{ $w->candidate?->name ?? 'Unassigned' }}</div>
                            <small class="text-muted d-block">
                                {{ $w->candidate?->party_name ?? 'Independent' }} &bull; {{ $w->candidate?->uc?->name ?? 'Assigned UC' }}
                            </small>
                        </td>

                        <!-- Assigned Blocks -->
                        <td>
                            <span class="badge bg-dark bg-opacity-10 text-dark font-monospace px-2 py-1">
                                {{ $w->assigned_block_code ?: 'ALL' }}
                            </span>
                        </td>

                        <!-- Surveyed Counts -->
                        <td class="text-center">
                            <span class="badge bg-secondary rounded-pill px-2 py-1 fs-6">
                                {{ $w->total_visited }}
                            </span>
                        </td>

                        <!-- Pakka Counts -->
                        <td class="text-center">
                            <span class="badge bg-success rounded-pill px-2 py-1 fs-6">
                                {{ $w->pakka_count }}
                            </span>
                        </td>

                        <!-- VIP Requests -->
                        <td class="text-center">
                            @if($w->vip_requests_count > 0)
                                <span class="badge bg-purple text-white rounded-pill px-2 py-1 fs-6" style="background-color: #7c3aed;">
                                    {{ $w->vip_requests_count }}
                                </span>
                            @else
                                <span class="text-muted small">0</span>
                            @endif
                        </td>

                        <!-- Last Active / Sync -->
                        <td>
                            @if($w->last_sync_at)
                                <div class="d-flex align-items-center gap-1">
                                    @if(!$isIdle)
                                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 py-1 px-2">
                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.55rem;"></i>Active Now
                                        </span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning border-opacity-25 py-1 px-2">
                                            <i class="bi bi-clock-history me-1"></i>Idle
                                        </span>
                                    @endif
                                </div>
                                <small class="text-muted d-block mt-1">{{ $w->last_sync_at->diffForHumans() }}</small>
                            @else
                                <span class="text-muted small">Never Synced</span>
                            @endif
                        </td>

                        <!-- Status -->
                        <td>
                            @if($w->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Disabled</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                            No field workers found matching your filter criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($workers->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $workers->links() }}
        </div>
    @endif
</div>
@endsection
