@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <span id="welcomeToast" data-message="Welcome back, {{ Auth::user()->name ?? 'Admin' }}!"></span>

    <div class="page-head">
        <h4 class="page-title">Dashboard</h4>
        <p class="page-subtitle">Overview of your voter platform</p>
    </div>

    <div class="row g-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="text-muted small">Total Voters</div>
                        <div class="stat-value">{{ \App\Models\Voter::count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-geo-alt"></i></div>
                    <div>
                        <div class="text-muted small">Districts</div>
                        <div class="stat-value">{{ \App\Models\District::count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-grid-1x2"></i></div>
                    <div>
                        <div class="text-muted small">UCs</div>
                        <div class="stat-value">{{ \App\Models\UC::count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stat-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-house-door"></i></div>
                    <div>
                        <div class="text-muted small">Polling Stations</div>
                        <div class="stat-value">{{ \App\Models\PollingStation::count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Recent Voters</span>
                    <a href="{{ route('voters.index') }}" class="small">View all</a>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach (\App\Models\Voter::latest()->take(5)->get() as $voter)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="bi bi-person-circle me-2 text-muted"></i>{{ $voter->name }}
                                    <small class="text-muted">· {{ $voter->uc->name ?? '' }}</small></span>
                                <span class="badge bg-primary-subtle text-primary">
                                    {{ $voter->formatted_cnic }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Quick Actions</div>
                <div class="card-body d-grid gap-2">
                    <a href="{{ route('voters.index') }}" class="btn btn-outline-primary text-start">
                        <i class="bi bi-people me-2"></i> Manage Voters
                    </a>
                    <a href="{{ route('search.index') }}" class="btn btn-outline-secondary text-start">
                        <i class="bi bi-search me-2"></i> Voter Search
                    </a>
                    <a href="{{ route('voters.import.form') }}" class="btn btn-outline-info text-start">
                        <i class="bi bi-upload me-2"></i> Import Voters
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
