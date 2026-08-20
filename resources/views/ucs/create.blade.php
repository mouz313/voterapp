@extends('layouts.app')

@section('title', 'Add UC')

@section('content')
    <div class="mb-3">
        <a href="{{ route('ucs.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-3">Add Union Council</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('ucs.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Tehsil</label>
                    <select name="tehsil_id" class="form-select @error('tehsil_id') is-invalid @enderror" required>
                        <option value="">Select tehsil</option>
                        @foreach ($tehsils as $tehsil)
                            <option value="{{ $tehsil->id }}" {{ (old('tehsil_id', $tehsilId) == $tehsil->id) ? 'selected' : '' }}>
                                {{ $tehsil->district->name ?? '' }} / {{ $tehsil->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('tehsil_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">UC Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
            </form>
        </div>
    </div>
@endsection
