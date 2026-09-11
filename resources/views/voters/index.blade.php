@extends('layouts.app')

@section('title', 'Voters Directory')

@section('content')
    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Voters Directory</h4>
            <small class="text-muted">Manage registered voters, polling assignments, and verified contact details</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('public.parchi') }}" target="_blank" class="btn btn-outline-success">
                <i class="bi bi-box-arrow-up-right me-1"></i> Public Slip Portal
            </a>
            <a href="{{ route('voters.import.form') }}" class="btn btn-outline-secondary">
                <i class="bi bi-upload me-1"></i> Import List
            </a>
            <a href="{{ route('voters.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus-fill me-1"></i> Add Voter
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3 col-sm-6">
                    <select name="uc_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Union Councils (UCs)</option>
                        @foreach ($ucs as $uc)
                            <option value="{{ $uc->id }}" {{ $uc->id == request('uc_id') ? 'selected' : '' }}>
                                {{ $uc->tehsil->district->name ?? '' }} &bull; {{ $uc->tehsil->name ?? '' }} &bull; {{ $uc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search Name, Father, CNIC, Phone, Silsala or Gharana...">
                        <button class="btn btn-outline-secondary" type="submit">Filter</button>
                    </div>
                </div>
                @if (request('search') || request('uc_id'))
                    <div class="col-auto">
                        <a href="{{ route('voters.index') }}" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x-circle me-1"></i> Clear
                        </a>
                    </div>
                @endif
                <div class="col-auto ms-auto d-flex align-items-center gap-2">
                    <span class="text-muted small">Show:</span>
                    <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        @foreach ([15, 50, 100, 1000] as $opt)
                            <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt == 1000 ? 'All' : $opt }}</option>
                        @endforeach
                    </select>
                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1.5 px-2">
                        Total: {{ number_format($voters->total()) }}
                    </span>
                </div>
            </form>
        </div>

        <!-- Voters Data Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 170px;">Voter Details</th>
                        <th style="min-width: 130px;">CNIC</th>
                        <th style="min-width: 130px;">Phone / Mobile</th>
                        <th class="text-center">Age</th>
                        <th>Silsala / Gharana</th>
                        <th>Block Code</th>
                        <th style="min-width: 180px;">Polling Station</th>
                        <th>Union Council</th>
                        <th class="text-end" style="min-width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($voters as $voter)
                        <tr class="{{ $voter->name === 'No Vote' ? 'table-light text-muted' : '' }}">
                            <!-- Voter Name & Father -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center text-primary fw-bold flex-shrink-0" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                        {{ strtoupper(substr($voter->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('voters.show', $voter) }}" class="fw-semibold text-dark text-decoration-none hover-primary">
                                            {{ $voter->name }}
                                        </a>
                                        <small class="text-muted d-block" style="font-size: 0.78rem;">
                                            {{ $voter->father_name ?: '—' }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <!-- CNIC -->
                            <td>
                                <span class="font-monospace fw-semibold text-primary" style="letter-spacing: 0.5px;">
                                    {{ $voter->formatted_cnic }}
                                </span>
                            </td>

                            <!-- Phone / WhatsApp -->
                            <td>
                                @if(!empty($voter->phone))
                                    @php
                                        $cleanWa = preg_replace('/[^\d]/', '', $voter->phone);
                                        if (str_starts_with($cleanWa, '0')) {
                                            $cleanWa = '92' . substr($cleanWa, 1);
                                        }
                                    @endphp
                                    <a href="https://wa.me/{{ $cleanWa }}?text=Assalam-o-Alaikum%20{{ urlencode($voter->name) }}" 
                                       target="_blank" 
                                       class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none d-inline-flex align-items-center gap-1 py-1 px-2"
                                       title="Chat with {{ $voter->name }} on WhatsApp">
                                        <i class="bi bi-whatsapp"></i> {{ $voter->phone }}
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            <!-- Age -->
                            <td class="text-center">
                                @if($voter->age)
                                    <span class="badge bg-light text-dark border">{{ $voter->age }} yrs</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>

                            <!-- Silsala & Gharana -->
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-light text-dark border font-monospace" title="Silsala No">
                                        S: {{ $voter->silsala_no ?: '-' }}
                                    </span>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace" title="Gharana No">
                                        G: {{ $voter->gharana_no ?: '-' }}
                                    </span>
                                </div>
                            </td>

                            <!-- Block Code -->
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border font-monospace">
                                    {{ $voter->blockCode->code ?? '-' }}
                                </span>
                            </td>

                            <!-- Polling Station -->
                            <td>
                                <div class="text-truncate" style="max-width: 220px;" title="{{ $voter->pollingStation->name ?? 'Not Assigned' }}">
                                    <i class="bi bi-geo-alt text-danger me-1"></i>
                                    <span class="small fw-semibold">{{ $voter->pollingStation->name ?? '—' }}</span>
                                </div>
                            </td>

                            <!-- UC -->
                            <td>
                                <span class="small text-muted">{{ $voter->uc->name ?? '—' }}</span>
                            </td>

                            <!-- Actions -->
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('voters.show', $voter) }}" class="btn btn-outline-primary" title="View Voter Slip & Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('voters.edit', $voter) }}" class="btn btn-outline-secondary" title="Edit Voter">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if(auth()->user()->canDelete())
                                        <form method="POST" action="{{ route('voters.destroy', $voter) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this voter?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger" title="Delete Voter">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="bi bi-people display-6 text-secondary d-block mb-2"></i>
                                <h6 class="mb-1">No voters found</h6>
                                <p class="small text-muted mb-0">Try clearing your search query or selecting another Union Council.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination & Meta -->
        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-2.5">
            <span class="text-muted small">
                Showing {{ $voters->firstItem() ?? 0 }}–{{ $voters->lastItem() ?? 0 }} of {{ number_format($voters->total()) }} voters
            </span>
            <div>
                {{ $voters->links() }}
            </div>
        </div>
    </div>
@endsection
