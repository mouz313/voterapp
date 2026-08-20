@extends('layouts.app')

@section('title', 'UC: ' . $uc->name)

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small text-muted mb-2">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('ucs.index') }}" class="text-decoration-none">UCs</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $uc->name }}</li>
        </ol>
    </nav>

    <!-- Header + stats -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                <div>
                    <h4 class="page-title mb-1">{{ $uc->name }}</h4>
                    <span class="text-muted">
                        <i class="bi bi-geo-alt me-1"></i>{{ $uc->tehsil->district->name ?? '' }}
                        <span class="mx-1">/</span> {{ $uc->tehsil->name ?? '' }}
                    </span>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('block-codes.create', ['uc_id' => $uc->id]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i> Block Code</a>
                    <a href="{{ route('polling-stations.create', ['uc_id' => $uc->id]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg me-1"></i> Polling Station</a>
                    <a href="{{ route('voters.create', ['uc_id' => $uc->id]) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i> Voter</a>
                </div>
            </div>

            <div class="row g-2 mt-2">
                <div class="col-4">
                    <div class="rounded-3 bg-light-subtle p-2 text-center">
                        <div class="fw-bold">{{ $uc->blockCodes->count() }}</div>
                        <div class="small text-muted">Block Codes</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="rounded-3 bg-light-subtle p-2 text-center">
                        <div class="fw-bold">{{ $uc->pollingStations->count() }}</div>
                        <div class="small text-muted">Polling Stations</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="rounded-3 bg-light-subtle p-2 text-center">
                        <div class="fw-bold">{{ $uc->voters->count() }}</div>
                        <div class="small text-muted">Voters</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3" id="ucTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#blockcodes" type="button">Block Codes</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#stations" type="button">Polling Stations</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#voters" type="button">Voters</button></li>
    </ul>

    <div class="tab-content">
        <!-- Block Codes -->
        <div class="tab-pane fade show active" id="blockcodes">
            @if ($uc->blockCodes->isEmpty())
                <div class="empty-state"><i class="bi bi-collection"></i><p class="mt-2 mb-0">No block codes yet.</p></div>
            @else
                <div class="row g-2">
                    @foreach ($uc->blockCodes as $bc)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card h-100">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="text-muted small">Block</div>
                                        <div class="fw-semibold">{{ $bc->code }}</div>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary">{{ $bc->voters->count() }} voters</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Polling Stations -->
        <div class="tab-pane fade" id="stations">
            @if ($uc->pollingStations->isEmpty())
                <div class="empty-state"><i class="bi bi-house-door"></i><p class="mt-2 mb-0">No polling stations yet.</p></div>
            @else
                <div class="row g-2">
                    @foreach ($uc->pollingStations as $ps)
                        <div class="col-12 col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="fw-semibold"><i class="bi bi-geo-alt me-1 text-primary"></i>{{ $ps->name }}</div>
                                        <span class="badge bg-success-subtle text-success">{{ $ps->voters->count() }} voters</span>
                                    </div>
                                    <div class="small text-muted mt-1">{{ $ps->address ?? 'No address' }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Voters -->
        <div class="tab-pane fade" id="voters">
            <div class="card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>CNIC</th><th>Name</th><th>Father</th><th>Block</th>
                                <th>Station</th><th>Gharana</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($voters as $v)
                                <tr>
                                    <td>{{ $v->formatted_cnic }}</td>
                                    <td>{{ $v->name }}</td>
                                    <td>{{ $v->father_name }}</td>
                                    <td>{{ $v->blockCode->code ?? '-' }}</td>
                                    <td>{{ $v->pollingStation->name ?? '-' }}</td>
                                    <td>{{ $v->gharana_no ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No voters yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">{{ $voters->links() }}</div>
            </div>
        </div>
    </div>
@endsection
