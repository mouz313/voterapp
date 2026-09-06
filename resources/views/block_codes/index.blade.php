@extends('layouts.app')

@section('title', 'Census Block Codes')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Census Block Codes & Electoral Areas</h4>
            <small class="text-muted">ECP Delimitation mapping: Tehsil &rarr; UC &rarr; Census Block Code & Area Extent</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('polling-stations.mapping', ['uc_id' => $selectedUcId]) }}" class="btn btn-outline-primary">
                <i class="bi bi-diagram-3-fill me-1"></i> Polling Scheme Mapping Matrix
            </a>
            <a href="{{ route('block-codes.import.form') }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Delimitation List (CSV/Excel)
            </a>
            <a href="{{ route('block-codes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Block Code
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center" id="filterForm">
                <!-- Tehsil Filter -->
                <div class="col-md-3">
                    <select name="tehsil_id" id="tehsilFilter" class="form-select" onchange="onTehsilChange()">
                        <option value="">-- All Tehsils --</option>
                        @foreach ($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}" {{ $selectedTehsilId == $tehsil->id ? 'selected' : '' }}>
                                {{ $tehsil->district->name ?? 'District' }} &bull; {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- UC Filter (Scoped to Selected Tehsil) -->
                <div class="col-md-3">
                    <select name="uc_id" id="ucFilter" class="form-select" onchange="this.form.submit()">
                        <option value="">
                            {{ $selectedTehsilId ? '-- All UCs in this Tehsil (' . count($ucs) . ' UCs) --' : '-- All UCs (Select Tehsil first to filter) --' }}
                        </option>
                        @foreach ($ucs as $uc)
                            <option value="{{ $uc->id }}" {{ $selectedUcId == $uc->id ? 'selected' : '' }}>
                                UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }} {{ $uc->name_ur ? '(' . $uc->name_ur . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input -->
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by Block Code (e.g. 260250202) or Area..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </div>

                @if(request()->hasAny(['tehsil_id', 'uc_id', 'search']))
                    <div class="col-auto">
                        <a href="{{ route('block-codes.index') }}" class="btn btn-outline-danger btn-sm">Clear Filter</a>
                    </div>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-matrix align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 140px;">Census Block</th>
                        <th>Extent of Union Council (Electoral Area / Mohallah)</th>
                        <th>Union Council (UC)</th>
                        <th>Tehsil</th>
                        <th>Designated Polling Stations</th>
                        <th>Population</th>
                        <th>Voters Registered</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blockCodes as $bc)
                        <tr>
                            <td>
                                <span class="badge bg-dark font-mono fs-6 px-2 py-1">{{ $bc->code }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $bc->area_name ?: 'Electoral Area' }}</div>
                                @if($bc->area_name_ur)
                                    <small class="text-muted font-urdu d-block" style="font-family: 'Noto Nastaliq Urdu', serif;">{{ $bc->area_name_ur }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary">UC {{ $bc->uc->uc_no ?? '-' }}</span>
                                <small class="text-muted d-block">{{ $bc->uc->name ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $bc->uc->tehsil->name ?? '-' }}</span>
                                <small class="text-muted d-block">{{ $bc->uc->tehsil->district->name ?? '' }}</small>
                            </td>
                            <td>
                                @if ($bc->malePollingStation || $bc->femalePollingStation)
                                    @if ($bc->malePollingStation)
                                        <div class="small mb-1">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-mono" style="font-size: 0.72rem;">
                                                <i class="bi bi-gender-male me-1"></i>{{ Str::limit($bc->malePollingStation->name, 25) }}
                                            </span>
                                        </div>
                                    @endif
                                    @if ($bc->femalePollingStation && $bc->female_polling_station_id !== $bc->male_polling_station_id)
                                        <div class="small">
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle font-mono" style="font-size: 0.72rem;">
                                                <i class="bi bi-gender-female me-1"></i>{{ Str::limit($bc->femalePollingStation->name, 25) }}
                                            </span>
                                        </div>
                                    @elseif ($bc->female_polling_station_id === $bc->male_polling_station_id)
                                        <small class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-check2-all text-success me-1"></i>Same for Female</small>
                                    @endif
                                @else
                                    <a href="{{ route('polling-stations.mapping', ['uc_id' => $bc->uc_id]) }}" class="badge bg-warning-subtle text-warning border border-warning-subtle text-decoration-none" title="Assign stations in mapping matrix">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Unmapped
                                    </a>
                                @endif
                            </td>
                            <td>
                                @if($bc->population)
                                    <span class="badge bg-light text-dark border">{{ number_format($bc->population) }}</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                @if($bc->voters_count > 0)
                                    <span class="badge bg-success-subtle text-success fw-bold">
                                        <i class="bi bi-person-check me-1"></i>{{ number_format($bc->voters_count) }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">0</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('block-codes.edit', $bc) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('block-codes.destroy', $bc) }}" class="d-inline" onsubmit="return confirm('Delete this block code?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No Census Block Codes found for the selected filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $blockCodes->links() }}</div>
    </div>

    <script>
        function onTehsilChange() {
            // When tehsil changes, reset the uc_id filter so it doesn't try to query an old UC from previous tehsil
            const ucFilter = document.getElementById('ucFilter');
            if (ucFilter) {
                ucFilter.value = '';
            }
            document.getElementById('filterForm').submit();
        }
    </script>
@endsection
