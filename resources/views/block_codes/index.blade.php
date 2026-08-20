@extends('layouts.app')

@section('title', 'Block Codes')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Block Codes</h4>
            <small class="text-muted">Voter data is organized by block code within a UC</small>
        </div>
        <a href="{{ route('block-codes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Block Code
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
                <thead class="table-light"><tr><th>#</th><th>Code</th><th>UC</th><th>Tehsil</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @forelse ($blockCodes as $bc)
                        <tr>
                            <td>{{ $bc->id }}</td>
                            <td>{{ $bc->code }}</td>
                            <td>{{ $bc->uc->name ?? '-' }}</td>
                            <td>{{ $bc->uc->tehsil->name ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('block-codes.edit', $bc) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('block-codes.destroy', $bc) }}" class="d-inline" onsubmit="return confirm('Delete this block code?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No block codes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $blockCodes->links() }}</div>
    </div>
@endsection
