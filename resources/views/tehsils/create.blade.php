@extends('layouts.app')

@section('title', 'Add Tehsil')

@section('content')
    <div class="mb-3">
        <a href="{{ route('tehsils.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-3">Add Tehsil</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('tehsils.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">District</label>
                    <select name="district_id" class="form-select @error('district_id') is-invalid @enderror" required>
                        <option value="">Select district</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->id }}" {{ (old('district_id', $districtId) == $district->id) ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('district_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Tehsil Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
            </form>
        </div>
    </div>
@endsection
