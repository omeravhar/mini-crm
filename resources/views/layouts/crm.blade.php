<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('pageTitle', 'מערכת CRM')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.rtl.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #f4f6fb;
            text-align: right;
        }
        .desktop-sidebar {
            min-height: calc(100vh - 56px);
        }
        .desktop-sidebar-inner {
            position: sticky;
            top: 72px;
        }
        .mobile-sidebar {
            width: min(86vw, 320px);
        }
        .nav-pills .nav-link.active { background: #0d6efd; }
        .card-stat { border: 0; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        .mobile-record-card {
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .mobile-record-card .card-body {
            padding: 1rem;
        }
        .mobile-record-meta {
            font-size: 0.875rem;
            color: #64748b;
        }

        @media (max-width: 991.98px) {
            main.col-12 {
                padding: 1rem;
            }
            .page-actions {
                width: 100%;
            }
            .page-actions > * {
                width: 100%;
            }
            .navbar-user {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    @php
        $roleLabels = [
            'admin' => 'מנהל',
            'editor' => 'עורך',
            'viewer' => 'צופה',
        ];
    @endphp
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid gap-2">
            <div class="d-flex align-items-center gap-2">
                <button
                    class="btn btn-outline-light d-lg-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#mobileSidebar"
                    aria-controls="mobileSidebar"
                >
                    <i class="bi bi-list fs-5"></i>
                </button>
                <a class="navbar-brand fw-semibold mb-0" href="{{ route('dashboard') }}">מערכת CRM</a>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3 text-white navbar-user">
                <div class="small text-start">
                    <div>{{ auth()->user()->name }}</div>
                    <div class="text-white-50">{{ $roleLabels[auth()->user()->role] ?? auth()->user()->role }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-light btn-sm" type="submit">התנתקות</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start mobile-sidebar d-lg-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="mobileSidebarLabel">מערכת CRM</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('partials.sidebar-nav-admin')
        </div>
    </div>

    <div class="container-fluid">
        <div class="row g-0">
            <aside class="col-lg-2 d-none d-lg-block border-end bg-white desktop-sidebar">
                <div class="desktop-sidebar-inner p-3">
                    @include('partials.sidebar-nav-admin')
                </div>
            </aside>

            <main class="col-12 col-lg-10 p-3 p-lg-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                    <div>
                        <h1 class="h3 mb-1">@yield('pageTitle', 'מערכת CRM')</h1>
                        <div class="text-muted">@yield('pageSubtitle')</div>
                    </div>
                    <div class="page-actions d-grid d-sm-flex gap-2">
                        @yield('pageActions')
                    </div>
                </div>

                @include('partials.alerts')

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
