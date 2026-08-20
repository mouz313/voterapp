@extends('layouts.app')

@section('title', 'Districts')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Districts</h4>
            <small class="text-muted">Top-level administrative divisions</small>
        </div>
        <a href="{{ route('districts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add District
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Tehsils</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($districts as $district)
                        <tr>
                            <td>{{ $district->id }}</td>
                            <td>{{ $district->name }}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ $district->tehsils_count }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('districts.edit', $district) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form method="POST" action="{{ route('districts.destroy', $district) }}" class="d-inline" onsubmit="return confirm('Delete this district?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No districts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $districts->links() }}
        </div>
    </div>
@endsection
