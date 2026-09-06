<!-- Query Distribution Grid (Responsive 2x2 on Mobile, 4x1 on Tablet/Desktop) -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="p-2 rounded bg-light border h-100 d-flex flex-column justify-content-between">
            <div class="small text-muted d-flex align-items-center justify-content-between">
                <span class="text-truncate" style="font-size: 0.76rem;">CNIC Searches</span>
                <i class="bi bi-person-vcard text-primary flex-shrink-0 ms-1"></i>
            </div>
            <div class="fs-6 fw-bold text-dark font-mono mt-1">{{ number_format($searchBreakdown['cnic'] ?? 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-2 rounded bg-light border h-100 d-flex flex-column justify-content-between">
            <div class="small text-muted d-flex align-items-center justify-content-between">
                <span class="text-truncate" style="font-size: 0.76rem;">Name Searches</span>
                <i class="bi bi-fonts text-success flex-shrink-0 ms-1"></i>
            </div>
            <div class="fs-6 fw-bold text-dark font-mono mt-1">{{ number_format($searchBreakdown['name'] ?? 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-2 rounded bg-light border h-100 d-flex flex-column justify-content-between">
            <div class="small text-muted d-flex align-items-center justify-content-between">
                <span class="text-truncate" style="font-size: 0.76rem;">Gharana Queries</span>
                <i class="bi bi-house-door text-warning flex-shrink-0 ms-1"></i>
            </div>
            <div class="fs-6 fw-bold text-dark font-mono mt-1">{{ number_format($searchBreakdown['gharana'] ?? 0) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="p-2 rounded bg-light border h-100 d-flex flex-column justify-content-between">
            <div class="small text-muted d-flex align-items-center justify-content-between">
                <span class="text-truncate" style="font-size: 0.76rem;">Silsala Queries</span>
                <i class="bi bi-hash text-danger flex-shrink-0 ms-1"></i>
            </div>
            <div class="fs-6 fw-bold text-dark font-mono mt-1">{{ number_format($searchBreakdown['silsala'] ?? 0) }}</div>
        </div>
    </div>
</div>

<!-- Recent Searches Table -->
<div class="table-responsive">
    <table class="table table-matrix align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="text-nowrap">Time</th>
                <th>Candidate / User</th>
                <th>Union Council</th>
                <th>Last Query</th>
                <th class="text-end text-nowrap">Total Processed</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recentSearches as $rs)
                <tr>
                    <td class="text-nowrap"><small class="text-muted"><i class="bi bi-clock me-1"></i>{{ $rs->searched_at ? $rs->searched_at->diffForHumans() : '-' }}</small></td>
                    <td class="fw-semibold">
                        <div class="d-flex align-items-center text-truncate" style="max-width: 170px;">
                            <span class="badge bg-primary-subtle text-primary me-1 flex-shrink-0"><i class="bi bi-person"></i></span>
                            <span class="text-truncate">{{ $rs->user->name ?? 'Mobile Agent' }}</span>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark border text-truncate" style="max-width: 140px;">{{ $rs->uc->name ?? 'General' }}</span></td>
                    <td>
                        <span class="badge bg-secondary-subtle text-dark text-uppercase font-mono">{{ $rs->query_type ?: 'General' }}</span>
                    </td>
                    <td class="text-end text-nowrap">
                        <span class="badge bg-success-subtle text-success font-mono fw-bold">
                            <i class="bi bi-check2-circle me-1"></i>{{ number_format($rs->results_count) }} Searches
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">
                        <i class="bi bi-inbox text-secondary fs-4 d-block mb-1"></i>
                        No field search logs recorded yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
