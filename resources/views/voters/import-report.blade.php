@extends('layouts.app')

@section('title', 'Import Report')

@section('content')
    <div class="mb-3">
        <a href="{{ route('voters.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back to Voters</a>
    </div>
    <h4 class="page-title mb-3">Import Report</h4>

    <div class="alert alert-success">
        Imported <strong>{{ $imported }}</strong> voters into <strong>{{ $uc->name }}</strong>.
        Marked <strong>{{ $noVote }}</strong> as <strong>No Vote</strong>.
        Skipped <strong>{{ count($skippedRows) }}</strong> rows.
    </div>

    @if (count($skippedRows))
        <div class="card shadow-sm">
            <div class="card-body">
                <h6>Skipped Rows</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Row #</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($skippedRows as $s)
                                <tr>
                                    <td>{{ $s['row'] }}</td>
                                    <td dir="rtl">{{ $s['reason'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
