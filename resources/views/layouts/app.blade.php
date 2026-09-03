<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'VoterApp') }}</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Toastr -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Nastaliq+Urdu:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Election Theme Custom CSS -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="app-header navbar navbar-expand-lg fixed-top">
        <div class="container-fluid px-3">
            <button class="btn btn-link sidebar-toggle me-2" type="button" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('dashboard') }}">
                <span class="brand-logo"><i class="bi bi-shield-check"></i></span>
                <span>VoterApp <small class="text-success fw-semibold ms-1 font-monospace" style="font-size: 0.72rem;">ECP Edition</small></span>
            </a>

            <div class="ms-auto d-flex align-items-center gap-2 gap-sm-3">
                <!-- Device Telemetry Indicator -->
                <a href="{{ route('candidates.index') }}" class="badge bg-success-subtle text-success border border-success-subtle d-none d-md-inline-flex align-items-center gap-1 text-decoration-none py-2 px-2.5">
                    <span class="spinner-grow spinner-grow-sm text-success" style="width: 8px; height: 8px;" role="status"></span>
                    <span>{{ \App\Models\CandidateDevice::where('is_revoked', false)->count() }} Active Devices</span>
                </a>

                <div class="dropdown">
                    <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar-initials">{{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}</span>
                        <span class="ms-2 d-none d-sm-inline fw-semibold small">{{ Auth::user()->name ?? 'Administrator' }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><a class="dropdown-item py-2 small" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2 text-success"></i>Dashboard Matrix</a></li>
                        <li><a class="dropdown-item py-2 small" href="{{ route('settings.index') }}"><i class="bi bi-gear me-2 text-secondary"></i>System Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item py-2 small text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="app-body">
        <!-- Sidebar -->
        <aside class="app-sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <span class="brand-logo"><i class="bi bi-shield-check"></i></span>
                <span>VoterApp<small>Offline Voter Verification</small></span>
            </div>
            <nav class="sidebar-nav">
                <p class="sidebar-heading">Main Command</p>
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ Request::is('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span>Operations Matrix</span>
                </a>
                <a href="{{ route('search.index') }}" class="sidebar-link {{ Request::is('search*') ? 'active' : '' }}">
                    <i class="bi bi-search"></i> <span>Voter Search</span>
                </a>

                <p class="sidebar-heading">Constituencies</p>
                <a href="{{ route('national-assemblies.index') }}" class="sidebar-link {{ Request::is('national-assemblies*') ? 'active' : '' }}">
                    <i class="bi bi-flag"></i> <span>National Assembly (NA)</span>
                </a>
                <a href="{{ route('provincial-assemblies.index') }}" class="sidebar-link {{ Request::is('provincial-assemblies*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-2"></i> <span>Provincial (PP/PS/PK)</span>
                </a>

                <p class="sidebar-heading">Delimitations & Areas</p>
                <a href="{{ route('districts.index') }}" class="sidebar-link {{ Request::is('districts*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt"></i> <span>Districts</span>
                </a>
                <a href="{{ route('tehsils.index') }}" class="sidebar-link {{ Request::is('tehsils*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> <span>Tehsils</span>
                </a>
                <a href="{{ route('ucs.index') }}" class="sidebar-link {{ Request::is('ucs*') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2"></i> <span>Union Councils (UCs)</span>
                </a>
                <a href="{{ route('block-codes.index') }}" class="sidebar-link {{ (Request::is('block-codes*') && !Request::is('block-codes/import*')) ? 'active' : '' }}">
                    <i class="bi bi-collection"></i> <span>Census Block Codes</span>
                </a>
                <a href="{{ route('polling-stations.index') }}" class="sidebar-link {{ (Request::is('polling-stations*') && !Request::is('polling-stations/import*')) ? 'active' : '' }}">
                    <i class="bi bi-house-door"></i> <span>Polling Stations</span>
                </a>

                <p class="sidebar-heading">App Distribution</p>
                <a href="{{ route('candidates.index') }}" class="sidebar-link {{ Request::is('candidates*') ? 'active' : '' }}">
                    <i class="bi bi-person-badge"></i> <span>Candidates & Devices</span>
                </a>

                <p class="sidebar-heading">Imports & Processing</p>
                <a href="{{ route('block-codes.import.form') }}" class="sidebar-link {{ Request::is('block-codes/import*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-spreadsheet"></i> <span>Import ECP Delimitation</span>
                </a>
                <a href="{{ route('voters.import.form') }}" class="sidebar-link {{ Request::is('voters/import') ? 'active' : '' }}">
                    <i class="bi bi-filetype-csv"></i> <span>Import Voters List</span>
                </a>
                <a href="{{ route('polling-stations.import.form') }}" class="sidebar-link {{ Request::is('polling-stations/import*') ? 'active' : '' }}">
                    <i class="bi bi-upload"></i> <span>Import Stations</span>
                </a>
                <a href="{{ route('import.image.form') }}" class="sidebar-link {{ Request::is('import/image*') ? 'active' : '' }}">
                    <i class="bi bi-camera"></i> <span>Urdu OCR List Scanner</span>
                </a>

                <p class="sidebar-heading">Developer & APIs</p>
                <a href="{{ route('api.docs') }}" class="sidebar-link {{ Request::is('api-docs*') ? 'active' : '' }}">
                    <i class="bi bi-code-slash"></i> <span>Mobile APIs & Docs</span>
                </a>

                <p class="sidebar-heading">System</p>
                <a href="{{ route('settings.index') }}" class="sidebar-link {{ Request::is('settings*') ? 'active' : '' }}">
                    <i class="bi bi-gear"></i> <span>Settings & Purge</span>
                </a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="app-main">
            <div class="container-fluid py-3 px-3 px-md-4">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show small" role="alert">
                        <i class="bi bi-check-circle me-1"></i> {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="app-footer">
                <div class="container-fluid px-3 d-flex justify-content-between align-items-center">
                    <span>&copy; {{ date('Y') }} VoterApp &bull; Pakistan Election Voter Management Platform</span>
                    <span class="badge bg-light text-muted border font-mono">v2.1.0 ECP Delimitation</span>
                </div>
            </footer>
        </main>
    </div>

    <!-- Full-screen Election Preloader -->
    <div id="pageLoader" class="page-loader d-none">
        <div class="loader-seal">
            <i class="bi bi-shield-check"></i>
        </div>
        <div class="mt-3 fs-6 fw-bold text-white tracking-tight">Processing Electoral Data…</div>
        <div class="text-white-50 small mt-1" id="pageLoaderMsg">Validating records & indexing database. Please wait…</div>
        <div class="loader-progress-bar">
            <div class="bar"></div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="{{ asset('js/custom.js') }}"></script>
    @if (session('toast'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (typeof toastr !== 'undefined') {
                    toastr["{{ session('toast.type', 'info') }}"]("{{ session('toast.message') }}");
                }
            });
        </script>
    @endif
    @stack('scripts')
</body>
</html>
