@extends('layouts.app')

@section('title', 'Edit Polling Station')

@section('content')
    <div class="mb-3">
        <a href="{{ route('polling-stations.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-3">Edit Polling Station</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('polling-stations.update', $pollingStation) }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label">UC</label>
                    <select name="uc_id" class="form-select @error('uc_id') is-invalid @enderror" required>
                        @foreach ($ucs as $uc)
                            <option value="{{ $uc->id }}" {{ (old('uc_id', $pollingStation->uc_id) == $uc->id) ? 'selected' : '' }}>
                                {{ $uc->tehsil->district->name ?? '' }} / {{ $uc->tehsil->name ?? '' }} / {{ $uc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Station Name</label>
                    <input type="text" name="name" value="{{ old('name', $pollingStation->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Address (optional)</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $pollingStation->address) }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Update</button>
            </form>
        </div>
    </div>
@endsection
