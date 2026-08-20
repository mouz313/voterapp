@extends('layouts.app')

@section('title', 'UCs')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Union Councils (UCs)</h4>
            <small class="text-muted">Sub-divisions of tehsils</small>
        </div>
        <a href="{{ route('ucs.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add UC
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="tehsil_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Tehsils</option>
                        @foreach ($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}" {{ $tehsil->id == request('tehsil_id') ? 'selected' : '' }}>
                                {{ $tehsil->district->name ?? '' }} / {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Tehsil</th>
                        <th>District</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ucs as $uc)
                        <tr>
                            <td>{{ $uc->id }}</td>
                            <td>
                                <a href="{{ route('ucs.show', $uc) }}" class="fw-semibold text-decoration-none">{{ $uc->name }}</a>
                            </td>
                            <td>{{ $uc->tehsil->name ?? '-' }}</td>
                            <td>{{ $uc->tehsil->district->name ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('ucs.edit', $uc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('ucs.destroy', $uc) }}" class="d-inline" onsubmit="return confirm('Delete this UC?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No UCs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $ucs->links() }}</div>
    </div>
@endsection
