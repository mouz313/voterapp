@extends('layouts.app')

@section('title', 'Add Union Council')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold">Add Union Council (UC)</h5>
                    <small class="text-muted">According to ECP Delimitation (Tehsil &rarr; UC No &rarr; Area Name)</small>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('ucs.store') }}">
                        @csrf

                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-semibold">Tehsil (Administrative) <span class="text-danger">*</span></label>
                                <select name="tehsil_id" class="form-select @error('tehsil_id') is-invalid @enderror" required>
                                    <option value="">Select Tehsil</option>
                                    @foreach ($tehsils as $tehsil)
                                        <option value="{{ $tehsil->id }}" {{ (old('tehsil_id', $tehsilId) == $tehsil->id) ? 'selected' : '' }}>
                                            {{ $tehsil->district->name ?? '' }} &bull; {{ $tehsil->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tehsil_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-semibold">UC Number in Tehsil <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold">UC</span>
                                    <input type="text" name="uc_no" value="{{ old('uc_no') }}" placeholder="1, 2, 3..." class="form-control @error('uc_no') is-invalid @enderror" required>
                                </div>
                                <small class="text-muted">Starts from 1 for each Tehsil</small>
                                @error('uc_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">National Assembly (NA)</label>
                                <select name="national_assembly_id" class="form-select @error('national_assembly_id') is-invalid @enderror">
                                    <option value="">-- None / Unassigned --</option>
                                    @foreach ($nationalAssemblies as $na)
                                        <option value="{{ $na->id }}" {{ old('national_assembly_id') == $na->id ? 'selected' : '' }}>
                                            {{ $na->code }} - {{ $na->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('national_assembly_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Provincial Assembly (PP/PS/PK/PB)</label>
                                <select name="provincial_assembly_id" class="form-select @error('provincial_assembly_id') is-invalid @enderror">
                                    <option value="">-- None / Unassigned --</option>
                                    @foreach ($provincialAssemblies as $pa)
                                        <option value="{{ $pa->id }}" {{ old('provincial_assembly_id') == $pa->id ? 'selected' : '' }}>
                                            {{ $pa->code }} - {{ $pa->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('provincial_assembly_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">UC Name / Extent of Area (English) <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Shadi Pura / Gulzar Madina Moti Masjid" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">UC Name (Urdu / اردو نام)</label>
                            <input type="text" name="name_ur" value="{{ old('name_ur') }}" placeholder="مثلاً: شادی پورہ / گلزار مدینہ موتی مسجد" dir="rtl" class="form-control @error('name_ur') is-invalid @enderror">
                            @error('name_ur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('ucs.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Save Union Council</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
