@extends('layouts.app')

@section('title', 'Union Councils (UCs)')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Union Councils (UCs)</h4>
            <small class="text-muted">UCs scoped per Tehsil (ECP Delimitation: Tehsil &rarr; UC No &rarr; Electoral Area)</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('block-codes.import.form') }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import ECP Delimitation
            </a>
            <a href="{{ route('ucs.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Add UC
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="tehsil_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Tehsils</option>
                        @foreach ($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}" {{ $tehsil->id == request('tehsil_id') ? 'selected' : '' }}>
                                {{ $tehsil->district->name ?? '' }} &bull; {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="national_assembly_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All NA Seats</option>
                        @foreach ($nationalAssemblies as $na)
                            <option value="{{ $na->id }}" {{ $na->id == request('national_assembly_id') ? 'selected' : '' }}>
                                {{ $na->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="provincial_assembly_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Provincial Seats</option>
                        @foreach ($provincialAssemblies as $pa)
                            <option value="{{ $pa->id }}" {{ $pa->id == request('provincial_assembly_id') ? 'selected' : '' }}>
                                {{ $pa->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search UC number or name..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                @if(request()->hasAny(['tehsil_id', 'national_assembly_id', 'provincial_assembly_id', 'search']))
                    <div class="col-auto">
                        <a href="{{ route('ucs.index') }}" class="btn btn-outline-danger btn-sm">Clear Filter</a>
                    </div>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 90px;">UC No</th>
                        <th>Union Council / Electoral Area</th>
                        <th>Tehsil & District</th>
                        <th>NA & Provincial (PP)</th>
                        <th>Blocks & Voters</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ucs as $uc)
                        <tr>
                            <td>
                                <span class="badge bg-primary fs-6 px-2 py-1">
                                    UC {{ $uc->uc_no ?: $uc->id }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('ucs.show', $uc) }}" class="fw-bold text-decoration-none fs-6 text-dark d-block">
                                    {{ $uc->name }}
                                </a>
                                @if($uc->name_ur)
                                    <span class="text-muted small font-urdu" style="font-family: 'Noto Nastaliq Urdu', serif;">{{ $uc->name_ur }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $uc->tehsil->name ?? '-' }}</div>
                                <small class="text-muted">{{ $uc->tehsil->district->name ?? '-' }}</small>
                            </td>
                            <td>
                                @if($uc->nationalAssembly)
                                    <span class="badge bg-primary">{{ $uc->nationalAssembly->code }}</span>
                                @endif
                                @if($uc->provincialAssembly)
                                    <span class="badge bg-success">{{ $uc->provincialAssembly->code }}</span>
                                @endif
                                @if(!$uc->nationalAssembly && !$uc->provincialAssembly)
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('block-codes.index', ['uc_id' => $uc->id]) }}" class="badge bg-light text-dark border me-1 text-decoration-none">
                                    <i class="bi bi-collection me-1"></i>{{ $uc->block_codes_count }} Blocks
                                </a>
                                <span class="badge bg-info text-dark">
                                    <i class="bi bi-people me-1"></i>{{ number_format($uc->voters_count) }} Voters
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('ucs.show', $uc) }}" class="btn btn-sm btn-outline-primary" title="View Details"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('ucs.edit', $uc) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('ucs.destroy', $uc) }}" class="d-inline" onsubmit="return confirm('Delete this UC?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No UCs found for the selected filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $ucs->links() }}</div>
    </div>
@endsection
