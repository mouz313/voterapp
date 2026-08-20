@extends('layouts.app')

@section('title', 'Import Voters')

@section('content')
    <div class="mb-3">
        <a href="{{ route('voters.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-3">Import Voters (CSV)</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('voters.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Target UC</label>
                    <select name="uc_id" class="form-select @error('uc_id') is-invalid @enderror" required>
                        <option value="">Select UC</option>
                        @foreach ($ucs as $uc)
                            <option value="{{ $uc->id }}" {{ (old('uc_id') == $uc->id) ? 'selected' : '' }}>
                                {{ $uc->tehsil->district->name ?? '' }} / {{ $uc->tehsil->name ?? '' }} / {{ $uc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Block codes & polling stations referenced in the CSV must already exist in this UC.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">CSV File</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt" required>
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="alert alert-light border">
                    <strong>Expected CSV header:</strong>
                    <code>cnic, name, father_name, age, silsala_no, gharana_no, block_code, polling_station</code>
                    <ul class="mb-0 mt-2">
                        <li><code>cnic</code> must be 13 digits (dashes optional).</li>
                        <li><code>block_code</code> must match an existing Block Code in the chosen UC.</li>
                        <li><code>polling_station</code> must match an existing Polling Station name in the chosen UC.</li>
                        <li>Rows with duplicate/invalid CNIC or missing block/station are skipped.</li>
                    </ul>
                </div>

                <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import</button>
            </form>
        </div>
    </div>
@endsection
