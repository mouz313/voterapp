@extends('layouts.app')

@section('title', 'Provincial Assembly (PP/PS/PK/PB)')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Provincial Assembly Seats</h4>
            <small class="text-muted">Constituencies for PP (Punjab), PS (Sindh), PK (KPK), PB (Balochistan)</small>
        </div>
        <a href="{{ route('provincial-assemblies.create', ['national_assembly_id' => request('national_assembly_id')]) }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Provincial Seat
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
                <div class="col-md-3">
                    <select name="national_assembly_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All NA Seats</option>
                        @foreach ($nationalAssemblies as $na)
                            <option value="{{ $na->id }}" {{ request('national_assembly_id') == $na->id ? 'selected' : '' }}>
                                {{ $na->code }} - {{ $na->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search Code (PP-150...) or Name..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                @if(request()->hasAny(['province', 'national_assembly_id', 'search']))
                    <div class="col-auto">
                        <a href="{{ route('provincial-assemblies.index') }}" class="btn btn-outline-danger btn-sm">Clear Filter</a>
                    </div>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Seat Code</th>
                        <th>Name</th>
                        <th>Province</th>
                        <th>Parent NA Seat</th>
                        <th>Linked UCs</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assemblies as $pa)
                        <tr>
                            <td><span class="badge bg-success fs-6">{{ $pa->code }}</span></td>
                            <td class="fw-semibold">{{ $pa->name }}</td>
                            <td><span class="badge bg-secondary">{{ $pa->province }}</span></td>
                            <td>
                                @if($pa->nationalAssembly)
                                    <span class="badge bg-primary">{{ $pa->nationalAssembly->code }}</span>
                                    <small class="text-muted d-block">{{ $pa->nationalAssembly->name }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('ucs.index', ['provincial_assembly_id' => $pa->id]) }}" class="badge bg-info text-dark text-decoration-none">
                                    <i class="bi bi-grid-1x2 me-1"></i>{{ $pa->ucs_count }} UCs
                                </a>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('provincial-assemblies.edit', $pa) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('provincial-assemblies.destroy', $pa) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete {{ $pa->code }}?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No Provincial Assembly constituencies found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $assemblies->links() }}</div>
    </div>
@endsection
