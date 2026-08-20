@extends('layouts.app')

@section('title', 'Polling Stations')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Polling Stations</h4>
            <small class="text-muted">The polling scheme for each UC</small>
        </div>
        <a href="{{ route('polling-stations.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Polling Station
        </a>
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
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>#</th><th>Name</th><th>UC</th><th>Address</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse ($stations as $ps)
                        <tr>
                            <td>{{ $ps->id }}</td>
                            <td>{{ $ps->name }}</td>
                            <td>{{ $ps->uc->name ?? '-' }}</td>
                            <td>{{ $ps->address ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('polling-stations.edit', $ps) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('polling-stations.destroy', $ps) }}" class="d-inline" onsubmit="return confirm('Delete this polling station?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No polling stations found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $stations->links() }}</div>
    </div>
@endsection
