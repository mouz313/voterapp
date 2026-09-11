<!DOCTYPE html>
<html lang="ur" dir="rtl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Online Voter Slip') - {{ config('app.name', 'VoterApp') }}</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Nastaliq+Urdu:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Election Theme Custom CSS -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">

    <style>
        body {
            background-color: #f3f5f8;
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .font-urdu {
            font-family: "Noto Nastaliq Urdu", serif;
            line-height: 2.1;
        }
        .parchi-card {
            border: 2px dashed #a7f3d0;
            background: #ffffff;
            border-radius: 16px;
            position: relative;
        }
        .btn-whatsapp-theme {
            background-color: #25D366;
            color: #ffffff;
            border: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-whatsapp-theme:hover {
            background-color: #1eb954;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .parchi-card {
                border: 2px solid #000000 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Main Container -->
    <main class="flex-grow-1 d-flex flex-column justify-content-center py-2 py-sm-3">
        <div class="container px-3">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-top py-2 text-center text-muted small no-print mt-auto">
        <div class="container">
            <p class="mb-1 fw-semibold text-dark font-urdu">ووٹ ایک قومی امانت ہے۔ اپنے حق رائے دہی کا بروقت اور درست استعمال کریں۔</p>
            <p class="mb-0 text-secondary">&copy; {{ date('Y') }} VoterApp &bull; Pakistan Election Voter Management Platform</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
