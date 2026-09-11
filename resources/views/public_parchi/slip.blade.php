@extends('public_parchi.layout')

@section('title', 'ووٹر پرچی - ' . $voter->name)

@section('content')
<div class="row justify-content-center py-2">
    <div class="col-12" style="max-width: 750px;">
        
        <!-- Top Action Buttons (Hidden on Print) -->
        <div class="d-flex justify-content-between align-items-center mb-2 no-print">
            <a href="{{ route('public.parchi') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-arrow-right me-1"></i> نئی تلاش
            </a>
            <a href="{{ $whatsappUrl }}" target="_blank" class="btn btn-sm btn-whatsapp-theme rounded-pill px-3">
                <i class="bi bi-whatsapp me-1"></i> واٹس ایپ پر شیئر
            </a>
        </div>

        <!-- Authentic Horizontal Landscape Voter Parchi Card -->
        <div class="card shadow-sm border bg-white p-3 p-md-4 w-100 position-relative mb-3 parchi-slip" 
             style="border-radius: 16px; border: 2.5px solid #004d26 !important; background: #ffffff;" 
             id="printableParchi">
            
            <div class="row g-3 align-items-stretch">
                
                <!-- Right / Side Column (Candidate & Large Electoral Symbol) -->
                <div class="col-md-4 col-12 d-flex flex-column justify-content-between text-center border-start ps-md-3">
                    @php $firstCandidate = $candidates->first(); @endphp
                    
                    <!-- Candidate Photo & Name -->
                    <div>
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            @if($firstCandidate && $firstCandidate->candidate_image)
                                <img src="{{ asset($firstCandidate->candidate_image) }}" 
                                     alt="{{ $firstCandidate->name }}" 
                                     class="rounded-circle border border-2 border-success shadow-xs" 
                                     style="width: 72px; height: 72px; object-fit: cover;"
                                     title="Candidate Photo">
                            @else
                                <div class="rounded-circle bg-success-subtle text-success border border-success d-flex align-items-center justify-content-center fw-bold fs-3 shadow-xs" 
                                     style="width: 72px; height: 72px;">
                                    {{ $firstCandidate ? strtoupper(substr($firstCandidate->name, 0, 1)) : '🇵🇰' }}
                                </div>
                            @endif
                        </div>

                        @if($firstCandidate)
                            <h6 class="fw-bold text-dark font-urdu mb-0 fs-6">{{ $firstCandidate->name }}</h6>
                            @if($firstCandidate->party_name)
                                <span class="badge bg-light text-dark border font-urdu py-0.5 px-2 mb-2" style="font-size: 0.75rem;">
                                    {{ $firstCandidate->party_name }}
                                </span>
                            @endif
                        @else
                            <h6 class="fw-bold text-success font-urdu mb-2">امیدوار</h6>
                        @endif
                    </div>

                    <!-- PROMINENT LARGE ELECTORAL SYMBOL BOX (انتخابی نشان) -->
                    @if($firstCandidate && $firstCandidate->candidate_symbol)
                        <div class="p-2 rounded-3 my-2 text-center shadow-xs" 
                             style="border: 2px solid #006633 !important; background-color: #ecfdf5 !important;">
                            <span class="badge bg-success text-white font-urdu px-2 py-0.5 rounded-pill mb-1" style="font-size: 0.78rem;">
                                🗳️ انتخابی نشان
                            </span>
                            
                            <div class="d-flex flex-column align-items-center justify-content-center my-0.5">
                                @if($firstCandidate->candidate_symbol_image)
                                    <img src="{{ asset($firstCandidate->candidate_symbol_image) }}" 
                                         alt="{{ $firstCandidate->candidate_symbol }}" 
                                         style="height: 52px; max-width: 80px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));" 
                                         class="mb-0.5">
                                @endif
                                <div class="fw-bold text-success font-urdu mb-0" style="font-size: 2.2rem; line-height: 1;">
                                    {{ $firstCandidate->candidate_symbol }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Slogan / Note -->
                    <div class="text-muted font-urdu small mt-auto" style="font-size: 0.72rem;">
                        @if($firstCandidate && $firstCandidate->party_slogan)
                            <div class="text-success fw-semibold mb-1">"{{ $firstCandidate->party_slogan }}"</div>
                        @endif
                        <span>ووٹ ڈالنے کے لیے اصل شناختی کارڈ ساتھ لانا لازمی ہے۔</span>
                    </div>
                </div>

                <!-- Left / Main Column (Voter Details, Polling Station & Left Party Logo) -->
                <div class="col-md-8 col-12 d-flex flex-column justify-content-between">
                    
                    <!-- Parchi Header with Party Logo on the LEFT -->
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <!-- Right in RTL: Title & UC/Block details -->
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="fw-bold text-success font-urdu mb-0" style="letter-spacing: -0.5px;">انتخابی ووٹر پرچی</h5>
                                @if($firstCandidate && $firstCandidate->party_name)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle font-urdu px-2 py-0.5" style="font-size: 0.72rem;">
                                        {{ $firstCandidate->party_name }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-muted font-urdu small mt-1" style="font-size: 0.75rem;">
                                <span>بلاک کوڈ: <strong class="font-monospace text-dark">{{ $voter->blockCode->code ?? 'N/A' }}</strong></span>
                                <span class="text-muted mx-1">&bull;</span>
                                <span>یو سی: <strong class="text-dark">{{ $voter->uc->name ?? 'N/A' }}</strong></span>
                            </div>
                        </div>

                        <!-- Left in RTL: Party Logo -->
                        <div class="text-start">
                            @if($firstCandidate && $firstCandidate->party_logo)
                                <img src="{{ asset($firstCandidate->party_logo) }}" 
                                     alt="{{ $firstCandidate->party_name ?? 'Party Logo' }}" 
                                     class="rounded border bg-white shadow-xs p-1" 
                                     style="width: 56px; height: 56px; object-fit: contain;"
                                     title="{{ $firstCandidate->party_name ?? 'Party Logo' }}">
                            @else
                                <div class="rounded bg-light border p-1 text-center d-flex align-items-center justify-content-center" 
                                     style="width: 52px; height: 52px;">
                                    <span class="fs-4">🇵🇰</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Highlight Badges: Silsala & Gharana -->
                    <div class="row g-2 mb-2 text-center">
                        <div class="col-6">
                            <div class="p-1.5 rounded-3 bg-light border">
                                <small class="text-muted d-block font-urdu" style="font-size: 0.75rem;">سلسلہ نمبر (Serial No)</small>
                                <strong class="font-monospace fs-4 text-success d-block lh-1 mt-0.5">
                                    #{{ $voter->silsala_no ?: '—' }}
                                </strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-1.5 rounded-3 bg-light border">
                                <small class="text-muted d-block font-urdu" style="font-size: 0.75rem;">گھرانہ نمبر (Gharana No)</small>
                                <strong class="font-monospace fs-4 text-primary d-block lh-1 mt-0.5">
                                    G-{{ $voter->gharana_no ?: '—' }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    <!-- Voter Information Rows -->
                    <div class="bg-light p-2.5 rounded-3 border mb-2 font-urdu" style="font-size: 0.88rem;">
                        <div class="row g-2">
                            <div class="col-sm-6 col-12 d-flex justify-content-between align-items-center border-bottom pb-1">
                                <span class="text-muted small">نام ووٹر:</span>
                                <strong class="text-dark">{{ $voter->name }}</strong>
                            </div>
                            <div class="col-sm-6 col-12 d-flex justify-content-between align-items-center border-bottom pb-1">
                                <span class="text-muted small">ولدیت / شوہر:</span>
                                <strong class="text-dark">{{ $voter->father_name ?: '—' }}</strong>
                            </div>
                            <div class="col-sm-6 col-12 d-flex justify-content-between align-items-center border-bottom pb-1">
                                <span class="text-muted small">شناختی کارڈ:</span>
                                <strong class="font-monospace text-primary" dir="ltr">
                                    {{ \App\Models\Voter::formatCnic($voter->cnic) }}
                                </strong>
                            </div>
                            <div class="col-sm-6 col-12 d-flex justify-content-between align-items-center border-bottom pb-1">
                                <span class="text-muted small">بلاک کوڈ:</span>
                                <strong class="font-monospace text-dark">{{ $voter->blockCode->code ?? 'N/A' }}</strong>
                            </div>
                            <div class="col-sm-6 col-12 d-flex justify-content-between align-items-center">
                                <span class="text-muted small">یونین کونسل:</span>
                                <strong class="text-dark">{{ $voter->uc->name ?? 'N/A' }}</strong>
                            </div>
                            <div class="col-sm-6 col-12 d-flex justify-content-between align-items-center">
                                <span class="text-muted small">عمر:</span>
                                <strong class="text-dark">{{ $voter->age ? $voter->age . ' سال' : '—' }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Polling Station Highlight Box -->
                    <div class="p-2 rounded-3 border border-warning-subtle bg-warning-subtle text-center">
                        <span class="text-muted d-block font-urdu fw-semibold" style="font-size: 0.75rem;">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i> پولنگ اسٹیشن (Polling Station)
                        </span>
                        <div class="fw-bold text-danger font-urdu fs-6 lh-sm mt-0.5">
                            {{ $voter->pollingStation ? $voter->pollingStation->name : 'معلومات الیکشن ڈے پر دستیاب ہوں گی' }}
                        </div>
                        @if($voter->pollingStation && $voter->pollingStation->address)
                            <small class="text-muted d-block font-urdu mt-0.5" style="font-size: 0.72rem;">
                                {{ $voter->pollingStation->address }}
                            </small>
                        @endif
                    </div>

                    <!-- Multi-Candidate Alliance Panel (If more than 1 candidate) -->
                    @if($candidates->count() > 1)
                        <div class="border-top pt-1.5 mt-2 font-urdu">
                            <small class="text-dark fw-bold d-block text-center mb-1" style="font-size: 0.78rem;">🗳️ ہمارا انتخابی پینل:</small>
                            <div class="d-flex flex-wrap justify-content-center gap-1">
                                @foreach($candidates as $cand)
                                    <span class="badge bg-white text-dark border px-2 py-1 font-urdu" style="font-size: 0.75rem;">
                                        <strong>{{ $cand->name }}</strong>
                                        @if($cand->candidate_symbol)
                                            &bull; نشان: <strong class="text-success">{{ $cand->candidate_symbol }}</strong>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

            </div>

        </div>

        <!-- Action Buttons Below Parchi (Hidden on Print) -->
        <div class="d-flex flex-column gap-2 w-100 no-print mb-4">
            <a href="{{ $whatsappUrl }}" target="_blank" 
               class="btn btn-whatsapp-theme py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow-xs">
                <i class="bi bi-whatsapp fs-5"></i>
                <span class="font-urdu">واٹس ایپ پر شیئر کریں</span>
            </a>
            <a href="{{ route('public.parchi') }}" 
               class="btn btn-outline-secondary py-2 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-search"></i>
                <span class="font-urdu">کسی اور ووٹر کی پرچی تلاش کریں</span>
            </a>
        </div>

    </div>
</div>
@endsection
