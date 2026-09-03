@extends('layouts.app')

@section('title', 'Import Preview')

@section('content')
    <div class="mb-3">
        <a href="{{ route('voters.import.form') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-3">Map Columns &amp; Preview</h4>

    <div class="alert alert-info">
        <strong>UC:</strong> {{ $uc->name }}
        @if ($blockCodeId)
            &mdash; <strong>Block:</strong> {{ $blockCodeId }}
        @else
            &mdash; <strong>No block selected.</strong> Rows without a 9-digit block column will be skipped.
        @endif
    </div>

    <form method="POST" action="{{ route('voters.import') }}">
        @csrf
        <input type="hidden" name="uc_id" value="{{ $uc->id }}">
        <input type="hidden" name="block_code_id" value="{{ $blockCodeId }}">
        <input type="hidden" name="polling_station_id" value="{{ $pollingStationId }}">
        <input type="hidden" name="stored" value="{{ $stored }}">

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Column Mapping</h6>
                <p class="text-muted">Auto-detected from headers. Adjust if a column is wrong, then confirm.</p>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>File Column</th>
                                <th>Sample</th>
                                <th>Map to</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($header as $idx => $col)
                                @php
                                    $selectedField = array_search($idx, $autoMap, true) ?: 'ignore';
                                @endphp
                                <tr>
                                    <td dir="rtl">{{ $col }}</td>
                                    <td dir="rtl">{{ $sample[0][$idx] ?? '' }}</td>
                                    <td>
                                        <select name="map[{{ $idx }}]" class="form-select">
                                            @foreach ($fieldOptions as $f => $label)
                                                <option value="{{ $f }}" {{ $selectedField == $f ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Sample Rows (first {{ count($sample) }})</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                @foreach ($header as $col)
                                    <th dir="rtl">{{ $col }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sample as $row)
                                <tr>
                                    @foreach ($row as $cell)
                                        <td dir="rtl">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Confirm Import</button>
    </form>
@endsection
