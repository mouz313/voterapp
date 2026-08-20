@extends('layouts.app')

@section('title', 'Voter Search')

@section('content')
    <div class="mb-3">
        <h4 class="page-title mb-0">Voter Search</h4>
        <small class="text-muted">Look up a voter by CNIC or list a family by Gharana number</small>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('search.index') }}">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="by" id="byCnic" value="cnic" {{ ($by ?? 'cnic') == 'cnic' ? 'checked' : '' }} autocomplete="off">
                            <label class="btn btn-outline-primary" for="byCnic"><i class="bi bi-person-vcard me-1"></i> CNIC</label>

                            <input type="radio" class="btn-check" name="by" id="byGharana" value="gharana" {{ ($by ?? '') == 'gharana' ? 'checked' : '' }} autocomplete="off">
                            <label class="btn btn-outline-primary" for="byGharana"><i class="bi bi-people me-1"></i> Gharana No</label>
                        </div>
                    </div>
                    <div class="col">
                        <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control"
                               placeholder="Enter CNIC (e.g. 12345-1234567-8) or Gharana number" required>
                    </div>
                    <div class="col-md-3" id="ucField" style="display:none;">
                        <select name="uc_id" class="form-select" title="Select Union Council">
                            <option value="">Select Union Council</option>
                            @foreach ($ucs as $uc)
                                <option value="{{ $uc->id }}" {{ ($ucId ?? '') == $uc->id ? 'selected' : '' }}>{{ $uc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary"><i class="bi bi-search me-1"></i> Search</button>
                        <a href="{{ route('search.index') }}" class="btn btn-outline-secondary ms-2"><i class="bi bi-x-circle me-1"></i> Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var byCnic = document.getElementById('byCnic');
            var byGharana = document.getElementById('byGharana');
            var ucField = document.getElementById('ucField');
            function toggle() {
                ucField.style.display = byGharana.checked ? 'block' : 'none';
            }
            byCnic.addEventListener('change', toggle);
            byGharana.addEventListener('change', toggle);
            toggle();
        })();
    </script>

    @if (isset($error))
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1"></i> {{ $error }}</div>
    @endif

    @if (isset($voter) && $voter)
        <div class="card shadow-sm mb-4 border-success">
            <div class="card-header bg-success-subtle d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-person-check me-1"></i> Voter Found</span>
                <span class="badge bg-light text-dark">{{ $voter->formatted_cnic }}</span>
            </div>
            <div class="card-body row g-3">
                <div class="col-md-4"><div class="text-muted small">Name</div><div class="fw-semibold">{{ $voter->name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Father Name</div><div class="fw-semibold">{{ $voter->father_name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">CNIC</div><div class="fw-semibold">{{ $voter->formatted_cnic }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Silsala No</div><div class="fw-semibold">{{ $voter->silsala_no ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Gharana No</div><div class="fw-semibold">{{ $voter->gharana_no ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Polling Station</div><div class="fw-semibold">{{ $voter->pollingStation->name ?? '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Block Code</div><div class="fw-semibold">{{ $voter->blockCode->code ?? '-' }}</div></div>
                <div class="col-md-8"><div class="text-muted small">Location</div>
                    <div class="fw-semibold">
                        {{ $voter->uc->tehsil->district->name ?? '' }} /
                        {{ $voter->uc->tehsil->name ?? '' }} /
                        {{ $voter->uc->name ?? '' }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (isset($family) && $family->isNotEmpty())
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <span class="fw-semibold">
                    <i class="bi bi-people me-1"></i>
                    Family Members ({{ $family->count() }})
                </span>
                @if (isset($voter) && $voter)
                    <span class="text-muted small">— same Gharana in {{ $voter->uc->name }}</span>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>CNIC</th><th>Name</th><th>Father</th><th>Block</th>
                            <th>Station</th><th>Silsala</th><th>Gharana</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($family as $member)
                            <tr class="{{ isset($voter) && $voter->id === $member->id ? 'table-success' : '' }}">
                                <td>{{ $member->formatted_cnic }}</td>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->father_name }}</td>
                                <td>{{ $member->blockCode->code ?? '-' }}</td>
                                <td>{{ $member->pollingStation->name ?? '-' }}</td>
                                <td>{{ $member->silsala_no ?? '-' }}</td>
                                <td>{{ $member->gharana_no ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
