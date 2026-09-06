@extends('layouts.app')

@section('title', 'Add / Upload Polling Stations')

@section('content')
    @php
        $activeTab = request('tab', old('_tab', 'upload'));
        if ($errors->has('name') || $errors->has('gender') || $errors->has('address') || old('name')) {
            $activeTab = 'manual';
        }
    @endphp

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <a href="{{ route('polling-stations.index', ['uc_id' => $ucId]) }}" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to Polling Stations
            </a>
            <h4 class="page-title mb-0 mt-1">Add Polling Stations &amp; Scheme</h4>
            <small class="text-muted">Upload bulk Excel/CSV polling scheme or register individual polling stations</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('polling-stations.sample-template') }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Sample CSV
            </a>
            <a href="{{ route('polling-stations.mapping', ['uc_id' => $ucId]) }}" class="btn btn-outline-primary">
                <i class="bi bi-diagram-3 me-1"></i> Mapping Matrix
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <!-- TABS HEADER -->
                <div class="card-header bg-white p-0 border-bottom">
                    <ul class="nav nav-tabs card-header-tabs m-0 border-0" id="pollingStationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-3 px-4 fw-bold {{ $activeTab === 'upload' ? 'active text-success' : 'text-secondary' }}" 
                                    id="upload-tab" data-bs-toggle="tab" data-bs-target="#uploadTabPane" type="button" role="tab">
                                <i class="bi bi-file-earmark-spreadsheet-fill me-2 text-success"></i> Upload CSV / Excel File
                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-2">Recommended</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-3 px-4 fw-bold {{ $activeTab === 'manual' ? 'active text-primary' : 'text-secondary' }}" 
                                    id="manual-tab" data-bs-toggle="tab" data-bs-target="#manualTabPane" type="button" role="tab">
                                <i class="bi bi-pencil-square me-2 text-primary"></i> Single Station (Manual Entry)
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <div class="tab-content" id="pollingStationTabsContent">

                        <!-- ================= TAB 1: CSV / EXCEL UPLOAD ================= -->
                        <div class="tab-pane fade {{ $activeTab === 'upload' ? 'show active' : '' }}" id="uploadTabPane" role="tabpanel">
                            <form method="POST" action="{{ route('polling-stations.import') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="_tab" value="upload">

                                <div class="row g-4">
                                    <div class="col-lg-7">
                                        <!-- UC Selection -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Target Union Council (UC) <span class="text-danger">*</span></label>
                                            <select name="uc_id" class="form-select @error('uc_id') is-invalid @enderror" required>
                                                <option value="">-- Select Union Council --</option>
                                                
                                                @if(isset($pendingUcs) && $pendingUcs->isNotEmpty())
                                                    <optgroup label="⚠️ Pending UCs (0 Stations Uploaded)">
                                                        @foreach ($pendingUcs as $uc)
                                                            <option value="{{ $uc->id }}" {{ (old('uc_id', $ucId) == $uc->id) ? 'selected' : '' }}>
                                                                {{ $uc->tehsil->district->name ?? '' }} &bull; {{ $uc->tehsil->name ?? '' }} &bull; UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }} [Needs Upload]
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif

                                                @if(isset($completedUcs) && $completedUcs->isNotEmpty())
                                                    <optgroup label="✅ UCs with Existing Stations (Append / Update)">
                                                        @foreach ($completedUcs as $uc)
                                                            <option value="{{ $uc->id }}" {{ (old('uc_id', $ucId) == $uc->id) ? 'selected' : '' }}>
                                                                {{ $uc->tehsil->district->name ?? '' }} &bull; {{ $uc->tehsil->name ?? '' }} &bull; UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }} ({{ $uc->polling_stations_count }} stations)
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @else
                                                    @foreach ($ucs as $uc)
                                                        <option value="{{ $uc->id }}" {{ (old('uc_id', $ucId) == $uc->id) ? 'selected' : '' }}>
                                                            {{ $uc->tehsil->district->name ?? '' }} &bull; {{ $uc->tehsil->name ?? '' }} &bull; UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <!-- File Upload -->
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold">Select CSV / Excel File <span class="text-danger">*</span></label>
                                            <input type="file" name="file" class="form-control form-control-lg @error('file') is-invalid @enderror" accept=".csv,.txt,.xlsx,.xls" required>
                                            <div class="form-text">Supported formats: <strong>.csv, .xlsx, .xls</strong> (Max file size: 5 MB)</div>
                                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <!-- Template Download Action Box -->
                                        <div class="p-3 bg-light rounded border mb-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <div class="fw-bold text-dark"><i class="bi bi-file-earmark-excel text-success me-1"></i> Don't have the format?</div>
                                                    <small class="text-muted">Download our pre-formatted ECP Polling Scheme template with sample data.</small>
                                                </div>
                                                <a href="{{ route('polling-stations.sample-template') }}" class="btn btn-sm btn-success text-nowrap ms-2">
                                                    <i class="bi bi-download me-1"></i> Download Template
                                                </a>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-success px-4 py-2 fw-semibold">
                                                <i class="bi bi-upload me-1"></i> Upload &amp; Import Polling Stations
                                            </button>
                                            <a href="{{ route('polling-stations.index', ['uc_id' => $ucId]) }}" class="btn btn-outline-secondary px-3 py-2">
                                                Cancel
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Guidelines & Preview Column -->
                                    <div class="col-lg-5">
                                        <div class="card bg-light border-0 h-100">
                                            <div class="card-body p-3">
                                                <h6 class="fw-bold text-dark mb-2">
                                                    <i class="bi bi-info-circle-fill text-primary me-1"></i> Required Excel Columns
                                                </h6>
                                                <p class="small text-muted mb-2">Your spreadsheet should have a header row with these column names:</p>
                                                
                                                <div class="table-responsive bg-white rounded border mb-3">
                                                    <table class="table table-sm table-striped mb-0 small">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Column</th>
                                                                <th>Description</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td><code>station_no</code></td>
                                                                <td>Polling Station number (e.g. 1, 2)</td>
                                                            </tr>
                                                            <tr>
                                                                <td><code>name</code> <span class="text-danger">*</span></td>
                                                                <td>Building / School name</td>
                                                            </tr>
                                                            <tr>
                                                                <td><code>gender</code></td>
                                                                <td><code>male</code>, <code>female</code>, or <code>combined</code></td>
                                                            </tr>
                                                            <tr>
                                                                <td><code>block_codes</code></td>
                                                                <td>Census Block Code(s), comma-separated</td>
                                                            </tr>
                                                            <tr>
                                                                <td><code>address</code></td>
                                                                <td>Location / Street address</td>
                                                            </tr>
                                                            <tr>
                                                                <td><code>male_booths</code></td>
                                                                <td>Number of male booths (Optional)</td>
                                                            </tr>
                                                            <tr>
                                                                <td><code>female_booths</code></td>
                                                                <td>Number of female booths (Optional)</td>
                                                            </tr>
                                                            <tr>
                                                                <td><code>total_booths</code></td>
                                                                <td>Total booths (Auto-calculated)</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="alert alert-warning border-0 p-2 mb-0 small">
                                                    <i class="bi bi-lightbulb-fill text-warning me-1"></i>
                                                    <strong>Pro-Tip:</strong> If you include <code>block_codes</code> (e.g. <code>123456701, 123456702</code>), the system will automatically associate them with this station and route male/female voters accordingly!
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- ================= TAB 2: MANUAL ENTRY ================= -->
                        <div class="tab-pane fade {{ $activeTab === 'manual' ? 'show active' : '' }}" id="manualTabPane" role="tabpanel">
                            <form method="POST" action="{{ route('polling-stations.store') }}">
                                @csrf
                                <input type="hidden" name="_tab" value="manual">

                                <!-- UC SELECTION -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Target Union Council (UC) <span class="text-danger">*</span></label>
                                    <select name="uc_id" id="ucSelect" class="form-select @error('uc_id') is-invalid @enderror" required>
                                        <option value="">-- Select Union Council --</option>
                                        @foreach ($ucs as $uc)
                                            <option value="{{ $uc->id }}" {{ (old('uc_id', $ucId) == $uc->id) ? 'selected' : '' }}>
                                                {{ $uc->tehsil->district->name ?? '' }} &bull; {{ $uc->tehsil->name ?? '' }} &bull; UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <!-- STATION NO & NAME -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Station No <small class="text-muted">(Optional)</small></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">#</span>
                                            <input type="text" name="station_no" value="{{ old('station_no') }}" class="form-control font-mono @error('station_no') is-invalid @enderror" placeholder="e.g. 1">
                                        </div>
                                        @error('station_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label fw-semibold">Station Name &amp; Building <span class="text-danger">*</span></label>
                                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Govt Girls High School Chak 123" required>
                                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <!-- GENDER CATEGORY -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold d-block">Polling Station Gender / Category <span class="text-danger">*</span></label>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="card h-100 border p-3 cursor-pointer text-center gender-card" for="genderMale">
                                                <input class="form-check-input mb-2 mx-auto" type="radio" name="gender" id="genderMale" value="male" {{ old('gender') === 'male' ? 'checked' : '' }} required>
                                                <div class="fw-bold text-primary"><i class="bi bi-gender-male me-1"></i> Male (مردانہ)</div>
                                                <small class="text-muted" style="font-size: 0.78rem;">Exclusively for male voters of the block code(s)</small>
                                            </label>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="card h-100 border p-3 cursor-pointer text-center gender-card" for="genderFemale">
                                                <input class="form-check-input mb-2 mx-auto" type="radio" name="gender" id="genderFemale" value="female" {{ old('gender') === 'female' ? 'checked' : '' }}>
                                                <div class="fw-bold text-danger"><i class="bi bi-gender-female me-1"></i> Female (زنانہ)</div>
                                                <small class="text-muted" style="font-size: 0.78rem;">Exclusively for female voters of the block code(s)</small>
                                            </label>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="card h-100 border p-3 cursor-pointer text-center gender-card" for="genderCombined">
                                                <input class="form-check-input mb-2 mx-auto" type="radio" name="gender" id="genderCombined" value="combined" {{ old('gender', 'combined') === 'combined' ? 'checked' : '' }}>
                                                <div class="fw-bold text-success"><i class="bi bi-gender-ambiguous me-1"></i> Combined (مشترکہ)</div>
                                                <small class="text-muted" style="font-size: 0.78rem;">Serves both male and female voters with separate booths</small>
                                            </label>
                                        </div>
                                    </div>
                                    @error('gender') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- ASSIGNED BLOCK CODES (MULTI-SELECT) -->
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-semibold mb-0">
                                            <i class="bi bi-grid-3x3-gap me-1 text-primary"></i> Assign Census Block Codes (شماریاتی بلاک کوڈز)
                                        </label>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="selectAllBlocks">Select All</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAllBlocks">Deselect All</button>
                                        </div>
                                    </div>
                                    <div class="card bg-light border p-3" style="max-height: 220px; overflow-y: auto;" id="blockCodesContainer">
                                        <div class="row g-2" id="blockCodesList">
                                            @forelse ($blockCodes as $bc)
                                                <div class="col-md-4 col-sm-6">
                                                    <div class="form-check bg-white p-2 rounded border">
                                                        <input class="form-check-input ms-0 me-2 block-code-checkbox" type="checkbox" name="block_code_ids[]" value="{{ $bc->id }}" id="bc_{{ $bc->id }}" {{ (is_array(old('block_code_ids')) && in_array($bc->id, old('block_code_ids'))) ? 'checked' : '' }}>
                                                        <label class="form-check-label font-mono fw-bold" for="bc_{{ $bc->id }}" style="font-size: 0.85rem;">
                                                            {{ $bc->code }}
                                                            @if ($bc->area_name)
                                                                <small class="text-muted fw-normal d-block font-sans text-truncate" style="max-width: 170px;">{{ $bc->area_name }}</small>
                                                            @endif
                                                        </label>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="col-12 text-center text-muted py-3" id="noBlocksMsg">
                                                    No block codes found for this UC.
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        Checking block codes here will automatically set this station as their designated Male, Female, or Combined polling venue.
                                    </small>
                                    @error('block_code_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>

                                <!-- BOOTHS CONFIGURATION -->
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Male Booths <small class="text-muted">(مردانہ بوتھ)</small></label>
                                        <input type="number" name="male_booths" value="{{ old('male_booths') }}" min="0" class="form-control font-mono" placeholder="e.g. 2">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Female Booths <small class="text-muted">(زنانہ بوتھ)</small></label>
                                        <input type="number" name="female_booths" value="{{ old('female_booths') }}" min="0" class="form-control font-mono" placeholder="e.g. 2">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Total Booths <small class="text-muted">(کل بوتھ)</small></label>
                                        <input type="number" name="total_booths" value="{{ old('total_booths') }}" min="0" class="form-control font-mono" placeholder="Auto-summed">
                                    </div>
                                </div>

                                <!-- ADDRESS -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Address &amp; Location Details</label>
                                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="e.g. Main Street near Canal Road, Chak 123">{{ old('address') }}</textarea>
                                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('polling-stations.index', ['uc_id' => $ucId]) }}" class="btn btn-outline-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                        <i class="bi bi-save me-1"></i> Save Polling Station
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const ucSelect = document.getElementById('ucSelect');
                const listContainer = document.getElementById('blockCodesList');
                const selectAllBtn = document.getElementById('selectAllBlocks');
                const deselectAllBtn = document.getElementById('deselectAllBlocks');

                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', () => {
                        document.querySelectorAll('.block-code-checkbox').forEach(cb => cb.checked = true);
                    });
                }
                if (deselectAllBtn) {
                    deselectAllBtn.addEventListener('click', () => {
                        document.querySelectorAll('.block-code-checkbox').forEach(cb => cb.checked = false);
                    });
                }

                if (ucSelect && listContainer) {
                    ucSelect.addEventListener('change', function () {
                        const ucId = this.value;
                        if (!ucId) {
                            listContainer.innerHTML = '<div class="col-12 text-center text-muted py-3">Please select a UC first.</div>';
                            return;
                        }

                        listContainer.innerHTML = '<div class="col-12 text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading Block Codes...</div>';

                        fetch(`/ucs/${ucId}/block-codes`)
                            .then(r => r.json())
                            .then(data => {
                                if (!data || data.length === 0) {
                                    listContainer.innerHTML = '<div class="col-12 text-center text-muted py-3">No block codes found for this UC.</div>';
                                    return;
                                }

                                let html = '';
                                data.forEach(bc => {
                                    html += `
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check bg-white p-2 rounded border">
                                                <input class="form-check-input ms-0 me-2 block-code-checkbox" type="checkbox" name="block_code_ids[]" value="${bc.id}" id="bc_${bc.id}">
                                                <label class="form-check-label font-mono fw-bold" for="bc_${bc.id}" style="font-size: 0.85rem;">
                                                    ${bc.code}
                                                    ${bc.area_name ? `<small class="text-muted fw-normal d-block font-sans text-truncate" style="max-width: 170px;">${bc.area_name}</small>` : ''}
                                                </label>
                                            </div>
                                        </div>
                                    `;
                                });
                                listContainer.innerHTML = html;
                            })
                            .catch(() => {
                                listContainer.innerHTML = '<div class="col-12 text-center text-danger py-3">Failed to load block codes.</div>';
                            });
                    });
                }
            })();
        </script>
    @endpush
@endsection
