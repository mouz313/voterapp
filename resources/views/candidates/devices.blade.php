@extends('layouts.app')

@section('title', 'Manage Candidate Devices')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('candidates.index') }}">Candidates</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $candidate->name }}</li>
                </ol>
            </nav>
            <h4 class="page-title mb-0">Active Mobile Devices &bull; {{ $candidate->name }}</h4>
            <small class="text-muted">
                Assigned UC: <strong>{{ $candidate->uc->name ?? 'None' }}</strong> &bull; 
                Device Access: <span class="badge bg-success-subtle text-success border border-success-subtle">Unlimited Devices Allowed</span> &bull; 
                Active Connected: <strong>{{ $candidate->devices->where('is_revoked', false)->count() }} Devices</strong>
            </small>
        </div>
        <a href="{{ route('candidates.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Candidates
        </a>
    </div>

    <!-- Stats Row (Unlimited Devices Policy) -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-4 border-success bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold">Active Authorized Logins</div>
                            <div class="display-6 fw-bold text-success">{{ $candidate->devices->where('is_revoked', false)->count() }}</div>
                            <small class="text-muted">Currently active polling phones</small>
                        </div>
                        <div class="rounded-3 bg-success-subtle text-success p-3 fs-3">
                            <i class="bi bi-phone"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-4 border-primary bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold">Device Limit Policy</div>
                            <div class="fs-4 fw-bold text-primary mt-1">Unlimited</div>
                            <small class="text-success fw-semibold"><i class="bi bi-check2-circle me-1"></i>No login cap / Free for all agents</small>
                        </div>
                        <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-3">
                            <i class="bi bi-infinity"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 border-start border-4 border-danger bg-white">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small fw-semibold">Blocked / Revoked</div>
                            <div class="display-6 fw-bold text-danger">{{ $candidate->devices->where('is_revoked', true)->count() }}</div>
                            <small class="text-muted">Devices locked out by admin</small>
                        </div>
                        <div class="rounded-3 bg-danger-subtle text-danger p-3 fs-3">
                            <i class="bi bi-slash-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Devices Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-laptop me-2 text-primary"></i>Logged-in Polling Devices (Phones &amp; Tablets)</h6>
            <span class="badge bg-light text-dark border">{{ $candidate->devices->count() }} Total Registered</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-matrix align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Device Name / Model</th>
                        <th>Platform</th>
                        <th>Device Identifier (UUID)</th>
                        <th>IP Address</th>
                        <th>Last Active / Sync</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($candidate->devices as $dev)
                        <tr class="{{ $dev->is_revoked ? 'table-light text-muted' : '' }}">
                            <td>
                                <div class="fw-bold text-dark">
                                    <i class="bi {{ $dev->platform === 'ios' ? 'bi-apple' : 'bi-android2' }} me-1 {{ $dev->platform === 'ios' ? 'text-dark' : 'text-success' }}"></i>
                                    {{ $dev->device_name ?: 'Unknown Mobile Device' }}
                                </div>
                                <small class="text-muted">App Version: {{ $dev->app_version ?: '1.0.0' }}</small>
                            </td>
                            <td>
                                <span class="badge {{ $dev->platform === 'ios' ? 'bg-dark' : 'bg-success' }}">
                                    {{ strtoupper($dev->platform ?: 'Android') }}
                                </span>
                            </td>
                            <td>
                                <code class="small text-muted font-mono">{{ Str::limit($dev->device_uid, 22) }}</code>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $dev->ip_address ?: 'Offline Sync' }}</span>
                            </td>
                            <td>
                                @if($dev->last_active_at)
                                    <div>{{ $dev->last_active_at->diffForHumans() }}</div>
                                    <small class="text-muted">{{ $dev->last_active_at->format('d M Y, h:i A') }}</small>
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td>
                                @if($dev->is_revoked)
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Revoked / Blocked</span>
                                @else
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Authorized</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('candidates.devices.toggle', $dev) }}" class="d-inline">
                                    @csrf
                                    @if($dev->is_revoked)
                                        <button class="btn btn-sm btn-outline-success" title="Unblock / Reauthorize Device">
                                            <i class="bi bi-unlock me-1"></i> Unblock
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-outline-danger" title="Revoke Device Login" onclick="return confirm('Revoke access for this device? The user will be blocked immediately.');">
                                            <i class="bi bi-lock me-1"></i> Revoke
                                        </button>
                                    @endif
                                </form>

                                <form method="POST" action="{{ route('candidates.devices.destroy', $dev) }}" class="d-inline" onsubmit="return confirm('Delete this device record permanently?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-secondary" title="Remove Record"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-phone fs-1 d-block mb-2 text-secondary"></i>
                                <strong>No devices have logged in under this candidate account yet.</strong><br>
                                <span class="small text-muted">When polling agents log into the mobile app using <code>{{ $candidate->email }}</code>, their devices will automatically appear here.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
