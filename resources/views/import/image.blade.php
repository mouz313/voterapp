@extends('layouts.app')

@section('title', 'Urdu OCR List Scanner')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Urdu OCR Voter List Scanner</h4>
            <small class="text-muted">Scan physical printed voter lists in Urdu, extract CNIC & family details, and export to Excel</small>
        </div>
        <a href="{{ route('voters.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-camera me-2 text-success"></i>Upload Physical Voter List Photo / Scan</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Upload a clear photo/scan of an official printed voter list. The OCR engine reads Urdu voter names, CNIC, Silsala #, Gharana #, and age, producing a ready-to-use <strong>Excel (.xlsx)</strong> or <strong>CSV</strong> file.
                    </p>

                    <form method="POST" action="{{ route('import.image.store') }}" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Photo / Scan Image <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept="image/jpeg,image/png,image/webp" required>
                            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Export Format</label>
                            <select name="format" class="form-select">
                                <option value="xlsx">Excel Spreadsheet (.xlsx)</option>
                                <option value="csv">CSV File (.csv)</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('voters.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4" id="imageSubmitBtn">
                                <i class="bi bi-cpu me-1"></i> Run OCR &amp; Download Sheet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
