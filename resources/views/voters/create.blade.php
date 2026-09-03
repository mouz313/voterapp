@extends('layouts.app')

@section('title', 'Add Voter')

@section('content')
    <div class="mb-3">
        <a href="{{ route('voters.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>
    <h4 class="page-title mb-3">Add Voter</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('voters.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">UC</label>
                        <select name="uc_id" id="ucSelect" class="form-select @error('uc_id') is-invalid @enderror" required>
                            <option value="">Select UC</option>
                            @foreach ($ucs as $uc)
                                <option value="{{ $uc->id }}" {{ (old('uc_id', $ucId) == $uc->id) ? 'selected' : '' }}>
                                    {{ $uc->tehsil->district->name ?? '' }} / {{ $uc->tehsil->name ?? '' }} / {{ $uc->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Block Code</label>
                        <select name="block_code_id" id="blockSelect" class="form-select @error('block_code_id') is-invalid @enderror" required>
                            <option value="">Select block</option>
                            @foreach ($blockCodes as $bc)
                                <option value="{{ $bc->id }}" {{ (old('block_code_id') == $bc->id) ? 'selected' : '' }}>{{ $bc->code }}</option>
                            @endforeach
                        </select>
                        @error('block_code_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Polling Station</label>
                        <select name="polling_station_id" id="stationSelect" class="form-select @error('polling_station_id') is-invalid @enderror" required>
                            <option value="">Select station</option>
                            @foreach ($stations as $ps)
                                <option value="{{ $ps->id }}" {{ (old('polling_station_id') == $ps->id) ? 'selected' : '' }}>{{ $ps->name }}</option>
                            @endforeach
                        </select>
                        @error('polling_station_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CNIC (13 digits)</label>
                        <input type="text" name="cnic" value="{{ old('cnic') }}" class="form-control @error('cnic') is-invalid @enderror" placeholder="12345-1234567-8" required>
                        @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Father Name</label>
                        <input type="text" name="father_name" value="{{ old('father_name') }}" class="form-control @error('father_name') is-invalid @enderror" required>
                        @error('father_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Age (optional)</label>
                        <input type="number" name="age" min="1" max="120" value="{{ old('age') }}" class="form-control @error('age') is-invalid @enderror">
                        @error('age') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Silsala No (optional)</label>
                        <input type="text" name="silsala_no" value="{{ old('silsala_no') }}" class="form-control @error('silsala_no') is-invalid @enderror">
                        @error('silsala_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gharana No (optional)</label>
                        <input type="text" name="gharana_no" value="{{ old('gharana_no') }}" class="form-control @error('gharana_no') is-invalid @enderror">
                        @error('gharana_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address (optional)</label>
                        <input type="text" name="address" value="{{ old('address') }}" class="form-control @error('address') is-invalid @enderror">
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <button class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i> Save</button>
            </form>
        </div>
    </div>

    <script>
        // Reload block/station dropdowns when UC changes (basic UX).
        document.getElementById('ucSelect')?.addEventListener('change', function () {
            const ucId = this.value;
            if (!ucId) return;
            window.location.href = '{{ route('voters.create') }}?uc_id=' + ucId;
        });
    </script>
@endsection
