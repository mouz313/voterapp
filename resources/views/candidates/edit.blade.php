@extends('layouts.app')

@section('title', 'Edit Candidate')

@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="page-title mb-0">Edit Candidate: {{ $candidate->name }}</h4>
            <small class="text-muted">Update candidate credentials, assigned UC, Party branding (PTI, PMLN, Independent, etc.), candidate photo & election symbol</small>
        </div>
        <a href="{{ route('candidates.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Candidates
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-gear me-2 text-primary"></i>Candidate Configuration & Branding</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('candidates.update', $candidate) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-semibold">Candidate / Team Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $candidate->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Login Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $candidate->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">New Password (Leave blank to keep current)</label>
                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter new password to reset">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label fw-semibold">Phone / WhatsApp Number</label>
                                <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $candidate->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- PARTY BRANDING & ELECTORAL SYMBOL SECTION -->
                            <div class="col-12 mt-4">
                                <div class="p-3 rounded bg-light border border-info-subtle">
                                    <h6 class="fw-bold text-info-emphasis mb-2"><i class="bi bi-flag-fill me-1 text-primary"></i> Party Branding & Candidate Media (For Mobile App & Voter Parchi)</h6>
                                    <div class="row g-3">
                                        <!-- Party Selection -->
                                        <div class="col-md-6">
                                            <label for="party_name" class="form-label fw-semibold">Political Party / Affiliation</label>
                                            <select name="party_name" id="party_name" class="form-select @error('party_name') is-invalid @enderror" onchange="toggleIndependent(this.value)">
                                                <option value="">-- Select Party (or Independent) --</option>
                                                <option value="PTI" {{ old('party_name', $candidate->party_name) == 'PTI' ? 'selected' : '' }}>PTI (Pakistan Tehreek-e-Insaf)</option>
                                                <option value="PMLN" {{ old('party_name', $candidate->party_name) == 'PMLN' ? 'selected' : '' }}>PML-N (Pakistan Muslim League N)</option>
                                                <option value="PPP" {{ old('party_name', $candidate->party_name) == 'PPP' ? 'selected' : '' }}>PPP (Pakistan Peoples Party)</option>
                                                <option value="TLP" {{ old('party_name', $candidate->party_name) == 'TLP' ? 'selected' : '' }}>TLP (Tehreek Labbaik Pakistan)</option>
                                                <option value="JUI" {{ old('party_name', $candidate->party_name) == 'JUI' ? 'selected' : '' }}>JUI-F (Jamiat Ulema-e-Islam)</option>
                                                <option value="Independent" {{ old('party_name', $candidate->party_name) == 'Independent' ? 'selected' : '' }}>Independent / Azad Candidate</option>
                                            </select>
                                            @error('party_name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Independent Checkbox -->
                                        <div class="col-md-6 d-flex align-items-center pt-md-4">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_independent" id="is_independent" value="1" {{ old('is_independent', $candidate->is_independent) ? 'checked' : '' }}>
                                                <label class="form-check-label fw-semibold" for="is_independent">
                                                    Azad Umedwar (Independent Candidate - No Party Logo on Parchi)
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Electoral Symbol Name (Nishan) -->
                                        <div class="col-md-6">
                                            <label for="candidate_symbol" class="form-label fw-semibold">Electoral Symbol Name (Nishan)</label>
                                            <input type="text" name="candidate_symbol" id="candidate_symbol" class="form-control @error('candidate_symbol') is-invalid @enderror" value="{{ old('candidate_symbol', $candidate->candidate_symbol) }}" placeholder="e.g. Bat (Balle), Lion (Sher), Arrow (Teer), Bucket (Balti), Crane...">
                                            <small class="text-muted">Symbol name printed on voter parchi slip.</small>
                                            @error('candidate_symbol')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Candidate Photo Upload & Preview -->
                                        <div class="col-md-6">
                                            <label for="candidate_image" class="form-label fw-semibold">Candidate Photo (Top Right on Parchi)</label>
                                            @if ($candidate->candidate_image)
                                                <div class="mb-2">
                                                    <img src="{{ asset($candidate->candidate_image) }}" alt="Candidate Photo" class="rounded border shadow-sm" style="max-height: 70px; object-fit: contain;">
                                                    <span class="badge bg-secondary ms-1">Current Photo</span>
                                                </div>
                                            @endif
                                            <input type="file" name="candidate_image" id="candidate_image" class="form-control @error('candidate_image') is-invalid @enderror" accept="image/*">
                                            <small class="text-muted">Upload new photo to replace</small>
                                            @error('candidate_image')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Party Logo Upload & Preview -->
                                        <div class="col-md-6">
                                            <label for="party_logo" class="form-label fw-semibold">Party Logo (Top Left on Parchi for Party Candidates)</label>
                                            @if ($candidate->party_logo)
                                                <div class="mb-2">
                                                    <img src="{{ asset($candidate->party_logo) }}" alt="Party Logo" class="rounded border shadow-sm" style="max-height: 70px; object-fit: contain;">
                                                    <span class="badge bg-secondary ms-1">Current Party Logo</span>
                                                </div>
                                            @endif
                                            <input type="file" name="party_logo" id="party_logo" class="form-control @error('party_logo') is-invalid @enderror" accept="image/*">
                                            <small class="text-muted">Upload new logo to replace</small>
                                            @error('party_logo')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Candidate Symbol Image Upload & Preview -->
                                        <div class="col-md-6">
                                            <label for="candidate_symbol_image" class="form-label fw-semibold">Electoral Symbol Logo / Nishan Image</label>
                                            @if ($candidate->candidate_symbol_image)
                                                <div class="mb-2">
                                                    <img src="{{ asset($candidate->candidate_symbol_image) }}" alt="Symbol Image" class="rounded border shadow-sm" style="max-height: 70px; object-fit: contain;">
                                                    <span class="badge bg-secondary ms-1">Current Symbol Logo</span>
                                                </div>
                                            @endif
                                            <input type="file" name="candidate_symbol_image" id="candidate_symbol_image" class="form-control @error('candidate_symbol_image') is-invalid @enderror" accept="image/*">
                                            <small class="text-muted">Upload new symbol graphic to replace</small>
                                            @error('candidate_symbol_image')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- LOCATION SELECTION: STEP 1 TEHSIL -> STEP 2 UC -->
                            <div class="col-12 mt-4">
                                <div class="p-3 rounded bg-light border border-primary-subtle">
                                    <h6 class="fw-bold text-primary mb-2"><i class="bi bi-geo-alt-fill me-1"></i> Electoral Area Assignment (Tehsil &rarr; UC)</h6>
                                    <div class="row g-3">
                                        <!-- Step 1: Select Tehsil -->
                                        <div class="col-md-6">
                                            <label for="tehsilSelect" class="form-label fw-semibold">Step 1: Select Tehsil <span class="text-danger">*</span></label>
                                            <select id="tehsilSelect" class="form-select border-primary" required>
                                                <option value="">-- Choose Tehsil --</option>
                                                @foreach ($tehsils as $t)
                                                    <option value="{{ $t->id }}">
                                                        {{ $t->district->name ?? 'District' }} &bull; {{ $t->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Step 2: Select UC -->
                                        <div class="col-md-6">
                                            <label for="ucSelect" class="form-label fw-semibold">Step 2: Assigned Union Council (UC) <span class="text-danger">*</span></label>
                                            <select name="uc_id" id="ucSelect" class="form-select @error('uc_id') is-invalid @enderror" required>
                                                <option value="">-- Please select Tehsil first --</option>
                                            </select>
                                            <small class="text-muted" id="ucCountText"></small>
                                            @error('uc_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Allowed Devices Limit (Unlimited Default) -->
                            <div class="col-md-4 mt-3">
                                <label for="max_devices" class="form-label fw-semibold">Allowed Device Logins</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                                    <input type="number" name="max_devices" id="max_devices" class="form-control @error('max_devices') is-invalid @enderror" value="{{ old('max_devices', $candidate->max_devices) }}" placeholder="Unlimited (Default)" min="1" max="5000">
                                </div>
                                <small class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i> Unlimited Free Device Logins</small>
                            </div>

                            <div class="col-md-4 mt-3">
                                <label for="status" class="form-label fw-semibold">Account Status <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', $candidate->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ old('status', $candidate->status) == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mt-3">
                                <label for="expires_at" class="form-label fw-semibold">Subscription Expiry Date</label>
                                <input type="date" name="expires_at" id="expires_at" class="form-control @error('expires_at') is-invalid @enderror" value="{{ old('expires_at', $candidate->expires_at ? $candidate->expires_at->format('Y-m-d') : '') }}">
                                @error('expires_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('candidates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Update Candidate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Tehsil -> UC and Independent Toggle JS -->
    <script>
        function toggleIndependent(val) {
            const indCheck = document.getElementById('is_independent');
            if (val === 'Independent' || val === 'Azad') {
                indCheck.checked = true;
            }
        }

        const ucsData = [
            @foreach ($ucs as $uc)
                {
                    id: {{ $uc->id }},
                    tehsil_id: {{ $uc->tehsil_id }},
                    uc_no: "{{ $uc->uc_no ?: $uc->id }}",
                    name: "{{ addslashes($uc->name) }}",
                    name_ur: "{{ addslashes($uc->name_ur ?? '') }}",
                    na: "{{ $uc->nationalAssembly ? $uc->nationalAssembly->code : '' }}",
                    pp: "{{ $uc->provincialAssembly ? $uc->provincialAssembly->code : '' }}"
                },
            @endforeach
        ];

        const tehsilSelect = document.getElementById('tehsilSelect');
        const ucSelect = document.getElementById('ucSelect');
        const ucCountText = document.getElementById('ucCountText');
        const currentUcId = "{{ old('uc_id', $candidate->uc_id) }}";

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
                const naPp = (uc.na || uc.pp) ? ' [' + [uc.na, uc.pp].filter(Boolean).join('/') + ']' : '';
                html += `<option value="${uc.id}" ${isSel}>UC ${uc.uc_no}: ${uc.name}${urduPart}${naPp}</option>`;
            });

            ucSelect.innerHTML = html;
            ucSelect.disabled = false;
            ucCountText.textContent = filteredUcs.length + ' Union Councils available for this Tehsil.';
        }

        tehsilSelect.addEventListener('change', function () {
            updateUcsForTehsil(this.value);
        });

        if (currentUcId) {
            const matchedUc = ucsData.find(u => u.id == currentUcId);
            if (matchedUc) {
                tehsilSelect.value = matchedUc.tehsil_id;
                updateUcsForTehsil(matchedUc.tehsil_id, currentUcId);
            }
        }
    </script>
@endsection
