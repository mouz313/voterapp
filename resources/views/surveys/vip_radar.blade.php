@extends('layouts.app')

@section('title', 'VIP Visit Radar — Candidate Daurah Priority')

@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h4 class="page-title mb-0 d-flex align-items-center gap-2">
            <span class="badge rounded-pill bg-purple text-white p-2" style="background-color: #7c3aed;">
                <i class="bi bi-radar fs-5"></i>
            </span>
            <span>VIP Visit Radar (وی آئی پی دورہ ریڈار)</span>
        </h4>
        <small class="text-muted">High-priority community influencers, candidate daurah requests & critical swing family households</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('surveys.vip-print', request()->query()) }}" target="_blank" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Route Itinerary
        </a>
        <a href="{{ route('surveys.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Sentiment Matrix
        </a>
    </div>
</div>

<!-- Strategy Alert Banner -->
<div class="alert border-0 shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4" style="background: linear-gradient(135deg, #f5f3ff, #ede9fe); border-left: 4px solid #7c3aed !important;">
    <div class="d-flex align-items-center gap-3">
        <div class="fs-2 text-purple" style="color: #7c3aed;"><i class="bi bi-bullseye"></i></div>
        <div>
            <h6 class="mb-1 fw-bold text-dark">VIP Field Daurah & Swing Voter Conversion</h6>
            <p class="mb-0 small text-muted">
                Yeh woh gharanay hain jahan field workers ne candidate ke zaati dauray ki darkhwast ki hai ya ahem baradari influencers hain. Candidate ka zaati visit in vote banks ko mukammal lock kar sakta hai.
            </p>
        </div>
    </div>
    <div>
        <span class="badge bg-purple text-white fs-6 py-2 px-3 shadow-sm" style="background-color: #7c3aed;">
            {{ $vipList->total() }} Target Households
        </span>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('surveys.vip-radar') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="candidate_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Candidates</option>
                    @foreach($candidates as $c)
                        <option value="{{ $c->id }}" {{ request('candidate_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->party_name ?? 'Independent' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Priority Targets (VIP & High Swing)</option>
                    <option value="requested" {{ request('status') == 'requested' ? 'selected' : '' }}>🚨 Explicit VIP Requests Only</option>
                    <option value="swing_high" {{ request('status') == 'swing_high' ? 'selected' : '' }}>🟡 Large Swing Families (4+ Votes)</option>
                </select>
            </div>

            <div class="col-md-2">
                <input type="text" name="block_code" class="form-control form-control-sm" placeholder="Block Code..." value="{{ request('block_code') }}">
            </div>

            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <input type="text" name="search" class="form-control" placeholder="Influencer Name, Phone, Notes..." value="{{ request('search') }}">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
                    @if(request()->hasAny(['candidate_id', 'status', 'block_code', 'search']))
                        <a href="{{ route('surveys.vip-radar') }}" class="btn btn-outline-secondary" title="Clear"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- VIP Targets Cards Grid -->
<div class="row g-3">
    @forelse($vipList as $target)
        @php
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)($target->influencer_phone ?? ''));
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '92' . substr($cleanPhone, 1);
            }
            $isVip = (bool) $target->is_vip_visit_requested;
        @endphp
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm h-100 border-0 position-relative" style="border-left: 5px solid {{ $isVip ? '#7c3aed' : '#d97706' }} !important;">
                <div class="card-body p-3">
                    <!-- Top row: UC, Candidate, Block & Badges -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-2">
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-bold">
                                <i class="bi bi-geo-alt-fill me-1"></i>{{ $target->blockCodeModel?->uc?->name ?? $target->candidate?->uc?->name ?? 'Assigned UC' }}
                                @if($target->candidate?->uc?->tehsil)
                                    ({{ $target->candidate->uc->tehsil->name }})
                                @endif
                            </span>
                            <span class="badge bg-dark text-white">
                                <i class="bi bi-person-fill me-1 text-warning"></i>{{ $target->candidate?->name ?? 'Candidate' }} ({{ $target->candidate?->party_name ?? 'Independent' }})
                            </span>
                        </div>
                        <div>
                            @if($isVip)
                                <span class="badge bg-purple text-white py-1 px-2 shadow-xs" style="background-color: #7c3aed;">
                                    <i class="bi bi-star-fill me-1 text-warning"></i>VIP Daurah Needed
                                </span>
                            @else
                                <span class="badge bg-warning text-dark py-1 px-2">
                                    <i class="bi bi-arrow-left-right me-1"></i>Swing Target
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Second row: Block, Gharana, and Votes at stake -->
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2 pb-2 border-bottom">
                        <div>
                            <span class="badge bg-dark bg-opacity-10 text-dark font-monospace me-1">Block {{ $target->block_code }}</span>
                            <span class="badge bg-light text-dark border">Gharana #{{ $target->gharana_no }}</span>
                            <small class="text-muted ms-1">
                                {{ $target->blockCodeModel?->area_name ?? 'Area' }}
                            </small>
                        </div>
                        <div>
                            <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2.5 py-1 fw-bold fs-6">
                                <i class="bi bi-people-fill me-1"></i>{{ $target->voter_count ?: 1 }} Votes
                            </span>
                        </div>
                    </div>

                    <!-- Influencer Details -->
                    <div class="bg-light rounded p-2 mb-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted small d-block">Influencer / Head of Family:</span>
                                <h6 class="mb-0 fw-bold text-dark">
                                    <i class="bi bi-person-fill text-primary me-1"></i>
                                    {{ $target->influencer_name ?: 'Family Elder / Representative' }}
                                </h6>
                            </div>
                            <div class="text-end">
                                @if($target->sentiment === 'pakka')
                                    <span class="badge text-white px-2 py-1" style="background-color: #16a34a;"><i class="bi bi-check-circle me-1"></i>Pakka Support</span>
                                @elseif($target->sentiment === 'kacha')
                                    <span class="badge text-dark px-2 py-1" style="background-color: #f59e0b;"><i class="bi bi-question-circle me-1"></i>Kacha / Swing</span>
                                @elseif($target->sentiment === 'mukhalif')
                                    <span class="badge text-white px-2 py-1" style="background-color: #dc2626;"><i class="bi bi-x-circle me-1"></i>Mukhalif</span>
                                @else
                                    <span class="badge text-white px-2 py-1" style="background-color: #64748b;">Unassigned</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Field Notes / Demands -->
                    @if($target->notes)
                        <div class="p-2 rounded mb-3 border-start border-3 border-info bg-info bg-opacity-10">
                            <small class="text-muted d-block fw-semibold" style="font-size: 0.72rem;">WORKER OBSERVATION / COMMUNITY DEMAND:</small>
                            <span class="small text-dark fw-medium">{{ $target->notes }}</span>
                        </div>
                    @endif

                    <!-- Bottom Bar: Contact Links & Mark as Done Action -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2 border-top">
                        <div class="d-flex align-items-center gap-2">
                            @if($target->influencer_phone)
                                <a href="tel:{{ $target->influencer_phone }}" class="btn btn-sm btn-outline-secondary" title="Call Influencer">
                                    <i class="bi bi-telephone-fill text-primary me-1"></i>{{ $target->influencer_phone }}
                                </a>
                                @if(!empty($cleanPhone))
                                    <a href="https://wa.me/{{ $cleanPhone }}?text={{ rawurlencode('Salam ' . ($target->influencer_name ?: 'Sahab') . '! Regarding candidate visit for Block ' . $target->block_code . ', Gharana #' . $target->gharana_no) }}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-success text-white shadow-xs"
                                       title="Chat on WhatsApp">
                                        <i class="bi bi-whatsapp me-1"></i>WhatsApp
                                    </a>
                                @endif
                            @else
                                <span class="text-muted small"><i class="bi bi-telephone-x me-1"></i>No phone registered</span>
                            @endif
                        </div>

                        <!-- Action Form -->
                        <div>
                            <form action="{{ route('surveys.toggle-vip', $target) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $isVip ? 'btn-outline-danger' : 'btn-purple text-white' }}"
                                        style="{{ !$isVip ? 'background-color: #7c3aed;' : '' }}"
                                        title="{{ $isVip ? 'Mark this VIP visit as completed / resolved' : 'Escalate to VIP Visit Request' }}">
                                    @if($isVip)
                                        <i class="bi bi-check2-circle me-1"></i> Mark Daurah Done
                                    @else
                                        <i class="bi bi-star me-1"></i> Escalate to VIP
                                    @endif
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Worker info footer -->
                    <div class="mt-2 pt-1 d-flex align-items-center justify-content-between text-muted" style="font-size: 0.73rem;">
                        <div>
                            <i class="bi bi-person-badge-fill text-primary me-1"></i>
                            Worker: <strong class="text-dark">{{ $target->worker?->name ?? 'Mobile Worker' }}</strong> 
                            @if($target->worker?->phone)
                                ({{ $target->worker->phone }})
                            @endif
                        </div>
                        <div>
                            <i class="bi bi-clock-history me-1"></i>
                            {{ $target->visited_at ? $target->visited_at->diffForHumans() : $target->updated_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card shadow-sm border-0 text-center py-5">
                <div class="card-body">
                    <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                    <h5 class="fw-bold">No Pending VIP Visits or High-Impact Swing Targets</h5>
                    <p class="text-muted small mb-0">All high priority household requests are currently resolved or none match your filter.</p>
                </div>
            </div>
        </div>
    @endforelse
</div>

@if($vipList->hasPages())
    <div class="mt-4">
        {{ $vipList->links() }}
    </div>
@endif
@endsection
