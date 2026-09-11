@extends('public_parchi.layout')

@section('title', 'ووٹر پرچی تلاش کریں - Online Voter Verification Portal')

@section('content')
<div class="row justify-content-center py-1">
    <div class="col-12" style="max-width: 410px;">
        
        <!-- Official Compact Election Portal Card -->
        <div class="card shadow-sm border-0 bg-white overflow-hidden" 
             style="border-radius: 16px; border: 1.5px solid rgba(0, 77, 37, 0.15) !important; box-shadow: 0 12px 28px -10px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(0, 0, 0, 0.02) !important;">
            
            <!-- Compact Header (Official Election Green Gradient) -->
            <div class="py-3 px-3 text-center text-white position-relative" 
                 style="background: linear-gradient(145deg, #059669 0%, #004d25 100%);">
                
                <!-- Brand Emblem -->
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1.5 shadow-sm" 
                     style="width: 46px; height: 46px; background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.35); font-size: 1.4rem;">
                    🗳️
                </div>

                <h5 class="fw-bold font-urdu mb-0 text-white" style="letter-spacing: -0.3px; font-size: 1.15rem;">آن لائن ووٹر پرچی پورٹل</h5>
                <p class="mb-1.5 text-white opacity-80 small" style="font-size: 0.74rem;">Online Voter Slip &amp; Polling Station Verification</p>

                <span class="badge rounded-pill bg-white text-dark font-urdu py-0.5 px-2" 
                      style="background: rgba(255, 255, 255, 0.9) !important; color: #004d25 !important; font-size: 0.68rem;">
                    🇵🇰 عام انتخابات / بلدیاتی انتخابات
                </span>
            </div>

            <!-- Compact Card Body -->
            <div class="p-3 bg-white">
                
                @if(session('error'))
                    <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 shadow-xs mb-2.5 py-2 px-2.5 border-0 bg-danger-subtle text-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-6 flex-shrink-0"></i>
                        <div class="font-urdu small" style="font-size: 0.78rem;">{{ session('error') }}</div>
                    </div>
                @endif

                @if(isset($errors) && $errors->any())
                    <div class="alert alert-danger rounded-3 shadow-xs mb-2.5 py-2 px-2.5 border-0 bg-danger-subtle text-danger" role="alert">
                        <ul class="mb-0 ps-3 font-urdu small" style="font-size: 0.78rem;">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('public.parchi.search') }}" method="POST" id="parchiSearchForm">
                    @csrf
                    
                    <!-- CNIC Input -->
                    <div class="mb-2.5">
                        <label for="cnic" class="form-label fw-semibold text-dark font-urdu d-flex justify-content-between align-items-center mb-1" style="font-size: 0.82rem;">
                            <span><i class="bi bi-person-vcard text-success me-1"></i> شناختی کارڈ (CNIC):</span>
                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.68rem;">13 Digits</span>
                        </label>
                        <div class="input-group shadow-xs" style="border-radius: 8px; overflow: hidden;">
                            <span class="input-group-text bg-light border-end-0 text-muted px-2.5">
                                <i class="bi bi-credit-card-2-front text-success"></i>
                            </span>
                            <input type="text" 
                                   name="cnic" 
                                   id="cnic" 
                                   class="form-control text-center font-monospace fw-bold fs-6 border-start-0 ps-1 py-2" 
                                   placeholder="35201-1234567-8" 
                                   value="{{ old('cnic') }}" 
                                   maxlength="15" 
                                   dir="ltr" 
                                   required 
                                   autocomplete="off"
                                   style="letter-spacing: 0.5px;">
                        </div>
                        <div class="form-text text-muted font-urdu small mt-0.5" style="font-size: 0.68rem;">
                            مثال: 35201-1234567-8 (بغیر ڈیش کے یا ڈیش کے ساتھ)
                        </div>
                    </div>

                    <!-- Mobile Phone Input -->
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold text-dark font-urdu d-flex justify-content-between align-items-center mb-1" style="font-size: 0.82rem;">
                            <span><i class="bi bi-whatsapp text-success me-1"></i> موبائل / واٹس ایپ نمبر:</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">WhatsApp</span>
                        </label>
                        <div class="input-group shadow-xs" style="border-radius: 8px; overflow: hidden;">
                            <span class="input-group-text bg-light border-end-0 text-muted px-2.5">
                                <i class="bi bi-telephone text-success"></i>
                            </span>
                            <input type="tel" 
                                   name="phone" 
                                   id="phone" 
                                   class="form-control text-center font-monospace fw-bold fs-6 border-start-0 ps-1 py-2" 
                                   placeholder="03001234567" 
                                   value="{{ old('phone') }}" 
                                   maxlength="13" 
                                   dir="ltr" 
                                   required 
                                   autocomplete="off"
                                   style="letter-spacing: 0.5px;">
                        </div>
                        <div class="form-text text-muted font-urdu small mt-0.5" style="font-size: 0.68rem;">
                            درست موبائل نمبر درج کریں تاکہ پرچی ریکارڈ میں محفوظ ہو سکے۔
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mt-3">
                        <button type="submit" 
                                class="btn btn-primary py-2.5 fw-semibold shadow-xs d-flex justify-content-center align-items-center gap-2" 
                                id="btnSubmit" 
                                style="border-radius: 10px; background: linear-gradient(135deg, #059669, #004d25); border: none; font-size: 0.95rem;">
                            <i class="bi bi-search"></i>
                            <span class="font-urdu">پرچی تلاش کریں / Search Slip</span>
                        </button>
                    </div>
                </form>

                <!-- Features Highlight Grid -->
                <div class="row g-2 mt-2.5 pt-2.5 border-top text-center text-secondary">
                    <div class="col-4">
                        <div class="p-1.5 border rounded-2 bg-light shadow-xs">
                            <i class="bi bi-shield-check text-success d-block mb-0.5" style="font-size: 0.9rem;"></i>
                            <span class="font-urdu fw-semibold text-dark d-block" style="font-size: 0.68rem;">مفت سروس</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-1.5 border rounded-2 bg-light shadow-xs">
                            <i class="bi bi-lightning-charge-fill text-warning d-block mb-0.5" style="font-size: 0.9rem;"></i>
                            <span class="font-urdu fw-semibold text-dark d-block" style="font-size: 0.68rem;">فوری تصدیق</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-1.5 border rounded-2 bg-light shadow-xs">
                            <i class="bi bi-whatsapp text-success d-block mb-0.5" style="font-size: 0.9rem;"></i>
                            <span class="font-urdu fw-semibold text-dark d-block" style="font-size: 0.68rem;">ڈیجیٹل پرچی</span>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-2.5 text-muted font-urdu small" style="font-size: 0.68rem;">
                    <span>🔒 آپ کا ڈیٹا الیکشن کمیشن قوانین کے تحت مکمل محفوظ ہے۔</span>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cnicInput = document.getElementById('cnic');
    const phoneInput = document.getElementById('phone');
    const form = document.getElementById('parchiSearchForm');
    const btnSubmit = document.getElementById('btnSubmit');

    // Auto format CNIC as 12345-1234567-8
    cnicInput.addEventListener('input', function(e) {
        let val = e.target.value.replace(/\D/g, '');
        if (val.length > 13) val = val.substring(0, 13);
        
        let formatted = '';
        if (val.length > 5) {
            formatted = val.substring(0, 5) + '-' + val.substring(5, 12);
            if (val.length > 12) {
                formatted += '-' + val.substring(12, 13);
            }
        } else {
            formatted = val;
        }
        e.target.value = formatted;
    });

    // Auto format Phone to clean digits
    phoneInput.addEventListener('input', function(e) {
        let val = e.target.value.replace(/[^\d+]/g, '');
        if (val.length > 12) val = val.substring(0, 12);
        e.target.value = val;
    });

    let isSubmitting = false;
    form.addEventListener('submit', function(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }
        isSubmitting = true;
        btnSubmit.classList.add('disabled');
        btnSubmit.style.pointerEvents = 'none';
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> <span class="font-urdu">تلاش جاری ہے...</span>';
    });
});
</script>
@endpush
