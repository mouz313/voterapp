@extends('layouts.app')

@section('title', 'National Assembly (NA)')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">National Assembly (NA)</h4>
            <small class="text-muted">Constituencies of National Assembly of Pakistan (NA-1, NA-2...)</small>
        </div>
        <a href="{{ route('national-assemblies.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add NA Constituency
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="province" class="form-select" onchange="this.form.submit()">
                        <option value="">All Provinces</option>
                        @foreach ($provinces as $prov)
                            <option value="{{ $prov }}" {{ request('province') == $prov ? 'selected' : '' }}>
                                {{ $prov }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search by NA Code or Name..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                @if(request()->hasAny(['province', 'search']))
                    <div class="col-auto">
                        <a href="{{ route('national-assemblies.index') }}" class="btn btn-outline-danger btn-sm">Clear Filter</a>
                    </div>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Province</th>
                        <th>Provincial Seats (PP/PS/PK/PB)</th>
                        <th>Linked UCs</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assemblies as $na)
                        <tr>
                            <td><span class="badge bg-primary fs-6">{{ $na->code }}</span></td>
                            <td class="fw-semibold">{{ $na->name }}</td>
                            <td><span class="badge bg-secondary">{{ $na->province }}</span></td>
                            <td>
                                <a href="{{ route('provincial-assemblies.index', ['national_assembly_id' => $na->id]) }}" class="badge bg-info text-dark text-decoration-none">
                                    <i class="bi bi-diagram-2 me-1"></i>{{ $na->provincial_assemblies_count }} Seats
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $na->ucs_count }} UCs</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('national-assemblies.edit', $na) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('national-assemblies.destroy', $na) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete {{ $na->code }}?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No National Assembly constituencies found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $assemblies->links() }}</div>
    </div>
@endsection
