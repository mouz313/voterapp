@extends('layouts.app')

@section('title', 'Data Entry Operators')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0"><i class="bi bi-person-workspace text-primary me-2"></i>Data Entry Staff / Operators</h4>
            <small class="text-muted">Manage data entry operators who can input voters, block codes, and polling stations without deletion access</small>
        </div>
        <a href="{{ route('operators.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Add New Operator
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Operator Name</th>
                            <th>Login Email</th>
                            <th>Contact Phone</th>
                            <th>Assigned Role</th>
                            <th>Status</th>
                            <th>Registered On</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($operators as $op)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center rounded-circle" style="width: 38px; height: 38px;">
                                            {{ strtoupper(substr($op->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $op->name }}</div>
                                            <small class="text-muted">Operator #{{ $op->id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <code class="text-primary">{{ $op->email }}</code>
                                </td>
                                <td>
                                    {{ $op->phone ?: '—' }}
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info border">
                                        <i class="bi bi-shield-lock me-1"></i>Data Entry Only
                                    </span>
                                </td>
                                <td>
                                    @if ($op->status === 'active')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-check-circle me-1"></i>Active
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-x-circle me-1"></i>Suspended
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $op->created_at->format('M d, Y') }}
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('operators.edit', $op) }}" class="btn btn-outline-secondary" title="Edit Operator">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form method="POST" action="{{ route('operators.destroy', $op) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to remove operator [{{ $op->name }}]?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Delete Operator">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-person-workspace display-4 d-block mb-2 text-muted"></i>
                                    <h5>No Data Entry Operators Added Yet</h5>
                                    <p class="small text-muted mb-3">Add data entry operators to enter voters and block codes securely without delete access.</p>
                                    <a href="{{ route('operators.create') }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-person-plus-fill me-1"></i> Add First Operator
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($operators->hasPages())
            <div class="card-footer bg-white border-top-0 d-flex justify-content-end">
                {{ $operators->links() }}
            </div>
        @endif
    </div>
@endsection
