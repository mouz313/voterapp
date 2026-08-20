@extends('layouts.app')

@section('title', 'Add District')

@section('content')
    <div class="mb-3">
        <a href="{{ route('districts.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-3">Add District</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('districts.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">District Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button>
            </form>
        </div>
    </div>
@endsection
