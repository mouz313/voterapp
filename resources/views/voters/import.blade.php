@extends('layouts.app')

@section('title', 'Import Voters')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Import Voters List (CSV / Excel)</h4>
            <small class="text-muted">Upload official electoral rolls to parse voters, family trees (gharana), and silsala numbers</small>
        </div>
        <a href="{{ route('voters.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Voters
        </a>
    </div>

    <!-- TOP STATUS SUMMARY: UPLOADED VS PENDING UCS -->
    <div class="row g-3 mb-4">
        <!-- Completed UCs -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i> UCs with Uploaded Voters</span>
                        <span class="badge bg-success font-mono">{{ $completedUcs->count() }} UCs</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1" style="max-height: 100px; overflow-y: auto;">
                        @forelse ($completedUcs as $cu)
                            <span class="badge bg-success-subtle text-success border border-success-subtle p-1.5" style="font-size: 0.73rem;">
                                {{ $cu->tehsil->name ?? '' }} - UC {{ $cu->uc_no ?: $cu->id }}: <strong>{{ number_format($cu->voters_count) }} voters</strong>
                            </span>
                        @empty
                            <span class="text-muted small">No UCs uploaded yet. Upload a pending UC below.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending UCs -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i> Pending UCs (0 Voters Uploaded)</span>
                        <span class="badge bg-warning text-dark font-mono">{{ $pendingUcs->count() }} Pending</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1" style="max-height: 100px; overflow-y: auto;">
                        @forelse ($pendingUcs as $pu)
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle p-1.5" style="font-size: 0.73rem;">
                                <i class="bi bi-hourglass me-1"></i>{{ $pu->tehsil->name ?? '' }} - UC {{ $pu->uc_no ?: $pu->id }} ({{ $pu->name }})
                            </span>
                        @empty
                            <span class="badge bg-success text-white"><i class="bi bi-check2-all me-1"></i> All UCs Have Voter Data!</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN IMPORT FORM -->
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-upload me-2 text-success"></i>Upload Voter List Data</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('voters.import.preview') }}" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Union Council (UC) <span class="text-danger">*</span></label>
                            <select name="uc_id" id="ucSelectImport" class="form-select @error('uc_id') is-invalid @enderror" required autofocus>
                                <option value="">-- Select Union Council --</option>
                                
                                @if($pendingUcs->isNotEmpty())
                                    <optgroup label="⚠️ Pending UCs (Needs Voter Data Upload)">
                                        @foreach ($pendingUcs as $uc)
                                            <option value="{{ $uc->id }}" {{ (old('uc_id') == $uc->id) ? 'selected' : '' }} class="fw-bold">
                                                {{ $uc->tehsil->name ?? '' }} &bull; UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }} [Upload Needed]
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if($completedUcs->isNotEmpty())
                                    <optgroup label="✅ Already Uploaded UCs (Update or Add More)">
                                        @foreach ($completedUcs as $uc)
                                            <option value="{{ $uc->id }}" {{ (old('uc_id') == $uc->id) ? 'selected' : '' }}>
                                                {{ $uc->tehsil->name ?? '' }} &bull; UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }} ({{ number_format($uc->voters_count) }} voters)
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text small">All voters in this spreadsheet will be strictly scoped under this Union Council.</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Force Block Code (Optional)</label>
                                <select name="block_code_id" id="blockSelect" class="form-select @error('block_code_id') is-invalid @enderror">
                                    <option value="">— Auto-detect per row from file —</option>
                                </select>
                                @error('block_code_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Force Polling Station (Optional)</label>
                                <select name="polling_station_id" id="stationSelect" class="form-select @error('polling_station_id') is-invalid @enderror">
                                    <option value="">— Auto-detect per row from file —</option>
                                </select>
                                @error('polling_station_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select CSV / Excel File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt,.xlsx,.xls" required>
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="alert alert-light border small my-3">
                            <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Expected Header Columns:</div>
                            <code>cnic, name, father_name, age, silsala_no, gharana_no, block_code, polling_station, address</code>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('voters.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4" id="importSubmitBtn">
                                <i class="bi bi-eye me-1"></i> Preview & Validate Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ucSelectImport = document.getElementById('ucSelectImport');
        const blockSelect = document.getElementById('blockSelect');
        const stationSelect = document.getElementById('stationSelect');
        let ucIdValue = '';

        function loadImportOption(url, selectEl, placeholder) {
            selectEl.innerHTML = '<option value="">' + placeholder + '</option>';
            if (!ucIdValue) return;
            fetch(url)
                .then(r => r.json())
                .then(list => {
                    list.forEach(item => {
                        const o = document.createElement('option');
                        o.value = item.id;
                        o.textContent = item.code ? (item.code + (item.area_name ? ' - ' + item.area_name : '')) : item.name;
                        selectEl.appendChild(o);
                    });
                })
                .catch(() => {});
        }

        ucSelectImport?.addEventListener('change', function () {
            ucIdValue = this.value;
            loadImportOption('{{ url('/ucs') }}/' + ucIdValue + '/block-codes', blockSelect, '— Auto-detect per row from file —');
            loadImportOption('{{ url('/ucs') }}/' + ucIdValue + '/polling-stations', stationSelect, '— Auto-detect per row from file —');
        });
    </script>
@endsection
