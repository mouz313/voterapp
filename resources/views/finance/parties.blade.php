@extends('layouts.app')

@section('title', 'Selling Parties & Channels')

@section('content')
    @include('finance.partials.nav')

    <!-- Guidance Banner: Clarifying Commercial Sales Channel vs Political Party -->
    <div class="alert alert-info border-info-subtle bg-info-subtle py-2.5 px-3 rounded-3 mb-3 d-flex align-items-start gap-2 shadow-xs">
        <i class="bi bi-info-circle-fill text-info fs-5 mt-0.5"></i>
        <div class="small flex-grow-1">
            <strong class="text-dark">Rehnumai / Important Distinction:</strong>
            <span class="text-secondary d-block mt-0.5">
                Yahan <strong>Sales Parties &amp; Channels</strong> se murad commercial distribution channels (maslan: <em>Party A, Party B, Franchise, Direct Sales Group</em>) hain jo candidate ko app license sell krte hain.
                <em>(Umeedwar ki Siyasi Jamat jaise PTI / PML-N / Azad candidate create krte waqt alag se select hoti hai.)</em>
            </span>
        </div>
    </div>

    <!-- Header Actions -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-diagram-3 me-1 text-primary"></i> Commercial Sales Parties &amp; Channels</h5>
            <small class="text-muted">Manage distribution channels (Party A, Party B), default candidate license fees, and assigned investors</small>
        </div>
        <button type="button" class="btn btn-primary btn-sm shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#addPartyModal">
            <i class="bi bi-plus-lg me-1"></i> Add Selling Party
        </button>
    </div>

    <!-- Parties Grid Cards -->
    <div class="row g-3 mb-4">
        @forelse ($parties as $p)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card shadow-sm h-100 card-accent-{{ $p->code === 'PARTY_A' ? 'primary' : ($p->code === 'PARTY_B' ? 'success' : 'info') }}">
                    <div class="card-body p-3 d-flex flex-column justify-content-between">
                        <div>
                            <!-- Card Header -->
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">{{ $p->name }}</h6>
                                    <span class="badge bg-light text-muted border font-mono small">{{ $p->code }}</span>
                                </div>
                                <span class="badge {{ $p->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted' }}">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.45rem;"></i>{{ $p->is_active ? 'Active Channel' : 'Inactive' }}
                                </span>
                            </div>

                            <!-- Financial Metrics Box -->
                            <div class="p-2.5 rounded bg-light border small mb-2.5">
                                <div class="d-flex justify-content-between text-muted mb-1 pb-1 border-bottom border-light">
                                    <span>Default License Price:</span>
                                    <strong class="text-dark font-mono">PKR {{ number_format($p->default_price) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between text-muted mb-1">
                                    <span>Candidates Sold:</span>
                                    <strong class="text-primary font-mono">{{ $p->sales_count }} Apps</strong>
                                </div>
                                <div class="d-flex justify-content-between text-muted mb-1">
                                    <span>Revenue Collected:</span>
                                    <strong class="text-success font-mono">PKR {{ number_format($p->total_revenue) }}</strong>
                                </div>
                                <div class="d-flex justify-content-between text-muted">
                                    <span>Pending Due:</span>
                                    <strong class="text-danger font-mono">PKR {{ number_format($p->pending_amount) }}</strong>
                                </div>
                            </div>

                            <!-- Associated Investors / Partners -->
                            <div class="p-2.5 rounded bg-white border small mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1.5 pb-1 border-bottom">
                                    <span class="fw-semibold text-dark"><i class="bi bi-people-fill me-1 text-warning"></i> Assigned Investors / Partners</span>
                                    <a href="{{ route('finance.partners') }}" class="small text-decoration-none fw-semibold" style="font-size: 0.72rem;">Manage &rarr;</a>
                                </div>
                                @if ($p->partners && $p->partners->count() > 0)
                                    @foreach ($p->partners as $ptn)
                                        <div class="d-flex justify-content-between align-items-center py-1 {{ !$loop->last ? 'border-bottom border-light' : '' }}">
                                            <div>
                                                <strong class="text-dark">{{ $ptn->name }}</strong>
                                                <span class="badge {{ $ptn->type === 'investor' ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary' }} font-mono ms-1" style="font-size: 0.65rem;">{{ strtoupper($ptn->type) }}</span>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-dark font-mono">{{ $ptn->profit_share_pct }}% Profit</span>
                                                @if ($ptn->type === 'investor' && $ptn->invested_capital > 0)
                                                    <div class="text-muted font-mono" style="font-size: 0.68rem;">Capital: PKR {{ number_format($ptn->invested_capital) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-muted small py-1 fst-italic">
                                        No investor or partner specifically linked to this party yet.
                                    </div>
                                @endif
                            </div>

                            @if ($p->contact_person || $p->phone || $p->notes)
                                <div class="small text-muted mb-2 bg-light-subtle p-2 rounded border border-light">
                                    @if ($p->contact_person)
                                        <div><i class="bi bi-person me-1"></i> Contact: <strong class="text-dark">{{ $p->contact_person }}</strong></div>
                                    @endif
                                    @if ($p->phone)
                                        <div><i class="bi bi-telephone me-1 font-mono"></i> {{ $p->phone }}</div>
                                    @endif
                                    @if ($p->notes)
                                        <div class="text-truncate" title="{{ $p->notes }}"><i class="bi bi-info-circle me-1"></i> {{ $p->notes }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <!-- Card Footer -->
                        <div class="d-flex justify-content-end pt-2 border-top">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPartyModal_{{ $p->id }}">
                                <i class="bi bi-pencil-square me-1"></i> Edit Channel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-diagram-3 fs-1 text-muted d-block mb-2"></i>
                <div class="fw-semibold">No commercial selling parties registered yet.</div>
                <small class="text-muted">Click the button above to register Party A or Party B.</small>
            </div>
        @endforelse
    </div>

    <!-- Edit Party Modals (Rendered outside the grid row for clean DOM and zero layout interference) -->
    @foreach ($parties as $p)
        <div class="modal fade" id="editPartyModal_{{ $p->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form method="POST" action="{{ route('finance.parties.update', $p->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header py-2.5 bg-light">
                            <h6 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-1 text-primary"></i> Edit Selling Channel: {{ $p->name }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-3">
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Channel / Party Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ $p->name }}" class="form-control form-control-sm" placeholder="e.g. Party A, Channel B, Direct Sales" required>
                                <small class="text-muted" style="font-size: 0.7rem;">Commercial distribution group name</small>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Short Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" value="{{ $p->code }}" class="form-control form-control-sm font-mono" placeholder="e.g. PARTY_A" required>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Contact Person</label>
                                    <input type="text" name="contact_person" value="{{ $p->contact_person }}" class="form-control form-control-sm" placeholder="Representative name">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Phone / WhatsApp</label>
                                    <input type="text" name="phone" value="{{ $p->phone }}" class="form-control form-control-sm font-mono" placeholder="0300-1234567">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Default License Price (PKR) <span class="text-danger">*</span></label>
                                <input type="number" name="default_price" value="{{ (int)$p->default_price }}" class="form-control form-control-sm font-mono" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Notes / Description</label>
                                <input type="text" name="notes" value="{{ $p->notes }}" class="form-control form-control-sm" placeholder="Optional notes">
                            </div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act_{{ $p->id }}" {{ $p->is_active ? 'checked' : '' }}>
                                <label class="form-check-label small fw-semibold" for="act_{{ $p->id }}">
                                    Active Channel (Show in Candidate Registration Dropdown)
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer py-2 bg-light">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Add Party Modal -->
    <div class="modal fade" id="addPartyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('finance.parties.store') }}">
                    @csrf
                    <div class="modal-header py-2.5 bg-light">
                        <h6 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle-fill me-1 text-primary"></i> Register New Selling Channel / Party</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Channel / Party Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Channel C, Direct Sales, Party A" required>
                            <small class="text-muted" style="font-size: 0.7rem;">Commercial distribution group name</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Short Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control form-control-sm font-mono" placeholder="e.g. PARTY_C" required>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Contact Person</label>
                                <input type="text" name="contact_person" class="form-control form-control-sm" placeholder="Representative name">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Phone / WhatsApp</label>
                                <input type="text" name="phone" class="form-control form-control-sm font-mono" placeholder="0300-1234567">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Default License Price (PKR) <span class="text-danger">*</span></label>
                            <input type="number" name="default_price" value="20000" class="form-control form-control-sm font-mono" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Notes</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional notes">
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="act_new" checked>
                            <label class="form-check-label small fw-semibold" for="act_new">
                                Active Channel (Show in Candidate Registration Form)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer py-2 bg-light">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Create Selling Channel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
