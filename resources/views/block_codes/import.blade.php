@extends('layouts.app')

@section('title', 'Import ECP Delimitation')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Import ECP Delimitation List</h4>
            <small class="text-muted">Upload official ECP table to auto-create UCs, Census Block Codes, and Electoral Area Names</small>
        </div>
        <a href="{{ route('block-codes.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Block Codes
        </a>
    </div>

    <!-- TOP STATUS SUMMARY: UPLOADED VS PENDING TEHSILS -->
    <div class="row g-3 mb-4">
        <!-- Completed Tehsils -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 border-start border-4 border-success h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i> Uploaded Tehsils (Data Ready)</span>
                        <span class="badge bg-success font-mono">{{ $completedTehsils->count() }} Tehsils</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @forelse ($completedTehsils as $ct)
                            <span class="badge bg-success-subtle text-success border border-success-subtle p-1.5" style="font-size: 0.75rem;">
                                {{ $ct->name }}: <strong>{{ $ct->block_codes_count }} blocks</strong>
                            </span>
                        @empty
                            <span class="text-muted small">No Tehsils uploaded yet. Upload a pending Tehsil below.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Tehsils -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border-0 border-start border-4 border-warning h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i> Pending Tehsils (Needs Delimitation Upload)</span>
                        <span class="badge bg-warning text-dark font-mono">{{ $pendingTehsils->count() }} Pending</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        @forelse ($pendingTehsils as $pt)
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle p-1.5" style="font-size: 0.75rem;">
                                <i class="bi bi-hourglass me-1"></i>{{ $pt->name }} (Pending)
                            </span>
                        @empty
                            <span class="badge bg-success text-white"><i class="bi bi-check2-all me-1"></i> All Tehsils Uploaded!</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN UPLOAD FORM -->
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>Upload ECP Delimitation (Excel / CSV)</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('block-codes.import') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- TARGET TEHSIL DROPDOWN WITH PENDING TEHSILS FIRST -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Tehsil <span class="text-danger">*</span></label>
                            <select name="tehsil_id" class="form-select @error('tehsil_id') is-invalid @enderror" required autofocus>
                                <option value="">-- Select Target Tehsil --</option>
                                
                                @if($pendingTehsils->isNotEmpty())
                                    <optgroup label="⚠️ Pending Tehsils (Needs Delimitation Upload)">
                                        @foreach ($pendingTehsils as $tehsil)
                                            <option value="{{ $tehsil->id }}" {{ old('tehsil_id') == $tehsil->id ? 'selected' : '' }} class="fw-bold">
                                                {{ $tehsil->name }} ({{ $tehsil->district->name ?? 'District' }}) &bull; [Upload Needed]
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif

                                @if($completedTehsils->isNotEmpty())
                                    <optgroup label="✅ Already Uploaded Tehsils (Update or Add More)">
                                        @foreach ($completedTehsils as $tehsil)
                                            <option value="{{ $tehsil->id }}" {{ old('tehsil_id') == $tehsil->id ? 'selected' : '' }}>
                                                {{ $tehsil->name }} &bull; ({{ $tehsil->block_codes_count }} blocks already uploaded)
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            <small class="text-muted">UC 1, UC 2... will automatically be mapped/created specifically under this Tehsil.</small>
                            @error('tehsil_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Upload Excel / CSV File <span class="text-danger">*</span></label>
                            <input type="file" name="file" accept=".csv, .xlsx, .xls, .txt" class="form-control @error('file') is-invalid @enderror" required>
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="p-3 rounded-3 bg-light border mb-4">
                            <div class="fw-bold small mb-2"><i class="bi bi-info-circle me-1 text-primary"></i> Expected ECP Delimitation Format:</div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm small mb-0 bg-white">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>Column A</th>
                                            <th>Column B</th>
                                            <th>Column C</th>
                                            <th>Column D</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>No. of Union Council</strong> (e.g. <code>1</code>)</td>
                                            <td><strong>Extent of UC / Electoral Area</strong> (e.g. <code>Band Road Shadi Pura</code>)</td>
                                            <td><strong>Census Block Code</strong> (e.g. <code>260250202</code>)</td>
                                            <td><strong>Population</strong> (e.g. <code>2857</code>)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('block-codes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success px-4"><i class="bi bi-upload me-1"></i> Start Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
