@extends('layouts.app')

@section('title', 'Door-to-Door Gharana Sentiment Matrix')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="page-title mb-0">Door-to-Door Gharana Sentiment Intelligence</h4>
        <small class="text-muted">Field workers door-to-door household sentiment survey, UC-wise political pulse & VIP visit radar</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('surveys.vip-radar') }}" class="btn btn-purple text-white shadow-sm position-relative" style="background-color: #7c3aed; border-color: #6d28d9;">
            <i class="bi bi-radar me-1"></i> VIP Visit Radar
            @if($vipCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                    {{ $vipCount }}
                </span>
            @endif
        </a>
        <a href="{{ route('campaign-workers.index') }}" class="btn btn-outline-dark">
            <i class="bi bi-people me-1"></i> Field Workers
        </a>
    </div>
</div>

<!-- Top Stats Matrix Cards -->
<div class="row g-3 mb-3">
    <!-- Total Households -->
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Surveyed Gharanas</span>
                        <h3 class="mb-0 fw-bold mt-1 text-white">{{ number_format($totalGharanas) }}</h3>
                        <small class="text-white-50"><i class="bi bi-people me-1"></i>{{ number_format($totalVoters) }} Total Voters</small>
                    </div>
                    <div class="rounded-3 p-2 bg-white bg-opacity-10 text-white fs-4">
                        <i class="bi bi-houses"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pakka Votes (Green) -->
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #065f46, #047857); color: #fff;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pakka Support (پکا)</span>
                        <h3 class="mb-0 fw-bold mt-1 text-white">{{ number_format($pakkaCount) }} <span class="fs-6 fw-normal text-white-50">Gharanas</span></h3>
                        <small class="text-white-50"><i class="bi bi-check2-circle me-1"></i><strong>{{ number_format($pakkaVoters) }}</strong> Votes ({{ $pakkaPct }}%)</small>
                    </div>
                    <div class="rounded-3 p-2 bg-white bg-opacity-10 text-white fs-4">
                        <i class="bi bi-hand-thumbs-up-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kacha / Swing Votes (Amber) -->
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #b45309, #d97706); color: #fff;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Kacha / Swing (کچا)</span>
                        <h3 class="mb-0 fw-bold mt-1 text-white">{{ number_format($kachaCount) }} <span class="fs-6 fw-normal text-white-50">Gharanas</span></h3>
                        <small class="text-white-50"><i class="bi bi-arrow-left-right me-1"></i><strong>{{ number_format($kachaVoters) }}</strong> Swing ({{ $kachaPct }}%)</small>
                    </div>
                    <div class="rounded-3 p-2 bg-white bg-opacity-10 text-white fs-4">
                        <i class="bi bi-question-circle-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mukhalif (Red) -->
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #991b1b, #b91c1c); color: #fff;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Mukhalif (مخالف)</span>
                        <h3 class="mb-0 fw-bold mt-1 text-white">{{ number_format($mukhalifCount) }} <span class="fs-6 fw-normal text-white-50">Gharanas</span></h3>
                        <small class="text-white-50"><i class="bi bi-x-circle me-1"></i>{{ number_format($mukhalifVoters) }} Votes ({{ $mukhalifPct }}%)</small>
                    </div>
                    <div class="rounded-3 p-2 bg-white bg-opacity-10 text-white fs-4">
                        <i class="bi bi-hand-thumbs-down-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VIP Requests (Purple) -->
    <div class="col-6 col-md-4 col-xl">
        <div class="card shadow-sm border-0 h-100" style="background: linear-gradient(135deg, #5b21b6, #7c3aed); color: #fff;">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-white-50 text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.5px;">VIP Daurah Radar</span>
                        <h3 class="mb-0 fw-bold mt-1 text-white">{{ number_format($vipCount) }} <span class="fs-6 fw-normal text-white-50">Visits</span></h3>
                        <small class="text-white-50"><i class="bi bi-geo-alt-fill me-1"></i>Immediate Attention</small>
                    </div>
                    <div class="rounded-3 p-2 bg-white bg-opacity-10 text-white fs-4">
                        <i class="bi bi-radar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Political Pulse & Live Sentiment Distribution Bar -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark px-2 py-1"><i class="bi bi-bar-chart-fill me-1"></i>Political Sentiment Pulse</span>
                <span class="text-muted small">Visual Vote Share Distribution Across Surveyed Households</span>
            </div>
            <div class="d-flex align-items-center gap-3 small fw-semibold">
                <span class="text-success"><i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i>Pakka: {{ $pakkaPct }}% ({{ number_format($pakkaVoters) }} votes)</span>
                <span class="text-warning-emphasis"><i class="bi bi-circle-fill text-warning me-1" style="font-size: 0.6rem;"></i>Swing: {{ $kachaPct }}% ({{ number_format($kachaVoters) }} votes)</span>
                <span class="text-danger"><i class="bi bi-circle-fill me-1" style="font-size: 0.6rem;"></i>Mukhalif: {{ $mukhalifPct }}% ({{ number_format($mukhalifVoters) }} votes)</span>
            </div>
        </div>
        <div class="progress" style="height: 14px; border-radius: 8px; overflow: hidden; background-color: #e2e8f0;">
            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $pakkaPct }}%;" title="Pakka: {{ $pakkaPct }}% ({{ $pakkaVoters }} Votes)"></div>
            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $kachaPct }}%;" title="Kacha / Swing: {{ $kachaPct }}% ({{ $kachaVoters }} Votes)"></div>
            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $mukhalifPct }}%;" title="Mukhalif: {{ $mukhalifPct }}% ({{ $mukhalifVoters }} Votes)"></div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card shadow-sm mb-4 border-0">
    <div class="card-header bg-white py-3 border-bottom">
        <form method="GET" action="{{ route('surveys.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Candidate (امیدوار)</label>
                <select name="candidate_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Candidates (تمام امیدوار)</option>
                    @foreach($candidates as $c)
                        <option value="{{ $c->id }}" {{ request('candidate_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->party_name ?? 'Independent' }}) &bull; {{ $c->uc?->name ?? 'Assigned UC' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Union Council (یوسی)</label>
                <select name="uc_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All UCs (تمام یونین کونسلز)</option>
                    @foreach($ucs as $u)
                        <option value="{{ $u->id }}" {{ request('uc_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->tehsil?->name ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Sentiment (رجحان)</label>
                <select name="sentiment" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ request('sentiment') == 'all' ? 'selected' : '' }}>All Sentiments</option>
                    <option value="pakka" {{ request('sentiment') == 'pakka' ? 'selected' : '' }}>🟢 Pakka (پکا)</option>
                    <option value="kacha" {{ request('sentiment') == 'kacha' ? 'selected' : '' }}>🟡 Kacha (کچا / Swing)</option>
                    <option value="mukhalif" {{ request('sentiment') == 'mukhalif' ? 'selected' : '' }}>🔴 Mukhalif (مخالف)</option>
                    <option value="unassigned" {{ request('sentiment') == 'unassigned' ? 'selected' : '' }}>⚪ Unassigned (غیر جانبدار)</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">VIP Daurah (دورہ)</label>
                <select name="vip" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Gharanas</option>
                    <option value="1" {{ request('vip') == '1' ? 'selected' : '' }}>🚨 VIP Requested Only</option>
                    <option value="0" {{ request('vip') === '0' ? 'selected' : '' }}>Regular Visits</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Search Keyword / Block Code</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Influencer, Phone, Block, Gharana..." value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    @if(request()->hasAny(['candidate_id', 'uc_id', 'sentiment', 'vip', 'block_code', 'search']))
                        <a href="{{ route('surveys.index') }}" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 140px;">UC &amp; Delimitation</th>
                    <th style="min-width: 160px;">Candidate (امیدوار)</th>
                    <th style="min-width: 150px;">Field Worker (ورکر)</th>
                    <th style="min-width: 180px;">Gharana &amp; Influencer</th>
                    <th class="text-center" style="width: 80px;">Voters</th>
                    <th style="min-width: 120px;">Sentiment</th>
                    <th style="min-width: 130px;">VIP Daurah</th>
                    <th class="text-end" style="width: 90px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($surveys as $survey)
                    @php
                        $cleanPhone = preg_replace('/[^0-9]/', '', (string)($survey->influencer_phone ?? ''));
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '92' . substr($cleanPhone, 1);
                        }

                        // Resolve UC accurately
                        $resolvedUc = $survey->blockCodeModel?->uc ?? $survey->candidate?->uc;
                    @endphp
                    <tr>
                        <!-- 1. UC & Delimitation -->
                        <td>
                            @if($resolvedUc)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 fw-bold">
                                    <i class="bi bi-geo-alt-fill me-1"></i>{{ $resolvedUc->name }}
                                </span>
                                <small class="text-muted d-block mt-1">
                                    {{ $resolvedUc->tehsil?->name ?? 'Constituency' }}
                                </small>
                            @else
                                <span class="badge bg-secondary">UC Unassigned</span>
                            @endif
                            <div class="font-monospace fw-semibold text-dark mt-1" style="font-size: 0.8rem;">
                                <i class="bi bi-collection me-1 text-secondary"></i>Block {{ $survey->block_code }}
                            </div>
                            <small class="text-muted d-block text-truncate" style="max-width: 140px;">
                                {{ $survey->blockCodeModel?->area_name ?? 'Census Block' }}
                            </small>
                        </td>

                        <!-- 2. Candidate & Party -->
                        <td>
                            @if($survey->candidate)
                                <div class="fw-bold text-dark">
                                    {{ $survey->candidate->name }}
                                </div>
                                <div class="d-flex align-items-center gap-1 mt-1">
                                    <span class="badge bg-dark text-white">
                                        {{ $survey->candidate->party_name ?? 'Independent' }}
                                    </span>
                                    @if($survey->candidate->candidate_symbol)
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-shield text-warning me-1"></i>{{ $survey->candidate->candidate_symbol }}
                                        </span>
                                    @endif
                                </div>
                                @if($survey->candidate->candidate_code)
                                    <small class="text-muted font-monospace d-block mt-0.5" style="font-size: 0.72rem;">
                                        Code: {{ $survey->candidate->candidate_code }}
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </td>

                        <!-- 3. Field Worker Details -->
                        <td>
                            <div class="fw-semibold text-dark">
                                <i class="bi bi-person-badge-fill text-primary me-1"></i>
                                {{ $survey->worker?->name ?? 'Mobile App Staff' }}
                            </div>
                            @if($survey->worker?->phone)
                                <small class="text-muted d-block mt-0.5">
                                    <i class="bi bi-telephone me-1"></i>{{ $survey->worker->phone }}
                                </small>
                            @endif
                            <small class="text-secondary d-block mt-1" style="font-size: 0.72rem;">
                                <i class="bi bi-clock-history me-1"></i>{{ $survey->visited_at ? $survey->visited_at->diffForHumans() : $survey->updated_at->diffForHumans() }}
                            </small>
                        </td>

                        <!-- 4. Gharana & Influencer Details -->
                        <td>
                            <div class="d-flex align-items-center gap-1.5 mb-1">
                                <span class="badge bg-dark bg-opacity-10 text-dark fw-bold px-2 py-0.5 font-monospace">
                                    Gharana #{{ $survey->gharana_no }}
                                </span>
                                <span class="fw-bold text-dark">
                                    {{ $survey->influencer_name ?: 'Family Head / Resident' }}
                                </span>
                            </div>
                            @if($survey->influencer_phone)
                                <div class="small d-flex align-items-center gap-2 mt-1">
                                    <a href="tel:{{ $survey->influencer_phone }}" class="text-muted text-decoration-none">
                                        <i class="bi bi-telephone me-1"></i>{{ $survey->influencer_phone }}
                                    </a>
                                    @if(!empty($cleanPhone))
                                        <a href="https://wa.me/{{ $cleanPhone }}?text={{ rawurlencode('Salam ' . ($survey->influencer_name ?: 'Sahab') . '! Regarding election campaign visit for Block ' . $survey->block_code . ', Gharana #' . $survey->gharana_no) }}" 
                                           target="_blank" 
                                           class="badge bg-success text-white text-decoration-none px-2 py-0.5"
                                           title="WhatsApp Chat">
                                            <i class="bi bi-whatsapp me-1"></i>WhatsApp
                                        </a>
                                    @endif
                                </div>
                            @endif
                            @if($survey->notes)
                                <div class="small text-muted mt-1 p-1 bg-light rounded" style="font-size: 0.75rem;">
                                    <i class="bi bi-chat-left-text text-secondary me-1"></i>"{{ Str::limit($survey->notes, 65) }}"
                                </div>
                            @endif
                        </td>

                        <!-- 5. Voter Count -->
                        <td class="text-center">
                            <span class="badge bg-secondary rounded-pill px-2.5 py-1 fw-bold fs-6">
                                {{ $survey->voter_count ?: 1 }}
                            </span>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">Votes</small>
                        </td>

                        <!-- 6. Sentiment Badge -->
                        <td>
                            @if($survey->sentiment === 'pakka')
                                <span class="badge px-2.5 py-1.5 text-white fw-bold shadow-xs" style="background-color: #16a34a; font-size: 0.78rem;">
                                    <i class="bi bi-check-circle-fill me-1"></i>Pakka (پکا)
                                </span>
                            @elseif($survey->sentiment === 'kacha')
                                <span class="badge px-2.5 py-1.5 text-dark fw-bold shadow-xs" style="background-color: #f59e0b; font-size: 0.78rem;">
                                    <i class="bi bi-question-circle-fill me-1"></i>Kacha (کچا / Swing)
                                </span>
                            @elseif($survey->sentiment === 'mukhalif')
                                <span class="badge px-2.5 py-1.5 text-white fw-bold shadow-xs" style="background-color: #dc2626; font-size: 0.78rem;">
                                    <i class="bi bi-x-circle-fill me-1"></i>Mukhalif (مخالف)
                                </span>
                            @else
                                <span class="badge px-2.5 py-1.5 text-white fw-bold shadow-xs" style="background-color: #64748b; font-size: 0.78rem;">
                                    <i class="bi bi-dash-circle me-1"></i>Unassigned
                                </span>
                            @endif
                        </td>

                        <!-- 7. VIP Request Status -->
                        <td>
                            @if($survey->is_vip_visit_requested)
                                <span class="badge bg-purple text-white px-2 py-1 shadow-sm" style="background-color: #7c3aed;">
                                    <i class="bi bi-radar me-1"></i>VIP Daurah Needed
                                </span>
                            @else
                                <span class="text-muted small"><i class="bi bi-dash"></i></span>
                            @endif
                        </td>

                        <!-- 8. Action -->
                        <td class="text-end">
                            <form action="{{ route('surveys.toggle-vip', $survey) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $survey->is_vip_visit_requested ? 'btn-outline-danger' : 'btn-outline-purple' }}" 
                                        style="{{ !$survey->is_vip_visit_requested ? 'color: #7c3aed; border-color: #7c3aed;' : '' }}"
                                        title="{{ $survey->is_vip_visit_requested ? 'Mark Daurah as Done / Resolved' : 'Escalate to VIP Request' }}">
                                    @if($survey->is_vip_visit_requested)
                                        <i class="bi bi-check2-circle"></i> Done
                                    @else
                                        <i class="bi bi-star"></i> VIP
                                    @endif
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-search fs-1 d-block mb-2 text-secondary"></i>
                            No door-to-door survey records match your search criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($surveys->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $surveys->links() }}
        </div>
    @endif
</div>
@endsection
