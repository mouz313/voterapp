@extends('layouts.app')

@section('title', 'Edit Polling Station')

@section('content')
    <div class="mb-3">
        <a href="{{ route('polling-stations.index', ['uc_id' => $pollingStation->uc_id]) }}" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Back to Polling Stations
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Polling Station: {{ $pollingStation->name }}</h5>
                        <small class="text-muted">UC: {{ $pollingStation->uc->name ?? '—' }} &bull; Station ID: #{{ $pollingStation->id }}</small>
                    </div>
                    <span class="badge bg-{{ $pollingStation->gender_badge_color }}-subtle text-{{ $pollingStation->gender_badge_color }} border border-{{ $pollingStation->gender_badge_color }}-subtle px-3 py-1 font-mono">
                        <i class="bi {{ $pollingStation->gender_icon }} me-1"></i>{{ $pollingStation->gender_label_ur }}
                    </span>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('polling-stations.update', $pollingStation) }}">
                        @csrf
                        @method('PUT')

                        <!-- UC SELECTION -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Target Union Council (UC) <span class="text-danger">*</span></label>
                            <select name="uc_id" id="ucSelect" class="form-select @error('uc_id') is-invalid @enderror" required>
                                @foreach ($ucs as $uc)
                                    <option value="{{ $uc->id }}" {{ (old('uc_id', $pollingStation->uc_id) == $uc->id) ? 'selected' : '' }}>
                                        {{ $uc->tehsil->district->name ?? '' }} &bull; {{ $uc->tehsil->name ?? '' }} &bull; UC {{ $uc->uc_no ?: $uc->id }}: {{ $uc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('uc_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <!-- STATION NO & NAME -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Station No <small class="text-muted">(Optional)</small></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">#</span>
                                    <input type="text" name="station_no" value="{{ old('station_no', $pollingStation->station_no) }}" class="form-control font-mono @error('station_no') is-invalid @enderror" placeholder="e.g. 1">
                                </div>
                                @error('station_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-9">
                                <label class="form-label fw-semibold">Station Name &amp; Building <span class="text-danger">*</span></label>
                                <input type="text" name="name" value="{{ old('name', $pollingStation->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- GENDER CATEGORY -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block">Polling Station Gender / Category <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="card h-100 border p-3 cursor-pointer text-center gender-card" for="genderMale">
                                        <input class="form-check-input mb-2 mx-auto" type="radio" name="gender" id="genderMale" value="male" {{ old('gender', $pollingStation->gender) === 'male' ? 'checked' : '' }} required>
                                        <div class="fw-bold text-primary"><i class="bi bi-gender-male me-1"></i> Male (مردانہ)</div>
                                        <small class="text-muted" style="font-size: 0.78rem;">Exclusively for male voters of the block code(s)</small>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="card h-100 border p-3 cursor-pointer text-center gender-card" for="genderFemale">
                                        <input class="form-check-input mb-2 mx-auto" type="radio" name="gender" id="genderFemale" value="female" {{ old('gender', $pollingStation->gender) === 'female' ? 'checked' : '' }}>
                                        <div class="fw-bold text-danger"><i class="bi bi-gender-female me-1"></i> Female (زنانہ)</div>
                                        <small class="text-muted" style="font-size: 0.78rem;">Exclusively for female voters of the block code(s)</small>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="card h-100 border p-3 cursor-pointer text-center gender-card" for="genderCombined">
                                        <input class="form-check-input mb-2 mx-auto" type="radio" name="gender" id="genderCombined" value="combined" {{ old('gender', $pollingStation->gender) === 'combined' ? 'checked' : '' }}>
                                        <div class="fw-bold text-success"><i class="bi bi-gender-ambiguous me-1"></i> Combined (مشترکہ)</div>
                                        <small class="text-muted" style="font-size: 0.78rem;">Serves both male and female voters with separate booths</small>
                                    </label>
                                </div>
                            </div>
                            @error('gender') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <!-- ASSIGNED BLOCK CODES (MULTI-SELECT) -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold mb-0">
                                    <i class="bi bi-grid-3x3-gap me-1 text-primary"></i> Assign Census Block Codes (شماریاتی بلاک کوڈز)
                                </label>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="selectAllBlocks">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="deselectAllBlocks">Deselect All</button>
                                </div>
                            </div>
                            <div class="card bg-light border p-3" style="max-height: 220px; overflow-y: auto;" id="blockCodesContainer">
                                <div class="row g-2" id="blockCodesList">
                                    @php
                                        $checkedIds = old('block_code_ids', $assignedBlockCodeIds);
                                    @endphp
                                    @forelse ($blockCodes as $bc)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check bg-white p-2 rounded border">
                                                <input class="form-check-input ms-0 me-2 block-code-checkbox" type="checkbox" name="block_code_ids[]" value="{{ $bc->id }}" id="bc_{{ $bc->id }}" {{ in_array($bc->id, $checkedIds) ? 'checked' : '' }}>
                                                <label class="form-check-label font-mono fw-bold" for="bc_{{ $bc->id }}" style="font-size: 0.85rem;">
                                                    {{ $bc->code }}
                                                    @if ($bc->area_name)
                                                        <small class="text-muted fw-normal d-block font-sans text-truncate" style="max-width: 170px;">{{ $bc->area_name }}</small>
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="col-12 text-center text-muted py-3" id="noBlocksMsg">
                                            No block codes found for this UC.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                            <small class="text-muted d-block mt-1">
                                Checking block codes here will automatically set this station as their designated Male, Female, or Combined polling venue.
                            </small>
                            @error('block_code_ids') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <!-- BOOTHS CONFIGURATION -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Male Booths <small class="text-muted">(مردانہ بوتھ)</small></label>
                                <input type="number" name="male_booths" value="{{ old('male_booths', $pollingStation->male_booths) }}" min="0" class="form-control font-mono" placeholder="e.g. 2">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Female Booths <small class="text-muted">(زنانہ بوتھ)</small></label>
                                <input type="number" name="female_booths" value="{{ old('female_booths', $pollingStation->female_booths) }}" min="0" class="form-control font-mono" placeholder="e.g. 2">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Total Booths <small class="text-muted">(کل بوتھ)</small></label>
                                <input type="number" name="total_booths" value="{{ old('total_booths', $pollingStation->total_booths) }}" min="0" class="form-control font-mono" placeholder="Auto-summed">
                            </div>
                        </div>

                        <!-- ADDRESS -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Address &amp; Location Details</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2" placeholder="e.g. Main Street near Canal Road, Chak 123">{{ old('address', $pollingStation->address) }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('polling-stations.index', ['uc_id' => $pollingStation->uc_id]) }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-save me-1"></i> Update Polling Station
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const ucSelect = document.getElementById('ucSelect');
                const listContainer = document.getElementById('blockCodesList');
                const selectAllBtn = document.getElementById('selectAllBlocks');
                const deselectAllBtn = document.getElementById('deselectAllBlocks');

                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', () => {
                        document.querySelectorAll('.block-code-checkbox').forEach(cb => cb.checked = true);
                    });
                }
                if (deselectAllBtn) {
                    deselectAllBtn.addEventListener('click', () => {
                        document.querySelectorAll('.block-code-checkbox').forEach(cb => cb.checked = false);
                    });
                }

                if (ucSelect && listContainer) {
                    ucSelect.addEventListener('change', function () {
                        const ucId = this.value;
                        if (!ucId) {
                            listContainer.innerHTML = '<div class="col-12 text-center text-muted py-3">Please select a UC first.</div>';
                            return;
                        }

                        listContainer.innerHTML = '<div class="col-12 text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-2"></span>Loading Block Codes...</div>';

                        fetch(`/ucs/${ucId}/block-codes`)
                            .then(r => r.json())
                            .then(data => {
                                if (!data || data.length === 0) {
                                    listContainer.innerHTML = '<div class="col-12 text-center text-muted py-3">No block codes found for this UC.</div>';
                                    return;
                                }

                                let html = '';
                                data.forEach(bc => {
                                    html += `
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check bg-white p-2 rounded border">
                                                <input class="form-check-input ms-0 me-2 block-code-checkbox" type="checkbox" name="block_code_ids[]" value="${bc.id}" id="bc_${bc.id}">
                                                <label class="form-check-label font-mono fw-bold" for="bc_${bc.id}" style="font-size: 0.85rem;">
                                                    ${bc.code}
                                                    ${bc.area_name ? `<small class="text-muted fw-normal d-block font-sans text-truncate" style="max-width: 170px;">${bc.area_name}</small>` : ''}
                                                </label>
                                            </div>
                                        </div>
                                    `;
                                });
                                listContainer.innerHTML = html;
                            })
                            .catch(() => {
                                listContainer.innerHTML = '<div class="col-12 text-center text-danger py-3">Failed to load block codes.</div>';
                            });
                    });
                }
            })();
        </script>
    @endpush
@endsection

