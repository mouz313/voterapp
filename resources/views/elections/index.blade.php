@extends('layouts.app')

@section('title', 'Elections')

@section('content')
    <div class="mb-3">
        <h4 class="page-title mb-0">Elections</h4>
        <small class="text-muted">Create and manage voting events</small>
    </div>

    <div class="card shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-ballot fs-1 d-block mb-3 text-primary"></i>
            <h5>No elections yet</h5>
            <p>Election management will appear here.</p>
            <button class="btn btn-primary" data-toast="info"
                    data-toast-message="Election creation is coming soon.">
                <i class="bi bi-plus-lg me-1"></i> New Election
            </button>
        </div>
    </div>
@endsection
