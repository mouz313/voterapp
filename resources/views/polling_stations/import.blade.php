@extends('layouts.app')

@section('title', 'Import Polling Stations')

@section('content')
    <div class="mb-3">
        <a href="{{ route('polling-stations.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-3">Import Polling Stations (CSV)</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('polling-stations.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Target UC</label>
                    <select name="uc_id" class="form-select @error('uc_id') is-invalid @enderror" required>
                        <option value="">Select UC</option>
                        @foreach ($ucs as $uc)
                            <option value="{{ $uc->id }}" {{ old('uc_id') == $uc->id ? 'selected' : '' }}>
                                {{ $uc->tehsil->district->name ?? '' }} / {{ $uc->tehsil->name ?? '' }} / {{ $uc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">CSV File</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt" required>
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="alert alert-light border">
                    <strong>Expected CSV header:</strong>
                    <code>name, address</code>
                    <ul class="mb-0 mt-2">
                        <li><code>name</code> is required; <code>address</code> is optional.</li>
                        <li>Rows with a duplicate or empty name are skipped.</li>
                    </ul>
                </div>

                <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import</button>
            </form>
        </div>
    </div>
@Endsection
