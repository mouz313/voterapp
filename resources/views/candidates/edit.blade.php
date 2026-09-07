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

                                        <!-- Candidate Campaign Code -->
                                        <div class="col-md-6">
                                            <label for="candidate_code" class="form-label fw-semibold">
                                                Candidate Campaign Code 
                                                <span class="badge bg-primary-subtle text-primary">For Mobile Staff Login</span>
                                            </label>
                                            <input type="text" name="candidate_code" id="candidate_code" class="form-control @error('candidate_code') is-invalid @enderror" value="{{ old('candidate_code', $candidate->candidate_code) }}" placeholder="e.g. PTI-4821">
                                            <small class="text-muted">Workers enter this code on the mobile app to morph theme and verify campaign.</small>
                                            @error('candidate_code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Party Slogan / Tagline -->
                                        <div class="col-md-6">
                                            <label for="party_slogan" class="form-label fw-semibold">Party Slogan / Tagline (Naara)</label>
                                            <input type="text" name="party_slogan" id="party_slogan" class="form-control @error('party_slogan') is-invalid @enderror" value="{{ old('party_slogan', $candidate->party_slogan) }}" placeholder="e.g. Do Nahin Aik Pakistan">
                                            <small class="text-muted">Shows boldly on the mobile login screen and war-room header.</small>
                                            @error('party_slogan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Supreme Leader Photo Upload & Preview -->
                                        <div class="col-md-6">
                                            <label for="leader_image" class="form-label fw-semibold">
                                                Supreme Leader Photo (Qaid Ki Tasweer)
                                                <span class="badge bg-info-subtle text-info-emphasis">Mobile App Header</span>
                                            </label>
                                            @if ($candidate->leader_image)
                                                <div class="mb-2">
                                                    <img src="{{ asset($candidate->leader_image) }}" alt="Supreme Leader" class="rounded border shadow-sm" style="max-height: 70px; object-fit: contain;">
                                                    <span class="badge bg-secondary ms-1">Current Leader Photo</span>
                                                </div>
                                            @endif
                                            <input type="file" name="leader_image" id="leader_image" class="form-control @error('leader_image') is-invalid @enderror" accept="image/*">
                                            <small class="text-muted">Upload new portrait photo to replace</small>
                                            @error('leader_image')
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
                            <!-- 4. APP SALE & FINANCIAL LICENSING DETAILS (PARTY A / PARTY B) -->
                            <div class="col-12 mt-4">
                                <div class="p-3 rounded bg-light border border-warning-subtle">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="bi bi-cash-coin me-1 text-warning"></i> App Sale &amp; Licensing Details (سیلز اور فنانس ریکارڈ)
                                        </h6>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle font-mono">
                                            <i class="bi bi-shield-lock me-1"></i> Finance Vault
                                        </span>
                                    </div>
                                    <small class="text-muted d-block mb-3">Manage the selling party channel and agreed license fee for this candidate</small>

                                    <div class="row g-3">
                                        <!-- Selling Party / Channel -->
                                        <div class="col-md-4">
                                            <label for="sales_party_id" class="form-label fw-semibold">Selling Party / Distributor <span class="text-danger">*</span></label>
                                            <select name="sales_party_id" id="sales_party_id" class="form-select border-warning @error('sales_party_id') is-invalid @enderror" onchange="updateDefaultPrice(this)">
                                                <option value="">-- Direct Sale (No Party) --</option>
                                                @foreach ($salesParties as $sp)
                                                    <option value="{{ $sp->id }}" data-price="{{ $sp->default_price }}" {{ (old('sales_party_id', $candidate->sale?->sales_party_id) == $sp->id) ? 'selected' : '' }}>
                                                        {{ $sp->name }} ({{ $sp->code }}) &bull; Default: PKR {{ number_format($sp->default_price) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('sales_party_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <!-- Price Charged (PKR) -->
                                        <div class="col-md-4">
                                            <label for="sale_amount" class="form-label fw-semibold">App License Price Charged (PKR) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light fw-bold">PKR</span>
                                                <input type="number" name="sale_amount" id="sale_amount" class="form-control font-mono fw-bold text-dark @error('sale_amount') is-invalid @enderror" value="{{ old('sale_amount', $candidate->sale?->sale_amount ?? 20000) }}" min="0" step="500" placeholder="e.g. 20000" required>
                                            </div>
                                            <small class="text-muted">Agreed license fee charged to this candidate</small>
                                            @error('sale_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <!-- Amount Paid (PKR) -->
                                        <div class="col-md-4">
                                            <label for="amount_paid" class="form-label fw-semibold">Amount Received / Paid (PKR)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light fw-bold">PKR</span>
                                                <input type="number" name="amount_paid" id="amount_paid" class="form-control font-mono @error('amount_paid') is-invalid @enderror" value="{{ old('amount_paid', $candidate->sale?->amount_paid ?? 20000) }}" min="0" step="500" placeholder="e.g. 20000">
                                            </div>
                                            <small class="text-muted">Defaults to full price if paid</small>
                                            @error('amount_paid') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <!-- Payment Status -->
                                        <div class="col-md-4">
                                            <label for="payment_status" class="form-label fw-semibold">Payment Status <span class="text-danger">*</span></label>
                                            <select name="payment_status" id="payment_status" class="form-select @error('payment_status') is-invalid @enderror" onchange="syncPaymentStatus(this.value)">
                                                <option value="paid" {{ old('payment_status', $candidate->sale?->payment_status ?? 'paid') === 'paid' ? 'selected' : '' }}>Paid (مکمل وصول شدہ)</option>
                                                <option value="pending" {{ old('payment_status', $candidate->sale?->payment_status ?? 'paid') === 'pending' ? 'selected' : '' }}>Pending (باقیہ / ادائیگی زیر التواء)</option>
                                                <option value="partial" {{ old('payment_status', $candidate->sale?->payment_status ?? 'paid') === 'partial' ? 'selected' : '' }}>Partial (جزوی ادائیگی)</option>
                                            </select>
                                            @error('payment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>

                                        <!-- Payment Method -->
                                        <div class="col-md-4">
                                            <label for="payment_method" class="form-label fw-semibold">Payment Method</label>
                                            <select name="payment_method" id="payment_method" class="form-select">
                                                <option value="Cash" {{ old('payment_method', $candidate->sale?->payment_method ?? 'Cash') === 'Cash' ? 'selected' : '' }}>Cash (نقدی)</option>
                                                <option value="Bank Transfer" {{ old('payment_method', $candidate->sale?->payment_method) === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer (آن لائن بینک)</option>
                                                <option value="JazzCash" {{ old('payment_method', $candidate->sale?->payment_method) === 'JazzCash' ? 'selected' : '' }}>JazzCash</option>
                                                <option value="EasyPaisa" {{ old('payment_method', $candidate->sale?->payment_method) === 'EasyPaisa' ? 'selected' : '' }}>EasyPaisa</option>
                                                <option value="Cheque" {{ old('payment_method', $candidate->sale?->payment_method) === 'Cheque' ? 'selected' : '' }}>Cheque (بینک چیک)</option>
                                            </select>
                                        </div>

                                        <!-- Payment Date -->
                                        <div class="col-md-4">
                                            <label for="payment_date" class="form-label fw-semibold">Payment Date</label>
                                            <input type="date" name="payment_date" id="payment_date" class="form-control" value="{{ old('payment_date', $candidate->sale?->payment_date ? $candidate->sale->payment_date->format('Y-m-d') : date('Y-m-d')) }}">
                                        </div>

                                        <!-- Notes -->
                                        <div class="col-12">
                                            <label for="sales_notes" class="form-label fw-semibold">Sales Notes &amp; Transaction Details (Optional)</label>
                                            <input type="text" name="sales_notes" id="sales_notes" class="form-control" value="{{ old('sales_notes', $candidate->sale?->notes ?? '') }}" placeholder="e.g. Sold via Party A, ref #12345">
                                        </div>
                                    </div>
                                </div>
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
        function updateDefaultPrice(selectEl) {
            const selectedOption = selectEl.options[selectEl.selectedIndex];
            const defaultPrice = selectedOption.getAttribute('data-price');
            if (defaultPrice) {
                const saleInput = document.getElementById('sale_amount');
                if (saleInput) saleInput.value = parseFloat(defaultPrice).toFixed(0);
                const statusSelect = document.getElementById('payment_status');
                if (statusSelect && statusSelect.value === 'paid') {
                    const paidInput = document.getElementById('amount_paid');
                    if (paidInput) paidInput.value = parseFloat(defaultPrice).toFixed(0);
                }
            }
        }

        function syncPaymentStatus(status) {
            const saleAmount = document.getElementById('sale_amount')?.value || 20000;
            const paidInput = document.getElementById('amount_paid');
            if (!paidInput) return;
            if (status === 'paid') {
                paidInput.value = saleAmount;
            } else if (status === 'pending') {
                paidInput.value = 0;
            }
        }

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
