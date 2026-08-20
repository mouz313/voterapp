@extends('layouts.app')

@section('title', 'Import from PDF')

@section('content')
    <div class="mb-3">
        <a href="{{ route('voters.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-3">Import from PDF</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('import.pdf.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Import into</label>
                    <select name="entity" class="form-select @error('entity') is-invalid @enderror" required>
                        <option value="">Select target...</option>
                        <option value="voters" {{ old('entity') == 'voters' ? 'selected' : '' }}>Voters</option>
                        <option value="polling_stations" {{ old('entity') == 'polling_stations' ? 'selected' : '' }}>Polling Stations</option>
                    </select>
                    @error('entity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

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
                    <div class="form-text">
                        For Voters, block codes &amp; polling stations referenced in the PDF must already exist in this UC.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">PDF File</label>
                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf" required>
                    @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="alert alert-light border">
                    <strong>How it works:</strong> Digital (text) PDFs are extracted automatically with no extra setup.
                    Scanned PDFs need OCR enabled (<code>PDF_OCR_ENABLED=true</code> + Tesseract Urdu installed).
                    After upload you get a column-mapping preview to verify before importing.
                </div>

                <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> Extract &amp; Continue</button>
            </form>
        </div>
    </div>
@Endsection
