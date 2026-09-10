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
        <div class="card-header bg-white voters-filter">
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
                <div class="col-auto" style="width:320px; max-width:100%;">
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
                <div class="col-auto ms-auto d-flex align-items-center gap-2 voters-meta">
                    <label class="mb-0">Show</label>
                    <select name="per_page" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        @foreach ([15, 50, 100, 1000] as $opt)
                            <option value="{{ $opt }}" {{ $perPage == $opt ? 'selected' : '' }}>{{ $opt == 1000 ? 'All' : $opt }}</option>
                        @endforeach
                    </select>
                    <span class="ms-2">Total: <strong class="text-dark">{{ $voters->total() }}</strong></span>
                </div>
            </form>
        </div>

        <div class="table-scroll">
            <table class="table table-hover align-middle mb-0 voters-table">
                <thead class="table-light">
                        <tr>
                            <th>CNIC</th><th>Name</th><th>Father</th><th class="text-center">Age</th><th>Block</th>
                            <th>Station</th><th>Silsala</th><th>Gharana</th><th>Address</th><th class="text-end actions-col">Actions</th>
                        </tr>
                </thead>
                <tbody>
                    @forelse ($voters as $voter)
                        <tr class="{{ $voter->name === 'No Vote' ? 'row-novote' : '' }}">
                            <td class="cnic-cell">{{ $voter->formatted_cnic }}</td>
                            <td class="name-cell">{{ $voter->name }}</td>
                            <td class="muted-cell">{{ $voter->father_name }}</td>
                            <td class="text-center">{{ $voter->age ?? '-' }}</td>
                            <td><span class="badge badge-block">{{ $voter->blockCode->code ?? '-' }}</span></td>
                            <td><span class="badge badge-station">{{ $voter->pollingStation->name ?? '-' }}</span></td>
                            <td class="muted-cell">{{ $voter->silsala_no ?? '-' }}</td>
                            <td class="muted-cell">{{ $voter->gharana_no ?? '-' }}</td>
                            <td class="muted-cell text-truncate" style="max-width:220px">{{ $voter->address ?? '-' }}</td>
                            <td class="text-end actions-col">
                                <a href="{{ route('voters.show', $voter) }}" class="btn btn-sm btn-outline-info btn-action" title="View"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('voters.edit', $voter) }}" class="btn btn-sm btn-outline-secondary btn-action" title="Edit"><i class="bi bi-pencil"></i></a>
                                @if(auth()->user()->canDelete())
                                <form method="POST" action="{{ route('voters.destroy', $voter) }}" class="d-inline" onsubmit="return confirm('Delete this voter?');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger btn-action" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No voters found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center voters-meta">
            <span>Showing {{ $voters->firstItem() ?? 0 }}–{{ $voters->lastItem() ?? 0 }} of {{ $voters->total() }}</span>
            {{ $voters->links() }}
        </div>
    </div>
@endsection
