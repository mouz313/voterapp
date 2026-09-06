@extends('layouts.app')

@section('title', 'Polling Stations & Scheme')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0"><i class="bi bi-building-check me-2 text-primary"></i>Polling Stations &amp; Polling Scheme</h4>
            <small class="text-muted">Manage Male, Female &amp; Combined Polling Stations and assign them to Census Block Codes</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('polling-stations.mapping', ['uc_id' => $selectedUcId]) }}" class="btn btn-outline-success shadow-sm">
                <i class="bi bi-diagram-3-fill me-1"></i> Polling Scheme Mapping Matrix
            </a>
            <a href="{{ route('polling-stations.import.form') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Polling Scheme
            </a>
            <a href="{{ route('polling-stations.create', ['uc_id' => $selectedUcId]) }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Polling Station
            </a>
        </div>
    </div>

    <!-- GENDER FILTER PILLS -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <a href="{{ route('polling-stations.index', array_merge(request()->query(), ['gender' => null])) }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 border-start border-4 border-dark h-100 {{ !$selectedGender ? 'bg-light' : '' }}">
                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-muted d-block">All Stations</small>
                            <span class="fw-bold text-dark fs-5">{{ number_format($counts['total']) }}</span>
                        </div>
                        <i class="bi bi-buildings fs-3 text-secondary"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('polling-stations.index', array_merge(request()->query(), ['gender' => 'male'])) }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 border-start border-4 border-primary h-100 {{ $selectedGender === 'male' ? 'bg-primary-subtle' : '' }}">
                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-primary fw-semibold d-block">Male (مردانہ)</small>
                            <span class="fw-bold text-primary fs-5">{{ number_format($counts['male']) }}</span>
                        </div>
                        <i class="bi bi-gender-male fs-3 text-primary"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('polling-stations.index', array_merge(request()->query(), ['gender' => 'female'])) }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 border-start border-4 border-danger h-100 {{ $selectedGender === 'female' ? 'bg-danger-subtle' : '' }}">
                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-danger fw-semibold d-block">Female (زنانہ)</small>
                            <span class="fw-bold text-danger fs-5">{{ number_format($counts['female']) }}</span>
                        </div>
                        <i class="bi bi-gender-female fs-3 text-danger"></i>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('polling-stations.index', array_merge(request()->query(), ['gender' => 'combined'])) }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 border-start border-4 border-success h-100 {{ $selectedGender === 'combined' ? 'bg-success-subtle' : '' }}">
                    <div class="card-body p-2 d-flex align-items-center justify-content-between">
                        <div>
                            <small class="text-success fw-semibold d-block">Combined (مشترکہ)</small>
                            <span class="fw-bold text-success fs-5">{{ number_format($counts['combined']) }}</span>
                        </div>
                        <i class="bi bi-gender-ambiguous fs-3 text-success"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <form method="GET" class="row g-2 align-items-center">
                @if ($selectedGender)
                    <input type="hidden" name="gender" value="{{ $selectedGender }}">
                @endif
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1">Filter by Union Council (UC):</label>
                    <select name="uc_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- All Union Councils (UCs) --</option>
                        @foreach ($ucs as $uc)
                            <option value="{{ $uc->id }}" {{ (string)$uc->id === (string)$selectedUcId ? 'selected' : '' }}>
                                {{ $uc->tehsil->district->name ?? '' }} / {{ $uc->tehsil->name ?? '' }} / UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1">Search Station Name / No / Address:</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search by name, station number or address...">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="col-md-2 text-end d-flex align-items-end">
                    @if ($selectedUcId || $selectedGender || $search)
                        <a href="{{ route('polling-stations.index') }}" class="btn btn-sm btn-outline-secondary w-100 mt-auto">
                            <i class="bi bi-x-circle me-1"></i> Clear Filters
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;"># / No</th>
                        <th>Station Name</th>
                        <th style="width: 140px;">Type / Gender</th>
                        <th>Union Council</th>
                        <th>Assigned Block Codes</th>
                        <th style="width: 110px;">Booths</th>
                        <th style="width: 100px;">Voters</th>
                        <th class="text-end" style="width: 110px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stations as $ps)
                        <tr>
                            <td>
                                @if ($ps->station_no)
                                    <span class="badge bg-light text-dark border font-mono">#{{ $ps->station_no }}</span>
                                @else
                                    <span class="text-muted small">#{{ $ps->id }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold text-dark d-block">{{ $ps->name }}</span>
                                @if ($ps->address)
                                    <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ Str::limit($ps->address, 45) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $ps->gender_badge_color }}-subtle text-{{ $ps->gender_badge_color }} border border-{{ $ps->gender_badge_color }}-subtle px-2 py-1 font-mono">
                                    <i class="bi {{ $ps->gender_icon }} me-1"></i>{{ $ps->gender_label_ur }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $ps->uc->name ?? '—' }} (UC {{ $ps->uc->uc_no ?? $ps->uc_id }})
                                </span>
                                <small class="text-muted d-block">{{ $ps->uc->tehsil->name ?? '' }}</small>
                            </td>
                            <td>
                                @php
                                    $assignedBlocks = $ps->assigned_block_codes;
                                @endphp
                                @if ($assignedBlocks->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($assignedBlocks->take(4) as $b)
                                            <span class="badge bg-secondary-subtle text-dark border font-mono" style="font-size: 0.72rem;">
                                                {{ $b->code }}
                                            </span>
                                        @endforeach
                                        @if ($assignedBlocks->count() > 4)
                                            <span class="badge bg-light text-muted border font-mono" style="font-size: 0.72rem;">
                                                +{{ $assignedBlocks->count() - 4 }} more
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted small italic">Not mapped yet</span>
                                @endif
                            </td>
                            <td>
                                @if ($ps->total_booths || $ps->male_booths || $ps->female_booths)
                                    <span class="small font-mono d-block">
                                        Total: <strong>{{ $ps->total_booths ?: (($ps->male_booths ?? 0) + ($ps->female_booths ?? 0)) }}</strong>
                                    </span>
                                    <small class="text-muted" style="font-size: 0.72rem;">
                                        M: {{ $ps->male_booths ?? '—' }} | F: {{ $ps->female_booths ?? '—' }}
                                    </small>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-primary border font-mono">
                                    {{ number_format($ps->voters_count) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('polling-stations.edit', $ps) }}" class="btn btn-sm btn-outline-secondary" title="Edit Polling Station">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('polling-stations.destroy', $ps) }}" class="d-inline" onsubmit="return confirm('WARNING: Are you sure you want to delete this polling station?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete Station">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-building-x fs-2 d-block text-secondary mb-2"></i>
                                No polling stations found matching the criteria.
                                <div class="mt-2">
                                    <a href="{{ route('polling-stations.create', ['uc_id' => $selectedUcId]) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg me-1"></i> Add Polling Station
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($stations->hasPages())
            <div class="card-footer bg-white py-2">
                {{ $stations->links() }}
            </div>
        @endif
    </div>
@endsection

