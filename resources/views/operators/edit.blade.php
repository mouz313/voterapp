@extends('layouts.app')

@section('title', 'Edit Data Entry Operator')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h4 class="page-title mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Operator [{{ $operator->name }}]</h4>
                    <small class="text-muted">Update profile, change password, or toggle active/suspended status</small>
                </div>
                <a href="{{ route('operators.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back to Operators
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('operators.update', $operator) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-bold">Operator Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $operator->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Login Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $operator->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Contact Phone Number</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $operator->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">New Password (Leave blank to keep current)</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter new password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Account Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', $operator->status) === 'active' ? 'selected' : '' }}>Active (Allow Login)</option>
                                    <option value="suspended" {{ old('status', $operator->status) === 'suspended' ? 'selected' : '' }}>Suspended (Blocked)</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="alert alert-info py-2 px-3 small border-0 bg-info-subtle mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shield-lock text-info fs-5"></i>
                                <div>
                                    <strong>Role:</strong> Data Entry Operator. Delete permission is permanently disabled.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('operators.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i> Update Operator
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
