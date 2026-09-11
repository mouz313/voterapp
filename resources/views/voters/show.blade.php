@extends('layouts.app')

@section('title', 'Voter: ' . $voter->name)

@section('content')
    <!-- Top Action Bar (Hidden on Print) -->
    <div class="mb-3 no-print">
        <a href="{{ route('voters.index') }}" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left me-1"></i> Back to Voters Directory
        </a>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 no-print">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-success-subtle border border-success-subtle text-success d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 44px; height: 44px;">
                {{ strtoupper(substr($voter->name, 0, 1)) }}
            </div>
            <div>
                <h4 class="page-title mb-0">{{ $voter->name }}</h4>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-primary border font-monospace">{{ $voter->formatted_cnic }}</span>
                    @if($voter->age)
                        <span class="badge bg-light text-dark border">{{ $voter->age }} years old</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="printVoterParchi('{{ Str::slug('Voter Slip '.$voter->formatted_cnic.' '.$voter->name) }}')">
                <i class="bi bi-printer me-1"></i> Print Parchi / PDF
            </button>
            <a href="{{ route('voters.edit', $voter) }}" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i> Edit Voter
            </a>
            @if(auth()->user()->canDelete())
                <form method="POST" action="{{ route('voters.destroy', $voter) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this voter?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> Delete</button>
                </form>
            @endif
        </div>
    </div>

    <!-- Main Grid (Hidden on Print) -->
    <div class="row g-3 no-print">
        
        <!-- Left Column: Voter Info, Electoral Details & Household -->
        <div class="col-lg-8 col-12">
            
            <!-- Card 1: Voter Profile & Contact -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-person-lines-fill text-success me-2"></i>Personal &amp; Contact Information</h6>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Verified Citizen</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Full Name</label>
                            <span class="fw-bold fs-6 text-dark">{{ $voter->name }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Father / Husband Name</label>
                            <span class="fw-semibold text-dark">{{ $voter->father_name ?: '—' }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">National Identity Card (CNIC)</label>
                            <span class="font-monospace fw-bold fs-6 text-primary">{{ $voter->formatted_cnic }}</span>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small d-block">Phone / Mobile Number</label>
                            @if(!empty($voter->phone))
                                @php
                                    $cleanWa = preg_replace('/[^\d]/', '', $voter->phone);
                                    if (str_starts_with($cleanWa, '0')) $cleanWa = '92' . substr($cleanWa, 1);
                                @endphp
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="fw-bold font-monospace fs-6 text-dark">{{ $voter->phone }}</span>
                                    <a href="https://wa.me/{{ $cleanWa }}?text=Assalam-o-Alaikum%20{{ urlencode($voter->name) }}" target="_blank" class="btn btn-sm btn-success py-0.5 px-2" title="Chat on WhatsApp">
                                        <i class="bi bi-whatsapp"></i> WhatsApp
                                    </a>
                                    <a href="tel:{{ $voter->phone }}" class="btn btn-sm btn-outline-primary py-0.5 px-2" title="Call Now">
                                        <i class="bi bi-telephone"></i> Call
                                    </a>
                                </div>
                            @else
                                <span class="text-muted small fst-italic">No mobile number recorded</span>
                            @endif
                        </div>
                        <div class="col-12">
                            <label class="text-muted small d-block">Residential Address</label>
                            <span class="text-dark">{{ $voter->address ?: 'No address specified in electoral roll' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Electoral Registration & Polling Station -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-box-seam text-primary me-2"></i>Electoral Roll &amp; Polling Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-3 col-6">
                            <div class="p-2.5 rounded-3 bg-light border text-center">
                                <span class="text-muted small d-block">Silsala No</span>
                                <span class="fs-5 fw-bold font-monospace text-dark">{{ $voter->silsala_no ?: '—' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-3 col-6">
                            <div class="p-2.5 rounded-3 bg-light border text-center">
                                <span class="text-muted small d-block">Gharana No</span>
                                <span class="fs-5 fw-bold font-monospace text-primary">{{ $voter->gharana_no ?: '—' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-3 col-6">
                            <div class="p-2.5 rounded-3 bg-light border text-center">
                                <span class="text-muted small d-block">Block Code</span>
                                <span class="fs-6 fw-bold font-monospace text-dark">{{ $voter->blockCode->code ?? '—' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-3 col-6">
                            <div class="p-2.5 rounded-3 bg-light border text-center">
                                <span class="text-muted small d-block">Union Council</span>
                                <span class="fs-6 fw-bold text-dark text-truncate d-block">{{ $voter->uc->name ?? '—' }}</span>
                            </div>
                        </div>

                        <!-- Polling Station Box -->
                        <div class="col-12 mt-3">
                            <div class="p-3 rounded-3 border border-warning-subtle bg-warning-subtle text-dark">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Assigned Polling Station</span>
                                    @if ($voter->pollingStation)
                                        <span class="badge bg-white text-dark border">
                                            {{ $voter->pollingStation->gender_label_ur ?? 'مردانہ و زنانہ' }}
                                        </span>
                                    @endif
                                </div>
                                <h6 class="mb-1 fw-bold text-dark">
                                    {{ $voter->pollingStation ? (($voter->pollingStation->station_no ? '#' . $voter->pollingStation->station_no . ' ' : '') . $voter->pollingStation->name) : 'Polling station not yet assigned' }}
                                </h6>
                                @if($voter->pollingStation && $voter->pollingStation->address)
                                    <small class="text-muted d-block mb-2">{{ $voter->pollingStation->address }}</small>
                                @endif
                                @if($voter->pollingStation)
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($voter->pollingStation->name . ' ' . ($voter->uc->name ?? '')) }}" target="_blank" class="btn btn-sm btn-light border mt-1">
                                        <i class="bi bi-map me-1 text-primary"></i> View on Google Maps
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Household Members (Same Gharana No) -->
            @if ($family->isNotEmpty())
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-semibold text-dark">
                            <i class="bi bi-people-fill text-info me-2"></i>Household Members &mdash; Gharana No: <strong>{{ $voter->gharana_no }}</strong>
                        </h6>
                        <span class="badge bg-light text-dark border">{{ $family->count() }} voters in house</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Silsala</th>
                                    <th>CNIC</th>
                                    <th>Name &amp; Father</th>
                                    <th class="text-center">Age</th>
                                    <th>Phone</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($family as $member)
                                    <tr class="{{ $member->id === $voter->id ? 'table-success' : '' }}">
                                        <td class="font-monospace fw-bold">{{ $member->silsala_no ?: '-' }}</td>
                                        <td class="font-monospace text-primary">{{ $member->formatted_cnic }}</td>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ $member->name }}</span>
                                            @if($member->id === $voter->id)
                                                <span class="badge bg-success ms-1">Current</span>
                                            @endif
                                            <small class="text-muted d-block">{{ $member->father_name }}</small>
                                        </td>
                                        <td class="text-center">{{ $member->age ?: '-' }}</td>
                                        <td>
                                            @if(!empty($member->phone))
                                                <small class="text-success font-monospace"><i class="bi bi-telephone"></i> {{ $member->phone }}</small>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($member->id !== $voter->id)
                                                <a href="{{ route('voters.show', $member) }}" class="btn btn-sm btn-outline-primary" title="View Member">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>

        <!-- Right Column: Candidate Panel & Live Parchi Preview -->
        <div class="col-lg-4 col-12">
            
            <!-- Assigned Candidate Card -->
            @if(isset($candidate) && $candidate)
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-flag-fill text-success me-2"></i>Assigned Campaign Candidate</h6>
                    </div>
                    <div class="card-body text-center">
                        @if($candidate->candidate_image)
                            <img src="{{ asset($candidate->candidate_image) }}" alt="{{ $candidate->name }}" class="rounded-circle border border-2 border-success shadow-sm mb-2" style="width: 76px; height: 76px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-success-subtle text-success mx-auto d-flex align-items-center justify-content-center fw-bold fs-3 border mb-2" style="width: 76px; height: 76px;">
                                {{ strtoupper(substr($candidate->name, 0, 1)) }}
                            </div>
                        @endif

                        <h5 class="fw-bold mb-1 text-dark">{{ $candidate->name }}</h5>
                        @if($candidate->party_name)
                            <span class="badge bg-light text-dark border mb-2">{{ $candidate->party_name }}</span>
                        @endif

                        <div class="p-2.5 rounded-3 bg-light border mt-2">
                            <span class="text-muted small d-block">Electoral Symbol (نشان)</span>
                            <div class="d-flex align-items-center justify-content-center gap-2 mt-1">
                                @if($candidate->candidate_symbol_image)
                                    <img src="{{ asset($candidate->candidate_symbol_image) }}" alt="Symbol" style="max-height: 36px; max-width: 48px; object-fit: contain;">
                                @endif
                                <span class="fw-bold fs-5 text-success">{{ $candidate->candidate_symbol ?: 'Candidate' }}</span>
                            </div>
                        </div>

                        @if($candidate->party_slogan)
                            <p class="text-muted small fst-italic mt-2 mb-0">"{{ $candidate->party_slogan }}"</p>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Live Parchi Slip Preview Card -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-receipt text-secondary me-2"></i>Digital Parchi Preview</h6>
                    <span class="badge bg-light text-dark border">Print Ready</span>
                </div>
                <div class="card-body p-3">
                    <div class="p-3 border rounded-3 bg-light" style="border-style: dashed !important; border-width: 2px !important;">
                        <div class="text-center border-bottom pb-2 mb-2">
                            <span class="fw-bold text-success font-urdu fs-6">انتخابی ووٹر پرچی</span>
                            <div class="small text-muted font-monospace" style="font-size: 0.75rem;">
                                Block: {{ $voter->blockCode->code ?? 'N/A' }} | UC: {{ $voter->uc->name ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-muted">Name:</span>
                            <strong class="text-dark">{{ $voter->name }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-muted">CNIC:</span>
                            <strong class="font-monospace">{{ $voter->formatted_cnic }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-muted">Silsala No:</span>
                            <strong class="badge bg-success">{{ $voter->silsala_no ?: '-' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1 small">
                            <span class="text-muted">Gharana No:</span>
                            <strong class="badge bg-primary">{{ $voter->gharana_no ?: '-' }}</strong>
                        </div>
                        <div class="small mt-2 pt-2 border-top">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Polling Station:</span>
                            <strong class="text-danger small">{{ $voter->pollingStation ? $voter->pollingStation->name : 'Not Assigned' }}</strong>
                        </div>
                    </div>

                    <div class="d-grid mt-3">
                        <button type="button" class="btn btn-primary" onclick="printVoterParchi('{{ Str::slug('Voter Slip '.$voter->formatted_cnic.' '.$voter->name) }}')">
                            <i class="bi bi-printer me-1"></i> Print / Save Slip (PDF)
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Print-only parchi (Clean, High-Res Official Print Layout) -->
    <div class="parchi-print">
        <div class="parchi-slip">
            <div class="ps-header-branding">
                <div class="ps-branding-left">
                    @if (isset($candidate) && !$candidate->is_independent && $candidate->party_logo)
                        <img src="{{ asset($candidate->party_logo) }}" alt="Party Logo" class="ps-logo-img">
                    @elseif (isset($candidate) && $candidate->candidate_symbol_image)
                        <img src="{{ asset($candidate->candidate_symbol_image) }}" alt="Symbol Logo" class="ps-logo-img">
                    @endif
                </div>

                <div class="ps-branding-center">
                    @if (isset($candidate) && $candidate->is_independent)
                        <div class="ps-party-title">AZAD CANDIDATE</div>
                    @elseif (isset($candidate) && $candidate->party_name)
                        <div class="ps-party-title">{{ strtoupper($candidate->party_name) }}</div>
                    @else
                        <div class="ps-party-title">VOTER VERIFICATION SLIP</div>
                    @endif

                    @if (isset($candidate) && $candidate->candidate_symbol)
                        <div class="ps-symbol-badge">
                            @if ($candidate->candidate_symbol_image)
                                <img src="{{ asset($candidate->candidate_symbol_image) }}" class="ps-symbol-inline-icon" alt="Nishan">
                            @endif
                            <span>Nishan: <strong>{{ $candidate->candidate_symbol }}</strong></span>
                        </div>
                    @endif

                    <div class="ps-sub-location">
                        {{ $voter->uc->tehsil->district->name ?? '' }}
                        @if ($voter->uc && $voter->uc->tehsil) / {{ $voter->uc->tehsil->name }} @endif
                        @if ($voter->uc) / {{ $voter->uc->name }} @endif
                    </div>
                </div>

                <div class="ps-branding-right">
                    @if (isset($candidate) && $candidate->candidate_image)
                        <img src="{{ asset($candidate->candidate_image) }}" alt="Candidate Photo" class="ps-candidate-photo">
                    @endif
                </div>
            </div>

            <div class="ps-grid">
                <div class="ps-cell"><span>Name</span><b>{{ $voter->name }}</b></div>
                <div class="ps-cell"><span>Father / Husband</span><b>{{ $voter->father_name }}</b></div>
                <div class="ps-cell"><span>CNIC</span><b class="ps-cnic">{{ $voter->formatted_cnic }}</b></div>
                <div class="ps-cell"><span>Block Code</span><b>{{ $voter->blockCode->code ?? '-' }}</b></div>
                <div class="ps-cell"><span>Silsala No</span><b>{{ $voter->silsala_no ?? '-' }}</b></div>
                <div class="ps-cell"><span>Gharana No</span><b>{{ $voter->gharana_no ?? '-' }}</b></div>
                <div class="ps-cell ps-full">
                    <span>Polling Station</span>
                    <b>
                        @if ($voter->pollingStation)
                            {{ $voter->pollingStation->station_no ? '#' . $voter->pollingStation->station_no . ' ' : '' }}{{ $voter->pollingStation->name }} ({{ $voter->pollingStation->gender_label_ur ?? 'مردانہ و زنانہ' }})
                        @else
                            -
                        @endif
                    </b>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printVoterParchi(filename) {
            var prev = document.title;
            document.title = filename || prev;
            var restore = function () {
                document.title = prev;
                window.removeEventListener('afterprint', restore);
            };
            window.addEventListener('afterprint', restore);
            window.print();
        }
    </script>
@endsection
