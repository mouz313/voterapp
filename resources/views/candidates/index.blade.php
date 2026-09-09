@extends('layouts.app')

@section('title', 'Candidates & Party Branding')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Candidates & Party Branding</h4>
            <small class="text-muted">Manage candidate accounts, assigned UCs, party logos, candidate photos, and mobile devices</small>
        </div>
        <a href="{{ route('candidates.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Register Candidate
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="uc_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All UCs</option>
                        @foreach ($ucs as $uc)
                            <option value="{{ $uc->id }}" {{ request('uc_id') == $uc->id ? 'selected' : '' }}>
                                {{ $uc->name }} ({{ $uc->tehsil->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by Candidate Name, Party, Email..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                @if(request()->hasAny(['uc_id', 'status', 'search']))
                    <div class="col-auto">
                        <a href="{{ route('candidates.index') }}" class="btn btn-outline-danger btn-sm">Clear Filter</a>
                    </div>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Candidate Profile</th>
                        <th>Party & Nishan</th>
                        <th>Assigned UC</th>
                        <th>App Sale / Party</th>
                        <th>Active Devices</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($candidates as $candidate)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($candidate->candidate_image)
                                        <img src="{{ asset($candidate->candidate_image) }}" class="rounded-circle border" style="width: 44px; height: 44px; object-fit: cover;" alt="Photo">
                                    @else
                                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-primary fw-bold" style="width: 44px; height: 44px;">
                                            {{ strtoupper(substr($candidate->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('candidates.show', $candidate) }}" class="fw-bold text-dark text-decoration-none hover-primary">
                                            {{ $candidate->name }}
                                        </a>
                                        <small class="text-muted d-block"><i class="bi bi-envelope me-1"></i>{{ $candidate->email }}</small>
                                        @if($candidate->candidate_code)
                                            <span class="badge bg-dark text-white font-monospace mt-1" style="letter-spacing: 0.5px;">
                                                <i class="bi bi-key-fill text-warning me-1"></i>{{ $candidate->candidate_code }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($candidate->party_logo)
                                        <img src="{{ asset($candidate->party_logo) }}" class="rounded border p-1" style="max-height: 36px;" alt="Logo">
                                    @endif
                                    <div>
                                        @if($candidate->is_independent)
                                            <span class="badge bg-secondary">Azad Umedwar (Independent)</span>
                                        @else
                                            <span class="badge bg-primary">{{ $candidate->party_name ?: 'Party Candidate' }}</span>
                                        @endif
                                        @if($candidate->candidate_symbol)
                                            <div class="small text-dark fw-semibold mt-1">
                                                <i class="bi bi-shield me-1 text-warning"></i>Nishan: {{ $candidate->candidate_symbol }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($candidate->uc)
                                    <span class="badge bg-success fs-6">{{ $candidate->uc->name }}</span>
                                    <small class="text-muted d-block">
                                        {{ $candidate->uc->tehsil->district->name ?? '' }} &bull; {{ $candidate->uc->tehsil->name ?? '' }}
                                    </small>
                                @else
                                    <span class="badge bg-warning text-dark">No UC Assigned</span>
                                @endif
                            </td>
                            <td>
                                @if($candidate->sale)
                                    <div>
                                        <span class="badge bg-dark-subtle text-dark border font-mono">
                                            <i class="bi bi-shop me-1 text-primary"></i>{{ $candidate->sale->party->name ?? 'Direct' }}
                                        </span>
                                        <div class="small fw-bold text-dark mt-1 font-mono">PKR {{ number_format($candidate->sale->sale_amount) }}</div>
                                        @if($candidate->sale->payment_status === 'paid')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1" style="font-size: 0.72rem;">
                                                <i class="bi bi-check-circle-fill me-1"></i>Paid
                                            </span>
                                        @elseif($candidate->sale->payment_status === 'partial')
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-0 px-1" style="font-size: 0.72rem;">
                                                <i class="bi bi-clock-history me-1"></i>Partial ({{ number_format($candidate->sale->amount_paid) }})
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-0 px-1" style="font-size: 0.72rem;">
                                                <i class="bi bi-exclamation-circle-fill me-1"></i>Unpaid
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="badge bg-light text-muted border font-mono">Not Linked</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('candidates.devices', $candidate) }}" class="text-decoration-none">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 font-mono">
                                        <i class="bi bi-phone me-1"></i>{{ $candidate->active_devices_count }} Active (Unlimited)
                                    </span>
                                </a>
                            </td>
                            <td>
                                @if($candidate->status === 'active')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>Suspended</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('candidates.show', $candidate) }}" class="btn btn-sm btn-outline-primary" title="Performance Matrix & Stats"><i class="bi bi-speedometer2"></i></a>
                                <a href="{{ route('candidates.report', $candidate) }}" class="btn btn-sm btn-outline-danger" title="Executive PDF Report"><i class="bi bi-file-earmark-pdf"></i></a>
                                <button type="button" class="btn btn-sm btn-outline-warning text-dark" title="Reset Password" data-bs-toggle="modal" data-bs-target="#resetPassModal{{ $candidate->id }}">
                                    <i class="bi bi-key-fill text-warning"></i>
                                </button>
                                <a href="{{ route('candidates.devices', $candidate) }}" class="btn btn-sm btn-outline-info" title="Manage Devices"><i class="bi bi-phone"></i></a>
                                <a href="{{ route('candidates.edit', $candidate) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('candidates.destroy', $candidate) }}" class="d-inline" onsubmit="return confirm('Delete candidate account {{ $candidate->name }}?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>

                                <!-- Quick Reset Password Modal -->
                                <div class="modal fade text-start" id="resetPassModal{{ $candidate->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header bg-warning-subtle py-2.5">
                                                <h6 class="modal-title fw-bold text-dark">
                                                    <i class="bi bi-key-fill text-warning me-1"></i>Reset Password — {{ $candidate->name }}
                                                </h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('candidates.reset-password', $candidate) }}" method="POST">
                                                @csrf
                                                <div class="modal-body p-3">
                                                    <p class="small text-muted mb-2">Email: <code>{{ $candidate->email }}</code></p>
                                                    <div class="input-group mb-2">
                                                        <input type="text" class="form-control font-mono" id="passInput_{{ $candidate->id }}" name="password" required minlength="6" placeholder="Enter new password">
                                                        <button type="button" class="btn btn-outline-secondary" onclick="
                                                            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
                                                            let r = 'Vp@';
                                                            for(let i=0; i<6; i++) r += chars.charAt(Math.floor(Math.random()*chars.length));
                                                            document.getElementById('passInput_{{ $candidate->id }}').value = r;
                                                        ">Generate</button>
                                                    </div>
                                                    <small class="text-muted">Minimum 6 characters. Copy and share via WhatsApp/SMS.</small>
                                                </div>
                                                <div class="modal-footer bg-light py-2">
                                                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold">Update Password</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No candidates registered yet. Click <strong>Register Candidate</strong> above to add one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $candidates->links() }}</div>
    </div>
@endsection
