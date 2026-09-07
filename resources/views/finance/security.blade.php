@extends('layouts.app')

@section('title', 'Vault Security Settings')

@section('content')
    @include('finance.partials.nav')

    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-lock-fill me-1 text-warning"></i> Finance Vault Security &amp; Access Control</h6>
                    <small class="text-muted">Manage the master password protecting all financial and sales records</small>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('finance.security.update') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Security Password <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control font-mono @error('current_password') is-invalid @enderror" placeholder="Enter current password" required>
                            @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <hr class="my-3 text-muted">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Security Password <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" class="form-control font-mono @error('new_password') is-invalid @enderror" placeholder="Minimum 6 characters" required>
                            @error('new_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" name="new_password_confirmation" class="form-control font-mono" placeholder="Confirm new password" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Session Auto-Lock Timeout</label>
                            <div class="input-group">
                                <input type="number" name="session_timeout_minutes" value="{{ old('session_timeout_minutes', $timeoutMinutes) }}" min="5" max="480" class="form-control font-mono" required>
                                <span class="input-group-text bg-light">Minutes</span>
                            </div>
                            <small class="text-muted">Vault will automatically re-lock if inactive for this duration.</small>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('finance.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-shield-check me-1"></i> Update Security Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
