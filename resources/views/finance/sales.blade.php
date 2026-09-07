@extends('layouts.app')

@section('title', 'Candidate Sales Ledger')

@section('content')
    @include('finance.partials.nav')

    <!-- Top Summary KPI Matrix -->
    <div class="row g-2 g-sm-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Total App Deals</span>
                    <div class="stat-icon-wrap bg-primary-subtle text-primary"><i class="bi bi-phone-vibrate"></i></div>
                </div>
                <div class="stat-value text-dark font-mono">{{ number_format($summary['total_count']) }}</div>
                <small class="text-muted">Registered Candidate Deals</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Gross Invoiced</span>
                    <div class="stat-icon-wrap bg-info-subtle text-info"><i class="bi bi-receipt"></i></div>
                </div>
                <div class="stat-value text-dark font-mono"><small class="fs-6 text-muted">PKR</small> {{ number_format($summary['total_invoiced']) }}</div>
                <small class="text-muted">Total Agreed Billing</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Total Collected</span>
                    <div class="stat-icon-wrap bg-success-subtle text-success"><i class="bi bi-cash-stack"></i></div>
                </div>
                <div class="stat-value text-success font-mono"><small class="fs-6 text-success">PKR</small> {{ number_format($summary['total_collected']) }}</div>
                <small class="text-muted">Realized Cash Revenue</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Outstanding Balance</span>
                    <div class="stat-icon-wrap bg-danger-subtle text-danger"><i class="bi bi-exclamation-circle"></i></div>
                </div>
                <div class="stat-value text-danger font-mono"><small class="fs-6 text-danger">PKR</small> {{ number_format($summary['total_pending']) }}</div>
                <small class="text-muted">Pending Due from Candidates</small>
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('finance.sales') }}" class="row g-2 align-items-center">
                <div class="col-12 col-lg-3 col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-start-0" placeholder="Candidate / Phone / UC...">
                    </div>
                </div>

                <div class="col-6 col-lg-2 col-md-3">
                    <select name="party_id" class="form-select form-select-sm">
                        <option value="">All Parties</option>
                        @foreach ($parties as $p)
                            <option value="{{ $p->id }}" {{ request('party_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-lg-2 col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial</option>
                    </select>
                </div>

                <div class="col-6 col-lg-2 col-md-4">
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm" title="From Date">
                </div>
                <div class="col-6 col-lg-2 col-md-4">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm" title="To Date">
                </div>

                <div class="col-12 col-lg-1 col-md-4 d-flex gap-1 justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter Records"><i class="bi bi-funnel-fill"></i></button>
                    @if(request()->anyFilled(['search', 'party_id', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('finance.sales') }}" class="btn btn-sm btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-circle"></i></a>
                    @endif
                    <a href="{{ route('finance.sales', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-sm btn-outline-success" title="Export CSV">
                        <i class="bi bi-download"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-matrix align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Candidate / Phone</th>
                        <th>Assigned Area</th>
                        <th>Selling Party</th>
                        <th>Price Charged</th>
                        <th>Collected</th>
                        <th>Balance Due</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $s)
                        <tr>
                            <td class="text-nowrap small text-muted font-mono">
                                {{ $s->payment_date ? $s->payment_date->format('d M Y') : $s->created_at->format('d M Y') }}
                            </td>
                            <td class="fw-semibold">
                                <a href="{{ route('candidates.show', $s->candidate_id) }}" class="text-decoration-none text-dark hover-primary text-truncate d-block" style="max-width: 170px;">
                                    {{ $s->candidate->name ?? 'Candidate #' . $s->candidate_id }}
                                </a>
                                <small class="text-muted font-mono d-block">{{ $s->candidate->phone ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border text-truncate d-block" style="max-width: 150px;">
                                    {{ $s->candidate->uc ? ($s->candidate->uc->tehsil?->name . ' - UC ' . ($s->candidate->uc->uc_no ?: $s->candidate->uc->id)) : 'General' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $s->party?->code === 'PARTY_A' ? 'bg-primary-subtle text-primary border border-primary-subtle' : ($s->party?->code === 'PARTY_B' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-dark border') }}">
                                    {{ $s->party->name ?? 'Direct' }}
                                </span>
                            </td>
                            <td class="font-mono fw-bold text-dark text-nowrap">
                                PKR {{ number_format($s->sale_amount) }}
                            </td>
                            <td class="font-mono text-success fw-bold text-nowrap">
                                PKR {{ number_format($s->amount_paid) }}
                            </td>
                            <td class="font-mono fw-bold text-nowrap {{ $s->balance_due > 0 ? 'text-danger' : 'text-muted' }}">
                                {{ $s->balance_due > 0 ? 'PKR ' . number_format($s->balance_due) : '-' }}
                            </td>
                            <td>
                                @if ($s->payment_status === 'paid')
                                    <span class="badge bg-success font-mono"><i class="bi bi-check2-circle me-1"></i>PAID</span>
                                @elseif ($s->payment_status === 'partial')
                                    <span class="badge bg-warning text-dark font-mono"><i class="bi bi-pie-chart me-1"></i>PARTIAL</span>
                                @else
                                    <span class="badge bg-danger font-mono"><i class="bi bi-hourglass-split me-1"></i>PENDING</span>
                                @endif
                            </td>
                            <td><small class="text-muted text-nowrap">{{ $s->payment_method ?: 'Cash' }}</small></td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2" 
                                        data-bs-toggle="modal" data-bs-target="#editSaleModal_{{ $s->id }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Edit / Update Sale Modal -->
                        <div class="modal fade" id="editSaleModal_{{ $s->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('finance.sales.update', $s->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header py-2.5">
                                            <h6 class="modal-title fw-bold">Update Sale: {{ $s->candidate->name ?? 'Candidate' }}</h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-3">
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold">Selling Party</label>
                                                <select name="sales_party_id" class="form-select form-select-sm">
                                                    <option value="">-- Direct Sale --</option>
                                                    @foreach ($parties as $p)
                                                        <option value="{{ $p->id }}" {{ $s->sales_party_id == $p->id ? 'selected' : '' }}>
                                                            {{ $p->name }} ({{ $p->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Price Charged (PKR)</label>
                                                    <input type="number" name="sale_amount" value="{{ (int)$s->sale_amount }}" class="form-control form-control-sm font-mono" required>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Amount Paid (PKR)</label>
                                                    <input type="number" name="amount_paid" value="{{ (int)$s->amount_paid }}" class="form-control form-control-sm font-mono" required>
                                                </div>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Status</label>
                                                    <select name="payment_status" class="form-select form-select-sm" required>
                                                        <option value="paid" {{ $s->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                                                        <option value="pending" {{ $s->payment_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                        <option value="partial" {{ $s->payment_status === 'partial' ? 'selected' : '' }}>Partial</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Payment Method</label>
                                                    <select name="payment_method" class="form-select form-select-sm">
                                                        <option value="Cash" {{ $s->payment_method === 'Cash' ? 'selected' : '' }}>Cash</option>
                                                        <option value="Bank Transfer" {{ $s->payment_method === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                                        <option value="JazzCash" {{ $s->payment_method === 'JazzCash' ? 'selected' : '' }}>JazzCash</option>
                                                        <option value="EasyPaisa" {{ $s->payment_method === 'EasyPaisa' ? 'selected' : '' }}>EasyPaisa</option>
                                                        <option value="Cheque" {{ $s->payment_method === 'Cheque' ? 'selected' : '' }}>Cheque</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold">Payment Date</label>
                                                <input type="date" name="payment_date" value="{{ $s->payment_date ? $s->payment_date->format('Y-m-d') : date('Y-m-d') }}" class="form-control form-control-sm">
                                            </div>

                                            <div class="mb-0">
                                                <label class="form-label small fw-semibold">Notes</label>
                                                <input type="text" name="notes" value="{{ $s->notes }}" class="form-control form-control-sm" placeholder="Optional reference">
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
                                    <div class="mb-2"><i class="bi bi-receipt text-muted" style="font-size: 2.5rem;"></i></div>
                                    <h6 class="fw-bold text-dark mb-1">No Candidate Sales Recorded</h6>
                                    <p class="small text-muted mb-3">When you register or sell an app to a candidate, their licensing and billing ledger appears here.</p>
                                    <a href="{{ route('candidates.create') }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-person-plus-fill me-1"></i> Register Candidate &amp; Sell App
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sales->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
@endsection
