@extends('layouts.app')

@section('title', 'Partners & Investor Payback')

@section('content')
    @include('finance.partials.nav')

    <!-- Top KPI Cards Matrix -->
    <div class="row g-2 g-sm-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Total Capital</span>
                    <div class="stat-icon-wrap bg-primary-subtle text-primary"><i class="bi bi-wallet2"></i></div>
                </div>
                <div class="stat-value text-dark font-mono"><small class="fs-6 text-muted">PKR</small> {{ number_format($totalInvested) }}</div>
                <small class="text-muted">Total Seed Capital Injected</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Capital Returned</span>
                    <div class="stat-icon-wrap bg-success-subtle text-success"><i class="bi bi-arrow-return-left"></i></div>
                </div>
                <div class="stat-value text-success font-mono"><small class="fs-6 text-success">PKR</small> {{ number_format($totalCapitalReturned) }}</div>
                <small class="text-muted">Repaid to Principal Investors</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Dividends Paid</span>
                    <div class="stat-icon-wrap bg-info-subtle text-info"><i class="bi bi-cash-coin"></i></div>
                </div>
                <div class="stat-value text-info-emphasis font-mono"><small class="fs-6 text-muted">PKR</small> {{ number_format($totalProfitDistributed) }}</div>
                <small class="text-muted">Total Profit Shares Distributed</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 {{ $totalProfitPctAssigned > 100 ? 'border-danger' : 'border-warning' }}">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Equity Assigned</span>
                    <div class="stat-icon-wrap bg-warning-subtle text-warning"><i class="bi bi-pie-chart-fill"></i></div>
                </div>
                <div class="stat-value font-mono {{ $totalProfitPctAssigned > 100 ? 'text-danger' : 'text-primary' }}">
                    {{ $totalProfitPctAssigned }}% <small class="text-muted fs-6">/ 100%</small>
                </div>
                <small class="text-muted">Allocated Partner Profit Shares</small>
            </div>
        </div>
    </div>

    <!-- Header Actions -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill me-1 text-warning"></i> Partners &amp; Investors Roster</h5>
            <small class="text-muted">Manage investor capital payback terms and partner percentage shares</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#recordPayoutModal">
                <i class="bi bi-cash-stack me-1"></i> Disburse Payout
            </button>
            <button type="button" class="btn btn-primary btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#addPartnerModal">
                <i class="bi bi-person-plus-fill me-1"></i> Add Partner / Investor
            </button>
        </div>
    </div>

    <!-- Partners Table -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="table-responsive">
            <table class="table table-matrix align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Partner Name</th>
                        <th>Linked Party</th>
                        <th>Type</th>
                        <th>Profit Share %</th>
                        <th>Invested Capital</th>
                        <th>Capital Returned</th>
                        <th>Recovery Progress</th>
                        <th>Profit Dividends Paid</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($partners as $p)
                        <tr>
                            <td class="fw-bold text-dark">
                                {{ $p->name }}
                                @if ($p->phone)
                                    <small class="text-muted d-block fw-normal font-mono">{{ $p->phone }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($p->salesParty)
                                    <span class="badge {{ $p->salesParty->code === 'PARTY_A' ? 'bg-primary-subtle text-primary border border-primary-subtle' : ($p->salesParty->code === 'PARTY_B' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-info-subtle text-info border border-info-subtle') }} font-mono px-2 py-1">
                                        <i class="bi bi-shop me-1"></i>{{ $p->salesParty->name }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border font-mono">General (All)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $p->type === 'investor' ? 'bg-warning text-dark font-mono' : 'bg-primary-subtle text-primary font-mono' }}">
                                    {{ strtoupper($p->type) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-mono fw-bold fs-6">{{ $p->profit_share_pct }}%</span>
                            </td>
                            <td class="font-mono text-dark font-semibold">
                                {{ $p->invested_capital > 0 ? 'PKR ' . number_format($p->invested_capital) : '-' }}
                            </td>
                            <td class="font-mono text-success fw-bold">
                                {{ $p->capital_returned > 0 ? 'PKR ' . number_format($p->capital_returned) : '-' }}
                            </td>
                            <td style="min-width: 140px;">
                                @if ($p->type === 'investor' && $p->invested_capital > 0)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: {{ $p->capital_payback_progress_pct }}%"></div>
                                        </div>
                                        <span class="small font-mono fw-bold {{ $p->capital_payback_progress_pct == 100 ? 'text-success' : 'text-muted' }}">
                                            {{ $p->capital_payback_progress_pct }}%
                                        </span>
                                    </div>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">
                                        Remaining: PKR {{ number_format($p->capital_remaining) }}
                                    </small>
                                @else
                                    <span class="text-muted small">Equity Partner</span>
                                @endif
                            </td>
                            <td class="font-mono text-info fw-bold">
                                {{ $p->profit_paid > 0 ? 'PKR ' . number_format($p->profit_paid) : '-' }}
                            </td>
                            <td>
                                <span class="badge {{ $p->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted' }}">
                                    {{ $p->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2" 
                                        data-bs-toggle="modal" data-bs-target="#editPartnerModal_{{ $p->id }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Partner Modal -->
                        <div class="modal fade" id="editPartnerModal_{{ $p->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('finance.partners.update', $p->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header py-2.5">
                                            <h6 class="modal-title fw-bold">Edit Partner / Investor: {{ $p->name }}</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-3">
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold">Partner Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" value="{{ $p->name }}" class="form-control form-control-sm" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold">Associated Party / Channel (پارٹی کا تعلق)</label>
                                                <select name="sales_party_id" class="form-select form-select-sm">
                                                    <option value="">-- General / Overarching (سب پارٹیز پر لاگو) --</option>
                                                    @foreach ($salesParties as $sp)
                                                        <option value="{{ $sp->id }}" {{ $p->sales_party_id == $sp->id ? 'selected' : '' }}>
                                                            {{ $sp->name }} ({{ $sp->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted" style="font-size: 0.72rem;">Select if this investor/partner represents Party A or Party B</small>
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
                                                    <select name="type" class="form-select form-select-sm" required>
                                                        <option value="investor" {{ $p->type === 'investor' ? 'selected' : '' }}>Investor (Capital Recovery)</option>
                                                        <option value="partner" {{ $p->type === 'partner' ? 'selected' : '' }}>Partner (Equity Profit Share)</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    @php
                                                        $currAssigned = $p->is_active ? (float)$p->profit_share_pct : 0;
                                                        $othersAssigned = (float)$totalProfitPctAssigned - $currAssigned;
                                                        $maxCap = max(0, round(100 - $othersAssigned, 2));
                                                    @endphp
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <label class="form-label small fw-semibold mb-0">Profit Share (%) <span class="text-danger">*</span></label>
                                                        <span class="badge bg-light text-primary border font-mono" style="font-size: 0.65rem;" title="Max capacity for this partner">
                                                            Max: {{ $maxCap }}%
                                                        </span>
                                                    </div>
                                                    <input type="number" name="profit_share_pct" value="{{ $p->profit_share_pct }}" class="form-control form-control-sm font-mono" min="0" max="{{ $maxCap }}" step="0.1" required>
                                                    <small class="text-muted" style="font-size: 0.68rem;">Is partner k liye max: <strong>{{ $maxCap }}%</strong></small>
                                                </div>
                                            </div>
                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Invested Capital (PKR)</label>
                                                    <input type="number" name="invested_capital" value="{{ (int)$p->invested_capital }}" class="form-control form-control-sm font-mono">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Phone / WhatsApp</label>
                                                    <input type="text" name="phone" value="{{ $p->phone }}" class="form-control form-control-sm font-mono">
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold">Bank / Account Details</label>
                                                <textarea name="bank_details" class="form-control form-control-sm" rows="2">{{ $p->bank_details }}</textarea>
                                            </div>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="pact_{{ $p->id }}" {{ $p->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label small fw-semibold" for="pact_{{ $p->id }}">
                                                    Active Partner
                                                </label>
                                            </div>
                                        </div>
                                        <div class="modal-footer py-2">
                                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <div class="py-3">
                                    <div class="mb-2"><i class="bi bi-people text-muted" style="font-size: 2.5rem;"></i></div>
                                    <h6 class="fw-bold text-dark mb-1">No Partners or Investors Registered</h6>
                                    <p class="small text-muted mb-3">Add seed investors to track capital payback or business partners with customized profit shares.</p>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPartnerModal">
                                        <i class="bi bi-person-plus-fill me-1"></i> Add First Partner / Investor
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Partner Modal -->
    <div class="modal fade" id="addPartnerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('finance.partners.store') }}">
                    @csrf
                    <div class="modal-header py-2.5">
                        <h6 class="modal-title fw-bold">Add New Partner or Investor</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Malik Ahmad (Investor)" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Associated Party / Channel (پارٹی کا تعلق)</label>
                            <select name="sales_party_id" class="form-select form-select-sm">
                                <option value="">-- General / Overarching (سب پارٹیز پر لاگو) --</option>
                                @foreach ($salesParties as $sp)
                                    <option value="{{ $sp->id }}">{{ $sp->name }} ({{ $sp->code }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 0.72rem;">Select if this investor or partner represents Party A or Party B</small>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select form-select-sm" required>
                                    <option value="investor">Investor (With Capital)</option>
                                    <option value="partner">Partner (Profit Share Only)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                @php
                                    $addMaxCap = max(0, round(100 - $totalProfitPctAssigned, 2));
                                @endphp
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold mb-0">Profit Share (%) <span class="text-danger">*</span></label>
                                    <span class="badge bg-light text-primary border font-mono" style="font-size: 0.65rem;" title="Available percentage capacity">
                                        Max: {{ $addMaxCap }}%
                                    </span>
                                </div>
                                <input type="number" name="profit_share_pct" value="{{ min(10, $addMaxCap) }}" class="form-control form-control-sm font-mono" min="0" max="{{ $addMaxCap }}" step="0.1" required>
                                <small class="text-muted" style="font-size: 0.68rem;">Gunjaish (Remaining): <strong>{{ $addMaxCap }}%</strong></small>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Initial Invested Capital (PKR)</label>
                                <input type="number" name="invested_capital" value="0" class="form-control form-control-sm font-mono" placeholder="e.g. 500000">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Phone / WhatsApp</label>
                                <input type="text" name="phone" class="form-control form-control-sm font-mono" placeholder="0300-1234567">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Bank / Payment Details</label>
                            <textarea name="bank_details" class="form-control form-control-sm" rows="2" placeholder="Bank Name, IBAN, Account Title"></textarea>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="pact_new" checked>
                            <label class="form-check-label small fw-semibold" for="pact_new">
                                Active Partner
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Create Partner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Disburse Payout Modal -->
    <div class="modal fade" id="recordPayoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('finance.payouts.store') }}">
                    @csrf
                    <div class="modal-header py-2.5">
                        <h6 class="modal-title fw-bold"><i class="bi bi-cash-stack me-1 text-success"></i> Disburse Payout to Partner / Investor</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Select Beneficiary Partner <span class="text-danger">*</span></label>
                            <select name="partner_id" class="form-select form-select-sm" required>
                                <option value="">-- Choose Partner --</option>
                                @foreach ($partners as $p)
                                    <option value="{{ $p->id }}">
                                        {{ $p->name }} ({{ ucfirst($p->type) }} &bull; {{ $p->profit_share_pct }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Payout Type <span class="text-danger">*</span></label>
                                <select name="payout_type" class="form-select form-select-sm" required>
                                    <option value="capital_return">Capital Return (انویسٹمنٹ واپسی)</option>
                                    <option value="profit_distribution">Profit Distribution (منافع شیئر)</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Payout Amount (PKR) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control form-control-sm font-mono fw-bold" placeholder="e.g. 50000" min="1" required>
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payout_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Payment Method</label>
                                <select name="payment_method" class="form-select form-select-sm">
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cash">Cash</option>
                                    <option value="JazzCash">JazzCash</option>
                                    <option value="EasyPaisa">EasyPaisa</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Reference / Receipt No.</label>
                            <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="e.g. Bank Ref #99212">
                        </div>

                        <div class="mb-0">
                            <label class="form-label small fw-semibold">Notes</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional notes">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-success px-3"><i class="bi bi-check-lg me-1"></i> Confirm &amp; Disburse</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
