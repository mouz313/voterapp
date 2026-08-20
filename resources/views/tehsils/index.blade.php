@extends('layouts.app')

@section('title', 'Tehsils')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Tehsils</h4>
            <small class="text-muted">Sub-divisions of districts</small>
        </div>
        <a href="{{ route('tehsils.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Tehsil
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="district_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Districts</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" {{ $district->id == request('district_id') ? 'selected' : '' }}>
                                {{ $district->name }}
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
                        <th>District</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tehsils as $tehsil)
                        <tr>
                            <td>{{ $tehsil->id }}</td>
                            <td>{{ $tehsil->name }}</td>
                            <td>{{ $tehsil->district->name ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('tehsils.edit', $tehsil) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('tehsils.destroy', $tehsil) }}" class="d-inline" onsubmit="return confirm('Delete this tehsil?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No tehsils found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $tehsils->links() }}</div>
    </div>
@endsection
