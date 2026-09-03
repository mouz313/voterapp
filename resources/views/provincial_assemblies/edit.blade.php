@extends('layouts.app')

@section('title', 'Edit Provincial Assembly Seat')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Edit Provincial Assembly Seat: {{ $provincialAssembly->code }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('provincial-assemblies.update', $provincialAssembly) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="national_assembly_id" class="form-label fw-semibold">Parent National Assembly (NA) Seat</label>
                            <select name="national_assembly_id" id="national_assembly_id" class="form-select @error('national_assembly_id') is-invalid @enderror">
                                <option value="">-- Select NA Seat (Optional) --</option>
                                @foreach ($nationalAssemblies as $na)
                                    <option value="{{ $na->id }}" {{ old('national_assembly_id', $provincialAssembly->national_assembly_id) == $na->id ? 'selected' : '' }}>
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
                                    <option value="{{ $prov }}" {{ old('province', $provincialAssembly->province) == $prov ? 'selected' : '' }}>
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
                            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $provincialAssembly->code) }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Constituency Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $provincialAssembly->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-semibold">Description / Notes</label>
                            <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $provincialAssembly->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('provincial-assemblies.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Update Provincial Seat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
