@extends('layouts.app')

@section('title', 'Map & Verify')

@section('content')
    <div class="mb-3">
        <a href="{{ route('import.pdf.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-1">Map Columns &amp; Verify</h4>
    <small class="text-muted d-block mb-3">
        {{ $entity === 'voters' ? 'Voters' : 'Polling Stations' }} import — UC #{{ $ucId }} ·
        {{ count($rows) }} rows parsed
    </small>

    @if ($scanned)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Little text was found via direct extraction. Enable OCR (Tesseract + Urdu) for scanned PDFs,
            or re-check the source file. You can still try mapping below.
        </div>
    @endif

    <form method="POST" action="{{ route('import.pdf.confirm') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white"><span class="fw-semibold">Assign each field to a column</span></div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($fields as $field)
                        <div class="col-md-4">
                            <label class="form-label text-capitalize">{{ str_replace('_', ' ', $field) }}</label>
                            <select name="map[{{ $field }}]" class="form-select">
                                <option value="">— ignore —</option>
                                @for ($i = 0; $i < $colCount; $i++)
                                    <option value="{{ $i }}" {{ ($autoMap[$field] ?? null) == $i ? 'selected' : '' }}>
                                        Col {{ $i + 1 }}: {{ \Illuminate\Support\Str::limit($header[$i] ?? '', 24) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white"><span class="fw-semibold">Preview (first 12 rows)</span></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @for ($i = 0; $i < $colCount; $i++)
                                <th>C{{ $i + 1 }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (array_slice($rows, 0, 12) as $row)
                            <tr>
                                @for ($i = 0; $i < $colCount; $i++)
                                    <td>{{ $row[$i] ?? '' }}</td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <button class="btn btn-success mt-3"><i class="bi bi-check-circle me-1"></i> Confirm Import</button>
    </form>
@Endsection
