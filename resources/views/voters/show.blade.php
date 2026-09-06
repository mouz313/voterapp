@extends('layouts.app')

@section('title', 'Voter Details')

@section('content')
    <div class="mb-3 no-print">
        <a href="{{ route('voters.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back to Voters</a>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 no-print">
        <h4 class="page-title mb-0">Voter Details</h4>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" onclick="printVoterParchi('{{ Str::slug('Voter Slip '.$voter->formatted_cnic.' '.$voter->name) }}')">
                <i class="bi bi-printer me-1"></i> Print / Save PDF
            </button>
            <a href="{{ route('voters.edit', $voter) }}" class="btn btn-outline-secondary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <form method="POST" action="{{ route('voters.destroy', $voter) }}" class="d-inline" onsubmit="return confirm('Delete this voter?');">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i> Delete</button>
            </form>
        </div>
    </div>

    <!-- On-screen: full data in a 6x2 grid -->
    <div class="card shadow-sm border-0 no-print">
        <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="fw-semibold">Voter Information</span>
            <span class="badge bg-brand" style="background: var(--brand); color:#fff; font-family: ui-monospace, Menlo, Consolas, monospace;">
                {{ $voter->formatted_cnic }}
            </span>
        </div>
        <div class="card-body">
            <div class="voter-grid">
                <div class="vg-cell"><div class="vg-label">Name</div><div class="vg-value">{{ $voter->name }}</div></div>
                <div class="vg-cell"><div class="vg-label">Father / Husband</div><div class="vg-value">{{ $voter->father_name }}</div></div>
                <div class="vg-cell"><div class="vg-label">Age</div><div class="vg-value">{{ $voter->age ?? '-' }}</div></div>
                <div class="vg-cell"><div class="vg-label">CNIC</div><div class="vg-value cnic-cell">{{ $voter->formatted_cnic }}</div></div>
                <div class="vg-cell"><div class="vg-label">Block Code</div><div class="vg-value">{{ $voter->blockCode->code ?? '-' }}</div></div>
                <div class="vg-cell"><div class="vg-label">Polling Station</div><div class="vg-value">{{ $voter->pollingStation->name ?? '-' }}</div></div>

                <div class="vg-cell"><div class="vg-label">Silsala No</div><div class="vg-value">{{ $voter->silsala_no ?? '-' }}</div></div>
                <div class="vg-cell"><div class="vg-label">Gharana No</div><div class="vg-value">{{ $voter->gharana_no ?? '-' }}</div></div>
                <div class="vg-cell"><div class="vg-label">Address</div><div class="vg-value">{{ $voter->address ?? '-' }}</div></div>
                <div class="vg-cell"><div class="vg-label">UC</div><div class="vg-value">{{ $voter->uc->name ?? '-' }}</div></div>
                <div class="vg-cell"><div class="vg-label">Tehsil</div><div class="vg-value">{{ $voter->uc->tehsil->name ?? '-' }}</div></div>
                <div class="vg-cell"><div class="vg-label">District</div><div class="vg-value">{{ $voter->uc->tehsil->district->name ?? '-' }}</div></div>
            </div>
        </div>
    </div>

    <!-- Household (same Gharana No) -->
    @if ($family->isNotEmpty())
    <div class="mt-4 no-print">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h6 class="text-muted mb-0">
                Household &mdash; Gharana No: <strong class="text-dark">{{ $voter->gharana_no }}</strong>
            </h6>
            <span class="badge bg-brand" style="background: var(--brand); color:#fff;">
                {{ $family->count() }} voter{{ $family->count() === 1 ? '' : 's' }} in this house
            </span>
        </div>
        <div class="card shadow-sm">
            <div class="table-scroll">
                <table class="table table-hover align-middle mb-0 household-table">
                    <thead class="table-light">
                        <tr>
                            <th>Silsala</th><th>CNIC</th><th>Name</th><th>Father</th>
                            <th>Age</th><th>Block</th><th>Station</th><th class="text-end">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($family as $member)
                            <tr class="{{ $member->id === $voter->id ? 'table-primary' : '' }}">
                                <td>{{ $member->silsala_no ?? '-' }}</td>
                                <td class="cnic-cell">{{ $member->formatted_cnic }}</td>
                                <td class="name-cell">{{ $member->name }}</td>
                                <td class="muted-cell">{{ $member->father_name }}</td>
                                <td>{{ $member->age ?? '-' }}</td>
                                <td><span class="badge badge-block">{{ $member->blockCode->code ?? '-' }}</span></td>
                                <td><span class="badge badge-station">{{ $member->pollingStation->name ?? '-' }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('voters.show', $member) }}" class="btn btn-sm btn-outline-info btn-action" title="View"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Related data (on-screen only) -->
    <div class="mt-4 no-print">
        <h6 class="text-muted mb-2">Related Records</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">UC</div>
                    <div class="card-body small">
                        <div><strong>Name:</strong> {{ $voter->uc->name ?? '-' }}</div>
                        <div><strong>Tehsil:</strong> {{ $voter->uc->tehsil->name ?? '-' }}</div>
                        <div><strong>District:</strong> {{ $voter->uc->tehsil->district->name ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Block Code</div>
                    <div class="card-body small">
                        <div><strong>Code:</strong> {{ $voter->blockCode->code ?? '-' }}</div>
                        @if ($voter->blockCode->description)
                            <div><strong>Description:</strong> {{ $voter->blockCode->description }}</div>
                        @endif
                        <div><strong>UC:</strong> {{ $voter->blockCode->uc->name ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                        <span>Polling Station</span>
                        @if ($voter->pollingStation)
                            <span class="badge bg-{{ $voter->pollingStation->gender_badge_color }}-subtle text-{{ $voter->pollingStation->gender_badge_color }} border border-{{ $voter->pollingStation->gender_badge_color }}-subtle">
                                <i class="bi {{ $voter->pollingStation->gender_icon }} me-1"></i>{{ $voter->pollingStation->gender_label_ur }}
                            </span>
                        @endif
                    </div>
                    <div class="card-body small">
                        <div><strong>Name:</strong> {{ $voter->pollingStation ? (($voter->pollingStation->station_no ? '#' . $voter->pollingStation->station_no . ' ' : '') . $voter->pollingStation->name) : '-' }}</div>
                        @if ($voter->pollingStation && $voter->pollingStation->address)
                            <div><strong>Address:</strong> {{ $voter->pollingStation->address }}</div>
                        @endif
                        <div><strong>UC:</strong> {{ $voter->pollingStation->uc->name ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print-only parchi (6 x 3 inches) -->
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
                        @if ($voter->uc->tehsil->name) / {{ $voter->uc->tehsil->name }} @endif
                        @if ($voter->uc->name) / {{ $voter->uc->name }} @endif
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
                            {{ $voter->pollingStation->station_no ? '#' . $voter->pollingStation->station_no . ' ' : '' }}{{ $voter->pollingStation->name }} ({{ $voter->pollingStation->gender_label_ur }})
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
