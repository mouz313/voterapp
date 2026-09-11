@extends('public_parchi.layout')

@section('title', 'براہ کرم انتظار فرمائیں - قطار نمبر #' . $position)

@section('content')
<div class="row justify-content-center py-4">
    <div class="col-lg-6 col-md-8 col-12">
        <div class="card shadow-sm border-0 bg-white p-4 p-md-5 text-center" style="border-radius: 16px; border: 2px solid #006633 !important;">
            
            <!-- Animated Icon / Pulse -->
            <div class="mb-4 position-relative d-inline-block mx-auto">
                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center mx-auto shadow-sm" 
                     style="width: 88px; height: 88px; font-size: 2.5rem; animation: pulse 2s infinite;">
                    ⏳
                </div>
            </div>

            <!-- Title & Status -->
            <h4 class="fw-bold text-dark font-urdu mb-2">آپ کی درخواست قطار میں ہے</h4>
            <p class="text-muted font-urdu small mb-4">
                انتخابی دن پر زیادہ رش کی وجہ سے سرور آپ کی درخواست کو بغیر کسی نقصان کے محفوظ طریقے سے پروسیس کر رہا ہے۔
            </p>

            <!-- Position Highlight Box -->
            <div class="p-3 rounded-4 bg-light border mb-4 shadow-xs">
                <div class="text-muted font-urdu small mb-1">قطار میں آپ کا نمبر (Queue Position)</div>
                <div class="display-5 fw-bold text-success font-monospace" id="queuePosDisplay">
                    #<span id="queuePosition">{{ $position }}</span>
                </div>
                <small class="text-muted font-urdu d-block mt-1">
                    متوقع وقت: <strong id="waitSecs" class="text-dark">{{ $estimatedWait }}</strong> سیکنڈ
                </small>
            </div>

            <!-- Progress Bar -->
            <div class="progress mb-3" style="height: 10px; border-radius: 6px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                     role="progressbar" 
                     id="queueProgressBar"
                     style="width: {{ max(10, 100 - ($position * 15)) }}%;"></div>
            </div>

            <!-- Alert Message -->
            <div class="alert alert-warning border-0 bg-warning-subtle text-dark font-urdu small text-center mb-4 py-2 px-3 rounded-3" id="statusMessage">
                <i class="bi bi-info-circle-fill me-1 text-warning"></i>
                <span>براہ کرم صفحہ ریفریش یا بند نہ کریں، آپ کی پرچی خودکار طور پر ظاہر ہو جائے گی۔</span>
            </div>

            <!-- Fallback Cancel / Retry -->
            <div class="d-flex justify-content-center gap-2">
                <a href="{{ route('public.parchi') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 font-urdu">
                    <i class="bi bi-arrow-right me-1"></i> منسوخ کریں / نئی تلاش
                </a>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ticketId = "{{ $ticketId }}";
    const statusUrl = "{{ route('public.parchi.queue.status') }}";
    const csrfToken = "{{ csrf_token() }}";
    const posElem = document.getElementById('queuePosition');
    const waitElem = document.getElementById('waitSecs');
    const barElem = document.getElementById('queueProgressBar');
    const msgElem = document.getElementById('statusMessage');

    let pollInterval = {{ (int) config('parchi.poll_interval_ms', 1500) }};
    let isChecking = false;

    async function checkQueueStatus() {
        if (isChecking) return;
        isChecking = true;

        try {
            const response = await fetch(statusUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ ticket_id: ticketId })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();

            if (data.status === 'ready' && data.redirect_url) {
                msgElem.className = 'alert alert-success border-0 bg-success-subtle text-success font-urdu small text-center mb-4 py-2 px-3 rounded-3';
                msgElem.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> پرچی تیار ہے! صفحہ لوڈ ہو رہا ہے...';
                barElem.style.width = '100%';
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 400);
                return;
            }

            if (data.status === 'waiting') {
                if (posElem && data.position) {
                    posElem.innerText = data.position;
                    const pct = Math.min(95, Math.max(15, 100 - (data.position * 15)));
                    barElem.style.width = pct + '%';
                }
                if (waitElem && data.estimated_wait) {
                    waitElem.innerText = data.estimated_wait;
                }
                setTimeout(checkQueueStatus, pollInterval);
            } else if (data.status === 'error') {
                msgElem.className = 'alert alert-danger border-0 bg-danger-subtle text-danger font-urdu small text-center mb-4 py-2 px-3 rounded-3';
                msgElem.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> ' + (data.message || 'معذرت! تلاش مکمل نہیں ہو سکی۔');
            } else {
                setTimeout(checkQueueStatus, pollInterval);
            }
        } catch (err) {
            console.error('Queue poll error:', err);
            setTimeout(checkQueueStatus, pollInterval * 1.5);
        } finally {
            isChecking = false;
        }
    }

    // Start auto-polling
    setTimeout(checkQueueStatus, 1000);
});
</script>

<style>
@keyframes pulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 102, 51, 0.4); }
    70% { transform: scale(1.05); box-shadow: 0 0 0 14px rgba(0, 102, 51, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 102, 51, 0); }
}
</style>
@endpush
