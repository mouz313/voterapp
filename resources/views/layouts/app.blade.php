<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Toastr -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />
    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Urdu font (proper Nastaliq for Urdu voter/location data) -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;500;600;700&family=Noto+Naskh+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="app-header navbar navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <button class="btn btn-link sidebar-toggle" type="button" id="sidebarToggle">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('dashboard') }}">
                <span class="brand-logo"><i class="bi bi-shield-check"></i></span> VoterApp
            </a>

            <div class="ms-auto d-flex align-items-center gap-3">
                <button class="btn btn-link position-relative" title="Notifications">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        3<span class="visually-hidden">unread</span>
                    </span>
                </button>

                <div class="dropdown">
                    <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar-initials">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                        <span class="ms-2 d-none d-sm-inline">{{ Auth::user()->name ?? 'User' }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
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
                <p class="sidebar-heading">Main</p>
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ Request::is('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
                </a>
                <a href="{{ route('search.index') }}" class="sidebar-link {{ Request::is('search*') ? 'active' : '' }}">
                    <i class="bi bi-search"></i> <span>Voter Search</span>
                </a>

                <p class="sidebar-heading">Locations</p>
                <a href="{{ route('districts.index') }}" class="sidebar-link {{ Request::is('districts*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt"></i> <span>Districts</span>
                </a>
                <a href="{{ route('tehsils.index') }}" class="sidebar-link {{ Request::is('tehsils*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> <span>Tehsils</span>
                </a>
                <a href="{{ route('ucs.index') }}" class="sidebar-link {{ Request::is('ucs*') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2"></i> <span>UCs</span>
                </a>
                <a href="{{ route('block-codes.index') }}" class="sidebar-link {{ Request::is('block-codes*') ? 'active' : '' }}">
                    <i class="bi bi-collection"></i> <span>Block Codes</span>
                </a>
                <a href="{{ route('polling-stations.index') }}" class="sidebar-link {{ (Request::is('polling-stations*') && !Request::is('polling-stations/import*')) ? 'active' : '' }}">
                    <i class="bi bi-house-door"></i> <span>Polling Stations</span>
                </a>
                <a href="{{ route('polling-stations.import.form') }}" class="sidebar-link {{ Request::is('polling-stations/import*') ? 'active' : '' }}">
                    <i class="bi bi-upload"></i> <span>Import Stations</span>
                </a>

                <p class="sidebar-heading">Voters</p>
                <a href="{{ route('voters.index') }}" class="sidebar-link {{ Request::is('voters*') && !Request::is('voters/import') && !Request::is('import/pdf*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> <span>Voters</span>
                </a>
                <a href="{{ route('voters.import.form') }}" class="sidebar-link {{ Request::is('voters/import') ? 'active' : '' }}">
                    <i class="bi bi-upload"></i> <span>Import Voters</span>
                </a>
                <a href="{{ route('import.pdf.index') }}" class="sidebar-link {{ Request::is('import/pdf*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-pdf"></i> <span>Import from PDF</span>
                </a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="app-main">
            <div class="container-fluid py-4">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @yield('content')
            </div>

            <!-- Footer -->
            <footer class="app-footer">
                <div class="container-fluid">
                    <span>&copy; {{ date('Y') }} VoterApp. Built with Laravel.</span>
                    <span class="ms-auto">v1.0.0</span>
                </div>
            </footer>
        </main>
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
    @yield('scripts')
</body>
</html>
