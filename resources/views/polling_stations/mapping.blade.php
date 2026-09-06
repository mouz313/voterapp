@extends('layouts.app')

@section('title', 'Polling Scheme Mapping Matrix')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0"><i class="bi bi-diagram-3-fill me-2 text-primary"></i>Polling Scheme Mapping Matrix</h4>
            <small class="text-muted">Map Census Block Codes to designated Male (مردانہ) and Female (زنانہ) Polling Stations</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($selectedUc)
                <form method="POST" action="{{ route('polling-stations.sync-voters') }}" class="d-inline" onsubmit="return confirm('Synchronize all voters in this UC to their designated Male/Female polling stations based on CNIC parity?');">
                    @csrf
                    <input type="hidden" name="uc_id" value="{{ $selectedUc->id }}">
                    <button type="submit" class="btn btn-outline-primary shadow-sm" title="Re-sync voters with designated stations">
                        <i class="bi bi-arrow-repeat me-1"></i> Sync Voters to Stations
                    </button>
                </form>
            @endif
            <a href="{{ route('polling-stations.index', ['uc_id' => $selectedUcId]) }}" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Polling Stations List
            </a>
            <a href="{{ route('polling-stations.create', ['uc_id' => $selectedUcId]) }}" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Station
            </a>
        </div>
    </div>

    <!-- UC SELECTION CARD -->
    <div class="card shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('polling-stations.mapping') }}" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <label class="form-label fw-semibold small text-muted mb-1">Select Target Union Council (UC):</label>
                    <select name="uc_id" class="form-select" onchange="this.form.submit()">
                        @foreach ($ucs as $uc)
                            <option value="{{ $uc->id }}" {{ (string)$uc->id === (string)$selectedUcId ? 'selected' : '' }}>
                                {{ $uc->tehsil->district->name ?? '' }} &bull; {{ $uc->tehsil->name ?? '' }} &bull; UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }} ({{ $uc->block_codes_count }} Blocks, {{ $uc->polling_stations_count }} Stations)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 text-md-end mt-md-4">
                    <span class="badge bg-light text-dark border p-2 font-mono">
                        {{ $totalBlocks }} Census Blocks &bull; {{ $allStations->count() }} Stations in UC
                    </span>
                </div>
            </form>
        </div>
    </div>

    @if ($selectedUc)
        <!-- KPI SUMMARY CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 border-start border-4 border-primary h-100">
                    <div class="card-body p-3">
                        <small class="text-muted d-block">Total Census Blocks</small>
                        <span class="fs-4 fw-bold font-mono text-dark">{{ $totalBlocks }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                    <div class="card-body p-3">
                        <small class="text-muted d-block">Fully Mapped (M &amp; F)</small>
                        <span class="fs-4 fw-bold font-mono text-success">{{ $fullyMapped }}</span>
                        @if ($totalBlocks > 0)
                            <small class="text-muted ms-1">({{ round(($fullyMapped / $totalBlocks) * 100) }}%)</small>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
                    <div class="card-body p-3">
                        <small class="text-muted d-block">Pending Assignment</small>
                        <span class="fs-4 fw-bold font-mono text-warning">{{ $pendingBlocks }}</span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 border-start border-4 border-info h-100">
                    <div class="card-body p-3">
                        <small class="text-muted d-block">UC Stations Pool</small>
                        <span class="fs-4 fw-bold font-mono text-info">{{ $allStations->count() }}</span>
                        <small class="text-muted d-block" style="font-size: 0.72rem;">
                            {{ $maleStations->where('gender', 'male')->count() }} M | {{ $femaleStations->where('gender', 'female')->count() }} F | {{ $allStations->where('gender', 'combined')->count() }} Comb
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAPPING MATRIX FORM -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-success"></i>Block Code to Polling Station Assignments</h6>
                    <small class="text-muted">Specify which venue male voters and female voters report to for each 9-digit block code</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="bulkCopyMaleToFemale">
                        <i class="bi bi-copy me-1"></i> Copy All Male &rarr; Female
                    </button>
                    <button type="submit" form="mappingForm" class="btn btn-sm btn-success px-3 shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Save Polling Scheme
                    </button>
                </div>
            </div>

            @if ($allStations->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="bi bi-building-exclamation fs-1 text-warning d-block mb-2"></i>
                    <h5>No Polling Stations in this UC</h5>
                    <p class="text-muted small">Please create or import polling stations for this Union Council before mapping block codes.</p>
                    <a href="{{ route('polling-stations.create', ['uc_id' => $selectedUc->id]) }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add First Polling Station
                    </a>
                    <a href="{{ route('polling-stations.import.form') }}" class="btn btn-outline-secondary ms-2">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import Polling Scheme
                    </a>
                </div>
            @elseif ($blockCodes->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="bi bi-grid-3x3 fs-1 text-secondary d-block mb-2"></i>
                    <h5>No Census Block Codes in this UC</h5>
                    <p class="text-muted small">Add or import Census Block Codes for this Union Council first.</p>
                    <a href="{{ route('block-codes.create', ['uc_id' => $selectedUc->id]) }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Block Code
                    </a>
                </div>
            @else
                <form method="POST" action="{{ route('polling-stations.mapping.update') }}" id="mappingForm">
                    @csrf
                    <input type="hidden" name="uc_id" value="{{ $selectedUc->id }}">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 140px;">Block Code</th>
                                    <th>Area / Neighborhood</th>
                                    <th style="width: 90px;">Voters</th>
                                    <th style="width: 300px;">
                                        <div class="d-flex align-items-center text-primary">
                                            <i class="bi bi-gender-male me-1"></i> Male Station (مردانہ)
                                        </div>
                                    </th>
                                    <th style="width: 300px;">
                                        <div class="d-flex align-items-center text-danger">
                                            <i class="bi bi-gender-female me-1"></i> Female Station (زنانہ)
                                        </div>
                                    </th>
                                    <th style="width: 80px;" class="text-center">Action</th>
                                    <th style="width: 110px;" class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($blockCodes as $bc)
                                    <tr>
                                        <td>
                                            <span class="fw-bold font-mono text-dark d-block">{{ $bc->code }}</span>
                                            <small class="text-muted">ID: {{ $bc->id }}</small>
                                        </td>
                                        <td>
                                            <span class="text-dark">{{ $bc->area_name ?: '—' }}</span>
                                            @if ($bc->area_name_ur)
                                                <small class="text-muted d-block font-sans" dir="rtl">{{ $bc->area_name_ur }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-mono">
                                                {{ number_format($bc->voters_count) }}
                                            </span>
                                        </td>
                                        <td>
                                            <select name="mappings[{{ $bc->id }}][male_station_id]" class="form-select form-select-sm male-station-select font-sans" data-block="{{ $bc->id }}">
                                                <option value="">-- Select Male Station --</option>
                                                @foreach ($maleStations as $st)
                                                    <option value="{{ $st->id }}" {{ (string)$bc->male_polling_station_id === (string)$st->id ? 'selected' : '' }}>
                                                        {{ $st->station_no ? "#{$st->station_no} - " : '' }}{{ $st->name }} ({{ $st->gender_label_ur }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="mappings[{{ $bc->id }}][female_station_id]" class="form-select form-select-sm female-station-select font-sans" id="female_station_{{ $bc->id }}">
                                                <option value="">-- Select Female Station --</option>
                                                @foreach ($femaleStations as $st)
                                                    <option value="{{ $st->id }}" {{ (string)$bc->female_polling_station_id === (string)$st->id ? 'selected' : '' }}>
                                                        {{ $st->station_no ? "#{$st->station_no} - " : '' }}{{ $st->name }} ({{ $st->gender_label_ur }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-secondary copy-row-btn" title="Copy Male Station to Female" data-block="{{ $bc->id }}">
                                                <i class="bi bi-arrow-right"></i>
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            @if ($bc->is_fully_mapped)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Mapped
                                                </span>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Pending
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer bg-white py-3 d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i> After saving mappings, use the <strong>Sync Voters</strong> button above to auto-assign existing voters by CNIC gender.
                        </span>
                        <button type="submit" class="btn btn-success px-4 shadow-sm">
                            <i class="bi bi-save me-1"></i> Save Polling Scheme Mappings
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    @push('scripts')
        <script>
            (function () {
                // Copy Male selection to Female selection for a specific row
                document.querySelectorAll('.copy-row-btn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const blockId = this.getAttribute('data-block');
                        const maleSelect = document.querySelector(`.male-station-select[data-block="${blockId}"]`);
                        const femaleSelect = document.getElementById(`female_station_${blockId}`);
                        if (maleSelect && femaleSelect && maleSelect.value) {
                            femaleSelect.value = maleSelect.value;
                        }
                    });
                });

                // Bulk Copy All Male to Female
                const bulkBtn = document.getElementById('bulkCopyMaleToFemale');
                if (bulkBtn) {
                    bulkBtn.addEventListener('click', function () {
                        if (!confirm('Copy all selected Male Polling Stations to Female Polling Stations? (Ideal when polling stations are combined venues)')) {
                            return;
                        }

                        document.querySelectorAll('.male-station-select').forEach(maleSelect => {
                            const blockId = maleSelect.getAttribute('data-block');
                            const femaleSelect = document.getElementById(`female_station_${blockId}`);
                            if (femaleSelect && maleSelect.value) {
                                femaleSelect.value = maleSelect.value;
                            }
                        });
                    });
                }
            })();
        </script>
    @endpush
@endsection
