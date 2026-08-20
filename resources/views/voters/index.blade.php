@extends('layouts.app')

@section('title', 'Voters')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Voters</h4>
            <small class="text-muted">Manage voter records and polling assignment</small>
        </div>
        <div>
            <a href="{{ route('voters.import.form') }}" class="btn btn-outline-secondary me-1">
                <i class="bi bi-upload me-1"></i> Import
            </a>
            <a href="{{ route('voters.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add Voter
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="uc_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All UCs</option>
                        @foreach ($ucs as $uc)
                            <option value="{{ $uc->id }}" {{ $uc->id == request('uc_id') ? 'selected' : '' }}>
                                {{ $uc->tehsil->district->name ?? '' }} / {{ $uc->tehsil->name ?? '' }} / {{ $uc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto flex-grow-1">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, father, CNIC or Gharana...">
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                    @if (request('search') || request('uc_id'))
                        <a href="{{ route('voters.index') }}" class="btn btn-link">Clear</a>
                    @endif
                </div>
                <div class="col-auto ms-auto text-muted small">
                    Total: {{ $voters->total() }}
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>CNIC</th><th>Name</th><th>Father</th><th>Age</th><th>Block</th>
                        <th>Station</th><th>Silsala</th><th>Gharana</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($voters as $voter)
                        <tr>
                            <td>{{ $voter->formatted_cnic }}</td>
                            <td>{{ $voter->name }}</td>
                            <td>{{ $voter->father_name }}</td>
                            <td>{{ $voter->age ?? '-' }}</td>
                            <td>{{ $voter->blockCode->code ?? '-' }}</td>
                            <td>{{ $voter->pollingStation->name ?? '-' }}</td>
                            <td>{{ $voter->silsala_no ?? '-' }}</td>
                            <td>{{ $voter->gharana_no ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('voters.edit', $voter) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('voters.destroy', $voter) }}" class="d-inline" onsubmit="return confirm('Delete this voter?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No voters found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $voters->links() }}</div>
    </div>
@endsection
