@extends('layouts.app')

@section('title', 'Import Polling Stations')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Import Polling Stations (CSV / Excel)</h4>
            <small class="text-muted">Upload list of polling stations, booth locations, and capacities for a Union Council</small>
        </div>
        <a href="{{ route('polling-stations.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- TOP STATUS SUMMARY: UPLOADED VS PENDING UCS -->
    <div class="row g-3 mb-4">
        <!-- Completed UCs -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i> UCs with Polling Stations</span>
                        <span class="badge bg-success font-mono">{{ $completedUcs->count() }} UCs</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1" style="max-height: 100px; overflow-y: auto;">
                        @forelse ($completedUcs as $cu)
                            <span class="badge bg-success-subtle text-success border border-success-subtle p-1.5" style="font-size: 0.73rem;">
                                {{ $cu->tehsil->name ?? '' }} - UC {{ $cu->uc_no ?: $cu->id }}: <strong>{{ $cu->polling_stations_count }} stations</strong>
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
                        <span class="fw-bold text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i> Pending UCs (0 Stations Uploaded)</span>
                        <span class="badge bg-warning text-dark font-mono">{{ $pendingUcs->count() }} Pending</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1" style="max-height: 100px; overflow-y: auto;">
                        @forelse ($pendingUcs as $pu)
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle p-1.5" style="font-size: 0.73rem;">
                                <i class="bi bi-hourglass me-1"></i>{{ $pu->tehsil->name ?? '' }} - UC {{ $pu->uc_no ?: $pu->id }} ({{ $pu->name }})
                            </span>
                        @empty
                            <span class="badge bg-success text-white"><i class="bi bi-check2-all me-1"></i> All UCs Have Polling Stations!</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN IMPORT FORM -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-house-door me-2 text-success"></i>Upload Polling Stations Spreadsheet</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('polling-stations.import') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Union Council (UC) <span class="text-danger">*</span></label>
                            <select name="uc_id" class="form-select @error('uc_id') is-invalid @enderror" required autofocus>
                                <option value="">-- Select Union Council --</option>
                                
                                @if($pendingUcs->isNotEmpty())
                                    <optgroup label="⚠️ Pending UCs (Needs Polling Stations Upload)">
                                        @foreach ($pendingUcs as $uc)
                                            <option value="{{ $uc->id }}" {{ old('uc_id') == $uc->id ? 'selected' : '' }} class="fw-bold">
                                                {{ $uc->tehsil->name ?? '' }} &bull; UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }} [Upload Needed]
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if($completedUcs->isNotEmpty())
                                    <optgroup label="✅ Already Uploaded UCs (Update or Add More)">
                                        @foreach ($completedUcs as $uc)
                                            <option value="{{ $uc->id }}" {{ old('uc_id') == $uc->id ? 'selected' : '' }}>
                                                {{ $uc->tehsil->name ?? '' }} &bull; UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }} ({{ $uc->polling_stations_count }} stations)
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">CSV / Excel File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt,.xlsx,.xls" required>
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="alert alert-light border small my-3">
                            <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Expected Columns:</div>
                            <code>name, address, total_voters, male_voters, female_voters</code>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('polling-stations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-upload me-1"></i> Start Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
