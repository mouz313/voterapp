@extends('layouts.app')

@section('title', 'Import Result')

@section('content')
    <div class="mb-3">
        <a href="{{ $redirect }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back to list</a>
    </div>
    <h4 class="page-title mb-3">Import Result</h4>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <div class="text-muted small">Imported</div>
                    <div class="fw-bold fs-4">{{ $imported }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <div class="text-muted small">Skipped</div>
                    <div class="fw-bold fs-4">{{ count($skipped) }}</div>
                </div>
            </div>
        </div>
    </div>

    @if (!empty($skipped))
        <div class="card shadow-sm">
            <div class="card-header bg-white"><span class="fw-semibold">Skipped rows</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Row</th><th>Reason</th></tr></thead>
                    <tbody>
                        @foreach (array_slice($skipped, 0, 50) as $s)
                            <tr><td>{{ $s['row'] }}</td><td>{{ $s['reason'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@Endsection
