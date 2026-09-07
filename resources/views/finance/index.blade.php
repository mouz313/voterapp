@extends('layouts.app')

@section('title', 'Finance & Sales Executive Matrix')

@section('content')
    @include('finance.partials.nav')

    <!-- 1. Top Financial KPI Cards -->
    <div class="row g-2 g-sm-3 mb-3">
        <!-- Total App Sales -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Apps Sold</span>
                    <div class="stat-icon-wrap bg-primary-subtle text-primary"><i class="bi bi-phone-vibrate"></i></div>
                </div>
                <div class="stat-value text-dark font-mono">{{ number_format($totalSalesCount) }}</div>
                <small class="text-muted">{{ $paidSalesCount }} Fully Paid &bull; {{ $pendingSalesCount }} Pending</small>
            </div>
        </div>

        <!-- Total Revenue Invoiced -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Total Invoiced</span>
                    <div class="stat-icon-wrap bg-info-subtle text-info"><i class="bi bi-receipt"></i></div>
                </div>
                <div class="stat-value text-dark font-mono"><small class="fs-6 text-muted">PKR</small> {{ number_format($totalInvoiced) }}</div>
                <small class="text-muted">Agreed Sales Total</small>
            </div>
        </div>

        <!-- Total Cash Collected / Realized -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Cash Collected</span>
                    <div class="stat-icon-wrap bg-success-subtle text-success"><i class="bi bi-cash-stack"></i></div>
                </div>
                <div class="stat-value text-success font-mono"><small class="fs-6 text-success">PKR</small> {{ number_format($totalRevenue) }}</div>
                <small class="text-muted">Realized App Revenue</small>
            </div>
        </div>

        <!-- Pending Receivables -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Pending Due</span>
                    <div class="stat-icon-wrap bg-danger-subtle text-danger"><i class="bi bi-exclamation-circle"></i></div>
                </div>
                <div class="stat-value text-danger font-mono"><small class="fs-6 text-danger">PKR</small> {{ number_format($totalPending) }}</div>
                <small class="text-muted">Receivable From Candidates</small>
            </div>
        </div>

        <!-- Investor Capital Payback -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Capital Returned</span>
                    <div class="stat-icon-wrap bg-warning-subtle text-warning"><i class="bi bi-arrow-return-left"></i></div>
                </div>
                <div class="stat-value text-dark font-mono"><small class="fs-6 text-muted">PKR</small> {{ number_format($totalCapitalReturned) }}</div>
                <small class="text-muted">{{ $capitalProgressPct }}% of Investment Recovered</small>
            </div>
        </div>

        <!-- Net Profit Pool -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card p-2.5 p-sm-3 h-100 border-start border-4 border-emerald" style="border-left-color: #10b981 !important;">
                <div class="d-flex justify-content-between align-items-center mb-1 gap-1">
                    <span class="stat-label text-truncate">Net Profit Pool</span>
                    <div class="stat-icon-wrap bg-success text-white"><i class="bi bi-pie-chart-fill"></i></div>
                </div>
                <div class="stat-value text-success font-mono"><small class="fs-6 text-success">PKR</small> {{ number_format($netProfitPool) }}</div>
                <small class="text-muted">Available for Partners</small>
            </div>
        </div>
    </div>

    <!-- 2. Multi-Party Distribution Breakdown (Party A vs Party B) -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold text-dark">
                        <i class="bi bi-diagram-3-fill me-1 text-primary"></i> Multi-Party Channel Distribution (Party A &amp; Party B)
                    </span>
                    <a href="{{ route('finance.parties') }}" class="small text-decoration-none">Manage Channels &rarr;</a>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        @forelse ($parties as $pty)
                            <div class="col-md-6">
                                <div class="p-3 rounded border bg-light h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold mb-0 text-dark">
                                            <span class="badge {{ $pty['code'] === 'PARTY_A' ? 'bg-primary' : 'bg-success' }} me-1 font-mono">{{ $pty['name'] }}</span>
                                        </h6>
                                        <span class="badge bg-white text-dark border font-mono">
                                            {{ $pty['sales_count'] }} Candidates
                                        </span>
                                    </div>

                                    <div class="mb-2">
                                        <small class="text-muted d-block">Revenue Realized:</small>
                                        <div class="fs-5 fw-bold text-success font-mono">PKR {{ number_format($pty['total_revenue']) }}</div>
                                    </div>

                                    <div class="d-flex justify-content-between small text-muted border-top pt-2">
                                        <span>Total Invoiced: <strong class="text-dark font-mono">PKR {{ number_format($pty['total_invoiced']) }}</strong></span>
                                        <span>Avg Sale: <strong class="text-dark font-mono">PKR {{ number_format($pty['average_price']) }}</strong></span>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        Pending Due: <strong class="text-danger font-mono">PKR {{ number_format($pty['pending_amount']) }}</strong>
                                    </div>

                                    @if (!empty($pty['partners']) && count($pty['partners']) > 0)
                                        <div class="mt-2 pt-2 border-top">
                                            <small class="text-muted d-block mb-1" style="font-size: 0.72rem;">Managing Investors / Partners:</small>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach ($pty['partners'] as $ptn)
                                                    <span class="badge {{ $ptn['type'] === 'investor' ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary border border-primary-subtle' }} font-mono py-1 px-1.5" style="font-size: 0.72rem;">
                                                        <i class="bi bi-person-fill me-0.5"></i>{{ $ptn['name'] }} ({{ $ptn['profit_share_pct'] }}%)
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted py-3">No sales parties registered yet.</div>
                        @endforelse
                    </div>

                    <!-- Visual Comparison Bar -->
                    @php
                        $partyTotalRev = $parties->sum('total_revenue');
                    @endphp
                    @if ($partyTotalRev > 0)
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Sales Share Ratio</span>
                                <span>Total: PKR {{ number_format($partyTotalRev) }}</span>
                            </div>
                            <div class="progress" style="height: 12px; border-radius: 6px;">
                                @foreach ($parties as $pty)
                                    @php
                                        $pct = $partyTotalRev > 0 ? round(($pty['total_revenue'] / $partyTotalRev) * 100, 1) : 0;
                                    @endphp
                                    <div class="progress-bar {{ $pty['code'] === 'PARTY_A' ? 'bg-primary' : 'bg-success' }}" 
                                         style="width: {{ $pct }}%" 
                                         title="{{ $pty['name'] }}: {{ $pct }}%">
                                        {{ $pct > 15 ? $pty['name'] . " ($pct%)" : '' }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Investor Capital Payback Progress Card -->
        <div class="col-12 col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="fw-bold text-dark">
                        <i class="bi bi-wallet2 me-1 text-warning"></i> Investor Capital Recovery
                    </span>
                    <a href="{{ route('finance.partners') }}" class="small text-decoration-none">Partner Ledger &rarr;</a>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <small class="text-muted d-block">Total Capital Invested</small>
                            <div class="fs-5 fw-bold text-dark font-mono">PKR {{ number_format($totalInvestedCapital) }}</div>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">Capital Returned</small>
                            <div class="fs-5 fw-bold text-success font-mono">PKR {{ number_format($totalCapitalReturned) }}</div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small fw-bold mb-1">
                            <span>Recovery Status</span>
                            <span class="{{ $capitalProgressPct == 100 ? 'text-success' : 'text-warning-emphasis' }}">{{ $capitalProgressPct }}% Complete</span>
                        </div>
                        <div class="progress" style="height: 10px; border-radius: 999px;">
                            <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: {{ $capitalProgressPct }}%"></div>
                        </div>
                    </div>

                    <div class="p-2.5 rounded bg-light border small">
                        <div class="d-flex justify-content-between text-muted mb-1">
                            <span>Outstanding Capital to Pay Back:</span>
                            <strong class="text-danger font-mono">PKR {{ number_format($outstandingCapital) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between text-muted">
                            <span>Surplus / Net Profit Available:</span>
                            <strong class="text-success font-mono">PKR {{ number_format($netProfitPool) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Partner Profit Sharing (%) Ledger -->
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <span class="fw-bold text-dark">
                        <i class="bi bi-pie-chart-fill me-1 text-success"></i> Partner &amp; Investor Dividend Distribution Pool
                    </span>
                    <a href="{{ route('finance.partners') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-lg me-1"></i> Manage Partners &amp; Record Payouts
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-matrix align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Partner / Investor</th>
                                <th>Role</th>
                                <th>Profit Share %</th>
                                <th>Invested Capital</th>
                                <th>Capital Returned</th>
                                <th>Accrued Profit Share</th>
                                <th>Profit Paid</th>
                                <th class="text-end">Net Balance Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($partners as $p)
                                <tr>
                                    <td class="fw-bold text-dark">
                                        {{ $p['name'] }}
                                        @if ($p['phone'])
                                            <small class="text-muted d-block fw-normal">{{ $p['phone'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $p['type'] === 'investor' ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary' }}">
                                            {{ ucfirst($p['type']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border font-mono fw-bold">{{ $p['profit_share_pct'] }}%</span>
                                    </td>
                                    <td class="font-mono">
                                        {{ $p['invested_capital'] > 0 ? 'PKR ' . number_format($p['invested_capital']) : '-' }}
                                    </td>
                                    <td class="font-mono text-success">
                                        {{ $p['capital_returned'] > 0 ? 'PKR ' . number_format($p['capital_returned']) : '-' }}
                                    </td>
                                    <td class="font-mono fw-bold text-dark">
                                        PKR {{ number_format($p['accrued_profit']) }}
                                    </td>
                                    <td class="font-mono text-muted">
                                        PKR {{ number_format($p['profit_paid']) }}
                                    </td>
                                    <td class="text-end font-mono fw-bold {{ $p['net_balance_due'] > 0 ? 'text-danger' : 'text-success' }}">
                                        PKR {{ number_format($p['net_balance_due']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">
                                        No partners or investors configured yet. <a href="{{ route('finance.partners') }}">Add Partner &rarr;</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Recent Candidate Sales Stream -->
    <div class="row g-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
                    <span class="fw-bold text-dark">
                        <i class="bi bi-clock-history me-1 text-info"></i> Recent Candidate App Sales
                    </span>
                    <a href="{{ route('finance.sales') }}" class="small text-decoration-none">View Full Sales Ledger &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-matrix align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Candidate</th>
                                <th>Assigned UC</th>
                                <th>Selling Party</th>
                                <th>Price Charged</th>
                                <th>Amount Paid</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSales as $s)
                                <tr>
                                    <td class="text-nowrap small text-muted font-mono">
                                        {{ $s->payment_date ? $s->payment_date->format('d M Y') : $s->created_at->format('d M Y') }}
                                    </td>
                                    <td class="fw-semibold">
                                        <a href="{{ route('candidates.show', $s->candidate_id) }}" class="text-decoration-none text-dark hover-primary">
                                            {{ $s->candidate->name ?? 'Candidate #' . $s->candidate_id }}
                                        </a>
                                        <small class="text-muted d-block">{{ $s->candidate->phone ?? '' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ $s->candidate->uc ? ($s->candidate->uc->tehsil?->name . ' - UC ' . ($s->candidate->uc->uc_no ?: $s->candidate->uc->id)) : 'General' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $s->party?->code === 'PARTY_A' ? 'bg-primary-subtle text-primary border border-primary-subtle' : ($s->party?->code === 'PARTY_B' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-dark border') }}">
                                            {{ $s->party->name ?? 'Direct Sale' }}
                                        </span>
                                    </td>
                                    <td class="font-mono fw-bold text-dark">
                                        PKR {{ number_format($s->sale_amount) }}
                                    </td>
                                    <td class="font-mono text-success fw-bold">
                                        PKR {{ number_format($s->amount_paid) }}
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
                                    <td>
                                        <span class="small text-muted">{{ $s->payment_method ?: 'Cash' }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                                        No candidate sales recorded yet. Register candidate to record a sale.
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
