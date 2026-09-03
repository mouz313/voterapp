@extends('layouts.app')

@section('title', 'Add Provincial Assembly Seat')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Add Provincial Assembly Seat (PP / PS / PK / PB)</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('provincial-assemblies.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="national_assembly_id" class="form-label fw-semibold">Parent National Assembly (NA) Seat</label>
                            <select name="national_assembly_id" id="national_assembly_id" class="form-select @error('national_assembly_id') is-invalid @enderror">
                                <option value="">-- Select NA Seat (Optional) --</option>
                                @foreach ($nationalAssemblies as $na)
                                    <option value="{{ $na->id }}" {{ old('national_assembly_id', $selectedNaId) == $na->id ? 'selected' : '' }}>
                                        {{ $na->code }} - {{ $na->name }} ({{ $na->province }})
                                    </option>
                                @endforeach
                            </select>
                            @error('national_assembly_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="province" class="form-label fw-semibold">Province <span class="text-danger">*</span></label>
                            <select name="province" id="province" class="form-select @error('province') is-invalid @enderror" required>
                                @foreach ($provinces as $prov)
                                    <option value="{{ $prov }}" {{ old('province', 'Punjab') == $prov ? 'selected' : '' }}>
                                        {{ $prov }}
                                    </option>
                                @endforeach
                            </select>
                            @error('province')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="code" class="form-label fw-semibold">Constituency Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}" placeholder="e.g. PP-150, PS-10, PK-45, PB-12" required>
                            <small class="text-muted">Constituency identifier (PP = Punjab, PS = Sindh, PK = KPK, PB = Balochistan)</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Constituency Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Lahore-VII" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description / Notes</label>
                            <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Optional notes or areas covered">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('provincial-assemblies.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Save Provincial Seat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
