<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div class="me-auto">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h4 class="page-title mb-0">Finance, Sales &amp; Partner Vault</h4>
            <span class="badge bg-warning text-dark font-mono px-2 py-1 shadow-xs">
                <i class="bi bi-shield-check me-1"></i> Vault Unlocked
            </span>
        </div>
        <small class="text-muted d-block mt-0.5">App Sales Tracking, Commercial Distribution Channels, Investor Capital Return &amp; Profit Sharing</small>
    </div>

    <div class="d-flex align-items-center flex-wrap gap-2">
        <a href="{{ route('candidates.create') }}" class="btn btn-primary shadow-sm btn-sm text-nowrap">
            <i class="bi bi-person-plus-fill me-1"></i> Sell App / Candidate
        </a>
        <form method="POST" action="{{ route('finance.lock') }}" class="d-inline m-0">
            @csrf
            <button type="submit" class="btn btn-outline-danger btn-sm shadow-sm text-nowrap" title="Lock Vault immediately">
                <i class="bi bi-lock-fill me-1"></i> Lock Vault
            </button>
        </form>
    </div>
</div>

<!-- Finance Suite Tabs -->
<div class="card shadow-sm border-0 mb-3 overflow-hidden">
    <div class="finance-nav-scroll bg-white border-bottom">
        <ul class="nav nav-tabs card-header-tabs m-0 px-2 pt-2 border-0">
            <li class="nav-item">
                <a class="nav-link fw-semibold py-2.5 px-3 {{ Request::routeIs('finance.index') ? 'active text-primary border-primary border-bottom-0' : 'text-secondary' }}" 
                   href="{{ route('finance.index') }}">
                    <i class="bi bi-speedometer2 me-1 text-primary"></i> Executive Matrix
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-semibold py-2.5 px-3 {{ Request::routeIs('finance.sales*') ? 'active text-success border-success border-bottom-0' : 'text-secondary' }}" 
                   href="{{ route('finance.sales') }}">
                    <i class="bi bi-receipt-cutoff me-1 text-success"></i> Sales Ledger
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-semibold py-2.5 px-3 {{ Request::routeIs('finance.parties*') ? 'active text-info border-info border-bottom-0' : 'text-secondary' }}" 
                   href="{{ route('finance.parties') }}">
                    <i class="bi bi-diagram-3 me-1 text-info"></i> Sales Parties (A &amp; B)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-semibold py-2.5 px-3 {{ Request::routeIs('finance.partners*') ? 'active text-warning-emphasis border-warning border-bottom-0' : 'text-secondary' }}" 
                   href="{{ route('finance.partners') }}">
                    <i class="bi bi-people-fill me-1 text-warning"></i> Partners &amp; Payback
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-semibold py-2.5 px-3 {{ Request::routeIs('finance.security*') ? 'active text-dark border-dark border-bottom-0' : 'text-muted' }}" 
                   href="{{ route('finance.security') }}">
                    <i class="bi bi-shield-lock me-1 text-secondary"></i> Vault Security
                </a>
            </li>
        </ul>
    </div>
</div>
