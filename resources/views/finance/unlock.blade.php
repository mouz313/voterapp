@extends('layouts.app')

@section('title', 'Security Lock - Finance & Sales Vault')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="col-12 col-sm-8 col-md-6 col-lg-5 col-xl-4">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
            <!-- Security Header Banner -->
            <div class="p-4 text-center text-white" style="background: linear-gradient(135deg, #0b1320 0%, #1e293b 100%);">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3 shadow" 
                     style="width: 70px; height: 70px; background: rgba(234, 179, 8, 0.15); border: 2px solid #eab308;">
                    <i class="bi bi-shield-lock-fill fs-1 text-warning"></i>
                </div>
                <h4 class="fw-bold mb-1 tracking-tight">Finance &amp; Sales Vault</h4>
                <div class="text-warning-emphasis small font-monospace">CONFIDENTIAL &bull; PROTECTED AREA</div>
            </div>

            <!-- Body -->
            <div class="card-body p-4 bg-white">
                <p class="text-muted small text-center mb-4">
                    Please enter the Master Security Password to access Candidate Sales, Party Distribution metrics, and Partner Profit Sharing.
                </p>

                <form method="POST" action="{{ route('finance.unlock.post') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold text-dark">
                            <i class="bi bi-key-fill text-warning me-1"></i> Security Password
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="password" name="password" id="password" 
                                   class="form-control font-mono @error('password') is-invalid @enderror" 
                                   placeholder="Enter security password" 
                                   required autofocus>
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()">
                                <i class="bi bi-eye" id="toggleEyeIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-warning btn-lg fw-bold text-dark shadow-sm py-2.5">
                            <i class="bi bi-unlock-fill me-1"></i> Unlock Vault (رسائی حاصل کریں)
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-link text-muted btn-sm text-decoration-none text-center mt-2">
                            <i class="bi bi-arrow-left me-1"></i> Back to Main Dashboard
                        </a>
                    </div>
                </form>
            </div>

            <!-- Footer Hint -->
            <div class="card-footer bg-light text-center py-2.5 border-top small text-muted">
                <i class="bi bi-info-circle me-1 text-primary"></i> Initial setup password is <code>voter123</code>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility() {
        const input = document.getElementById('password');
        const icon = document.getElementById('toggleEyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>
@endsection
