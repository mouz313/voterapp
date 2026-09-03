@extends('layouts.app')

@section('title', 'Edit Block Code')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Edit Census Block Code: {{ $blockCode->code }}</h4>
            <small class="text-muted">Update area name, Urdu extent, population, or assigned UC</small>
        </div>
        <a href="{{ route('block-codes.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Block Codes
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Block Code Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('block-codes.update', $blockCode) }}">
                        @csrf
                        @method('PUT')

                        <!-- STEP 1 TEHSIL -> STEP 2 UC -->
                        <div class="p-3 rounded bg-light border mb-4">
                            <h6 class="fw-bold text-primary mb-2"><i class="bi bi-geo-alt-fill me-1"></i> Target Union Council Location</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Step 1: Select Tehsil <span class="text-danger">*</span></label>
                                    <select id="tehsilSelect" class="form-select border-primary" required>
                                        <option value="">-- Choose Tehsil --</option>
                                        @foreach ($tehsils as $t)
                                            <option value="{{ $t->id }}">
                                                {{ $t->district->name ?? 'District' }} &bull; {{ $t->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Step 2: Target Union Council (UC) <span class="text-danger">*</span></label>
                                    <select name="uc_id" id="ucSelect" class="form-select @error('uc_id') is-invalid @enderror" required>
                                        <option value="">-- Please select Tehsil first --</option>
                                    </select>
                                    <small class="text-muted" id="ucCountText"></small>
                                    @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Census Block Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" value="{{ old('code', $blockCode->code) }}" class="form-control font-mono @error('code') is-invalid @enderror" required>
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Census Population</label>
                                <input type="number" name="population" value="{{ old('population', $blockCode->population) }}" class="form-control @error('population') is-invalid @enderror">
                                @error('population') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Extent of the Union Council (Electoral Area / Revenue Estate in English)</label>
                            <input type="text" name="area_name" value="{{ old('area_name', $blockCode->area_name) }}" class="form-control @error('area_name') is-invalid @enderror">
                            @error('area_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Electoral Area in Urdu (نام انتخابی علاقہ / محلہ / ریونیو اسٹیٹ)</label>
                            <input type="text" name="area_name_ur" value="{{ old('area_name_ur', $blockCode->area_name_ur) }}" dir="rtl" class="form-control @error('area_name_ur') is-invalid @enderror">
                            @error('area_name_ur') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('block-codes.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Update Block Code</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Client-side Dynamic Tehsil -> UC Dataset -->
    <script>
        const ucsData = [
            @foreach ($ucs as $uc)
                {
                    id: {{ $uc->id }},
                    tehsil_id: {{ $uc->tehsil_id }},
                    uc_no: "{{ $uc->uc_no ?: $uc->id }}",
                    name: "{{ addslashes($uc->name) }}",
                    name_ur: "{{ addslashes($uc->name_ur ?? '') }}"
                },
            @endforeach
        ];

        const tehsilSelect = document.getElementById('tehsilSelect');
        const ucSelect = document.getElementById('ucSelect');
        const ucCountText = document.getElementById('ucCountText');
        const currentUcId = "{{ old('uc_id', $blockCode->uc_id) }}";

        function updateUcsForTehsil(tehsilId, selectedUcId = '') {
            if (!tehsilId) {
                ucSelect.innerHTML = '<option value="">-- Please select Tehsil first --</option>';
                ucSelect.disabled = true;
                ucCountText.textContent = '';
                return;
            }

            const filteredUcs = ucsData.filter(u => u.tehsil_id == tehsilId);
            
            if (filteredUcs.length === 0) {
                ucSelect.innerHTML = '<option value="">-- No UCs registered in this Tehsil --</option>';
                ucSelect.disabled = true;
                ucCountText.textContent = '0 UCs found in this Tehsil.';
                return;
            }

            let html = '<option value="">-- Select Union Council (' + filteredUcs.length + ' UCs) --</option>';
            filteredUcs.forEach(uc => {
                const isSel = (selectedUcId && selectedUcId == uc.id) ? 'selected' : '';
                const urduPart = uc.name_ur ? ' (' + uc.name_ur + ')' : '';
                html += `<option value="${uc.id}" ${isSel}>UC ${uc.uc_no}: ${uc.name}${urduPart}</option>`;
            });

            ucSelect.innerHTML = html;
            ucSelect.disabled = false;
            ucCountText.textContent = filteredUcs.length + ' Union Councils available for this Tehsil.';
        }

        tehsilSelect.addEventListener('change', function () {
            updateUcsForTehsil(this.value);
        });

        if (currentUcId) {
            const matched = ucsData.find(u => u.id == currentUcId);
            if (matched) {
                tehsilSelect.value = matched.tehsil_id;
                updateUcsForTehsil(matched.tehsil_id, currentUcId);
            }
        }
    </script>
@endsection
