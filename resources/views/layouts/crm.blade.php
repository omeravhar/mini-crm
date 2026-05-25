<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('pageTitle', config('app.name', 'EasyCRM'))</title>
    <link rel="icon" href="{{ asset('assets/img/easycrm-mark.svg') }}" type="image/svg+xml">
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
        .app-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            min-width: 0;
            color: inherit;
            line-height: 1.1;
            text-decoration: none;
        }
        .navbar-brand.app-brand {
            margin-inline-end: 0;
            max-width: min(58vw, 28rem);
        }
        .app-brand__logo {
            flex: 0 0 auto;
            height: 2.35rem;
            width: auto;
            max-width: min(44vw, 16rem);
            object-fit: contain;
        }
        .offcanvas-title.app-brand .app-brand__logo {
            height: 2.15rem;
            width: auto;
            max-width: min(58vw, 14rem);
        }
        .app-brand__name {
            flex: 0 0 auto;
            white-space: nowrap;
        }
        .app-brand__slogan {
            display: inline-block;
            max-width: 14rem;
            overflow: hidden;
            color: rgba(255, 255, 255, 0.72);
            direction: ltr;
            font-size: 0.78rem;
            font-weight: 500;
            text-overflow: ellipsis;
            unicode-bidi: isolate;
            white-space: nowrap;
        }
        .app-brand--dark .app-brand__slogan {
            color: #6c757d;
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
        .admin-leads-filter {
            background: #fff;
            container-type: inline-size;
        }
        .admin-leads-filter .card-body {
            padding: 0.9rem 1rem;
        }
        .admin-leads-filter__grid {
            --bs-gutter-x: 0.7rem;
            --bs-gutter-y: 0.65rem;
        }
        .admin-leads-filter .form-label {
            margin-bottom: 0.35rem;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        .admin-leads-filter .form-control,
        .admin-leads-filter .form-select {
            min-height: 2.2rem;
            padding-block: 0.32rem;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        .admin-leads-filter .btn {
            min-height: 2.2rem;
            padding: 0.32rem 0.58rem;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        .admin-leads-filter__search {
            width: 100%;
        }
        .admin-leads-filter__select {
            min-width: 8.25rem;
        }
        .admin-leads-filter__date {
            min-width: 10.25rem;
        }
        .admin-leads-filter__actions {
            min-width: 4.1rem;
        }
        .lead-management-table .lead-owner-cell {
            width: 9.5rem;
            min-width: 9.5rem;
        }
        .lead-management-table .lead-status-cell {
            width: 7.5rem;
            min-width: 7.5rem;
        }
        .lead-management-table .lead-priority-cell {
            width: 6.75rem;
            min-width: 6.75rem;
        }
        .lead-management-table .lead-actions-cell {
            width: 11rem;
            min-width: 11rem;
        }
        .lead-management-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: #fff;
            box-shadow: inset 0 -1px 0 #dee2e6, 0 2px 8px rgba(15, 23, 42, 0.08);
        }
        .lead-compact-select {
            width: 100%;
            max-width: 100%;
            padding-block: 0.2rem;
            padding-inline-start: 1.9rem;
            padding-inline-end: 0.45rem;
            font-size: 0.84rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .lead-actions-group {
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 0.35rem;
        }
        .table-action-group {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 0.45rem;
        }
        .table-action-group--mobile {
            width: 100%;
            justify-content: flex-start;
        }
        .icon-action-group {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 0.45rem;
        }
        .icon-action-group form {
            margin: 0;
        }
        .icon-action-group .btn-outline-primary,
        .icon-action-group .btn-outline-danger,
        .icon-action-group .btn-outline-success {
            width: 2.15rem;
            height: 2.15rem;
            padding: 0 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: visible;
            white-space: nowrap;
            font-size: 1rem;
            line-height: 1;
            border-radius: 0.7rem;
            background-color: #fff;
            box-shadow: none;
            transition: border-color 0.14s ease, background-color 0.14s ease, box-shadow 0.14s ease;
        }
        .icon-action-group .btn-outline-primary.w-100,
        .icon-action-group .btn-outline-danger.w-100,
        .icon-action-group .btn-outline-success.w-100 {
            width: 2.15rem !important;
        }
        .icon-action-group .btn-outline-primary {
            color: #2563eb;
            border-color: #bfdbfe;
        }
        .icon-action-group .btn-outline-danger {
            color: #dc2626;
            border-color: #fecaca;
        }
        .icon-action-group .btn-outline-success {
            color: #059669;
            border-color: #bbf7d0;
        }
        .icon-action-group .btn-outline-primary:hover,
        .icon-action-group .btn-outline-danger:hover,
        .icon-action-group .btn-outline-success:hover {
            background-color: #f8fafc;
        }
        .icon-action-group .btn-outline-primary:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 16px -14px rgba(15, 23, 42, 0.2);
        }
        .icon-action-group .btn-outline-danger:hover {
            border-color: #fecaca;
            box-shadow: 0 6px 16px -14px rgba(15, 23, 42, 0.2);
        }
        .icon-action-group .btn-outline-success:hover {
            border-color: #bbf7d0;
            box-shadow: 0 6px 16px -14px rgba(15, 23, 42, 0.2);
        }
        .icon-action-group .btn-outline-primary:disabled,
        .icon-action-group .btn-outline-danger:disabled,
        .icon-action-group .btn-outline-success:disabled {
            opacity: 0.45;
            box-shadow: none;
        }
        .icon-action-icon {
            font-size: 1rem;
            line-height: 1;
            pointer-events: none;
        }
        .icon-action-icon--success {
            font-size: 1.08rem;
        }
        .icon-action-group .btn-outline-primary:focus-visible,
        .icon-action-group .btn-outline-danger:focus-visible {
            outline: 3px solid rgba(37, 99, 235, 0.18);
            outline-offset: 2px;
        }
        .icon-action-group .btn-outline-success:focus-visible {
            outline: 3px solid rgba(5, 150, 105, 0.18);
            outline-offset: 2px;
        }
        .icon-action-group .btn-outline-primary::after,
        .icon-action-group .btn-outline-danger::after,
        .icon-action-group .btn-outline-success::after {
            content: attr(aria-label);
            position: absolute;
            inset-inline-start: 50%;
            top: calc(100% + 0.4rem);
            transform: translateX(-50%);
            padding: 0.24rem 0.48rem;
            border-radius: 0.55rem;
            background: rgba(15, 23, 42, 0.9);
            color: #fff;
            font-size: 0.72rem;
            line-height: 1.2;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            box-shadow: 0 8px 18px -18px rgba(15, 23, 42, 0.55);
            transition: opacity 0.12s ease, visibility 0.12s ease;
            z-index: 20;
        }
        .icon-action-group .btn-outline-primary:hover::after,
        .icon-action-group .btn-outline-primary:focus-visible::after,
        .icon-action-group .btn-outline-danger:hover::after,
        .icon-action-group .btn-outline-danger:focus-visible::after,
        .icon-action-group .btn-outline-success:hover::after,
        .icon-action-group .btn-outline-success:focus-visible::after {
            opacity: 1;
            visibility: visible;
        }
        .lead-actions-cell .btn {
            padding: 0.2rem 0.55rem;
            font-size: 0.76rem;
            white-space: nowrap;
        }
        .lead-status-select {
            font-weight: 600;
            transition: background-color .2s ease, border-color .2s ease, color .2s ease, box-shadow .2s ease;
        }
        .lead-status-select option {
            color: #0f172a;
            background: #fff;
        }
        .lead-status-select--new,
        .lead-status-select--secondary {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #475569;
        }
        .lead-status-select--contacted,
        .lead-status-select--primary {
            background: #dbeafe;
            border-color: #93c5fd;
            color: #1d4ed8;
        }
        .lead-status-select--qualified,
        .lead-status-select--info {
            background: #e0f2fe;
            border-color: #7dd3fc;
            color: #0369a1;
        }
        .lead-status-select--proposal,
        .lead-status-select--warning {
            background: #fef3c7;
            border-color: #fcd34d;
            color: #92400e;
        }
        .lead-status-select--won,
        .lead-status-select--success {
            background: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }
        .lead-status-select--lost,
        .lead-status-select--danger {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #b91c1c;
        }
        .lead-status-select--dark {
            background: #e2e8f0;
            border-color: #94a3b8;
            color: #0f172a;
        }
        .lead-status-select--default {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #334155;
        }
        .lead-assignment-toast-container {
            position: fixed;
            left: 1rem;
            bottom: 1rem;
            z-index: 1090;
            display: grid;
            gap: 0.75rem;
            width: min(380px, calc(100vw - 2rem));
            pointer-events: none;
        }
        .lead-assignment-toast {
            pointer-events: auto;
            background: #fff;
            border: 1px solid #dbeafe;
            border-inline-start: 4px solid #0d6efd;
            border-radius: 0.75rem;
            box-shadow: 0 16px 42px rgba(15, 23, 42, 0.18);
            padding: 0.95rem;
        }
        .login-open-leads-toast {
            border-color: #d1fae5;
            border-inline-start-color: #10b981;
        }
        .login-open-leads-toast__count {
            color: #047857;
            font-weight: 800;
        }
        .lead-assignment-toast__title {
            font-weight: 700;
            color: #0f172a;
        }
        .lead-assignment-toast__lead {
            font-weight: 700;
            color: #0d6efd;
        }
        .lead-assignment-toast__details {
            margin-top: 0.55rem;
            display: grid;
            gap: 0.25rem;
            color: #475569;
            font-size: 0.875rem;
        }
        .lead-assignment-toast__actions {
            margin-top: 0.75rem;
            display: flex;
            justify-content: flex-end;
        }
        .lead-assignment-toast__close {
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: 1.1rem;
            line-height: 1;
            padding: 0.1rem 0.25rem;
        }

        @media (min-width: 992px) {
            .admin-leads-filter {
                position: sticky;
                top: 0;
                z-index: 5;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.10);
            }
            #adminLeadsContent .lead-table-responsive {
                overflow: visible;
            }
            #adminLeadsContent .lead-management-table thead {
                position: sticky;
                top: var(--admin-leads-filter-offset, 0px);
                z-index: 4;
                background: #fff;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
            }
            #adminLeadsContent .lead-management-table thead th {
                position: static;
            }
        }

        @media (min-width: 1200px) {
            .admin-leads-filter__search {
                width: min(16rem, 100%);
            }
        }

        @container (min-width: 1220px) {
            .admin-leads-filter__grid {
                --bs-gutter-x: 0.55rem;
                flex-wrap: nowrap;
            }
            .admin-leads-filter__search {
                flex: 1.35 1 0;
                min-width: 11.5rem;
                width: auto;
            }
            .admin-leads-filter__date {
                flex: 1 1 0;
                min-width: 8.6rem;
            }
            .admin-leads-filter__select {
                flex: 1 1 0;
                min-width: 7.3rem;
            }
            .admin-leads-filter__actions {
                flex: 0 0 4.5rem;
                min-width: 4.5rem;
            }
            .admin-leads-filter .form-control,
            .admin-leads-filter .form-select,
            .admin-leads-filter .btn {
                font-size: 0.76rem;
            }
        }

        @media (max-width: 991.98px) {
            .lead-assignment-toast-container {
                right: 1rem;
                left: 1rem;
                width: auto;
            }
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
            .app-brand__slogan {
                max-width: 8.5rem;
                font-size: 0.7rem;
            }
        }
    </style>
    @include('partials.global-css')
</head>
<body>
    @php
        $appName = config('app.name', 'EasyCRM');
        $appSlogan = config('app.slogan', 'Exactly What You Need');
        $appLogo = asset('assets/img/easycrm-logo.svg');
        $roleLabels = [
            'admin' => 'מנהל',
            'editor' => 'עורך',
            'viewer' => 'צופה',
        ];
    @endphp
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid gap-2">
            <div class="d-flex align-items-center gap-2 order-2">
                <button
                    class="btn btn-outline-light d-lg-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#mobileSidebar"
                    aria-controls="mobileSidebar"
                >
                    <i class="bi bi-list fs-5"></i>
                </button>
                <a class="navbar-brand app-brand fw-semibold mb-0" href="{{ route('dashboard') }}" aria-label="{{ $appName }}">
                    <img class="app-brand__logo" src="{{ $appLogo }}" alt="{{ $appName }}">
                </a>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3 text-white navbar-user order-1">
                <div class="small text-end">
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
            <h5 class="offcanvas-title app-brand app-brand--dark mb-0" id="mobileSidebarLabel">
                <img class="app-brand__logo" src="{{ $appLogo }}" alt="{{ $appName }}">
            </h5>
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
                <div class="page-header-shell @yield('pageHeaderClass')" data-page-header-shell>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 page-header-bar">
                        <div>
                            <h1 class="h3 mb-1">@yield('pageTitle', $appName)</h1>
                            <div class="text-muted">@yield('pageSubtitle')</div>
                        </div>
                        <div class="page-actions d-grid d-sm-flex gap-2">
                            @yield('pageActions')
                        </div>
                    </div>
                    @yield('pageHeaderExtras')
                </div>

                @include('partials.alerts')

                @yield('content')
            </main>
        </div>
    </div>

    @if ($loginOpenLeadsPopup = session('login_open_leads_popup'))
        <div class="lead-assignment-toast-container" data-lead-assignment-toast-container>
            <div class="lead-assignment-toast login-open-leads-toast" data-login-open-leads-toast>
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="lead-assignment-toast__title">שלום {{ $loginOpenLeadsPopup['name'] }}</div>
                        <div class="lead-assignment-toast__details">
                            <div>
                                יש לך
                                <span class="login-open-leads-toast__count">{{ $loginOpenLeadsPopup['open_leads_count'] }}</span>
                                לידים פתוחים שממתינים לטיפולך
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="lead-assignment-toast__close"
                        data-login-open-leads-toast-close
                        aria-label="סגירה"
                    >×</button>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script>
        (() => {
            const themeClasses = [
                'lead-status-select--new',
                'lead-status-select--contacted',
                'lead-status-select--qualified',
                'lead-status-select--proposal',
                'lead-status-select--won',
                'lead-status-select--lost',
                'lead-status-select--secondary',
                'lead-status-select--primary',
                'lead-status-select--info',
                'lead-status-select--warning',
                'lead-status-select--success',
                'lead-status-select--danger',
                'lead-status-select--dark',
                'lead-status-select--default',
            ];

            const applyLeadStatusTheme = (select) => {
                if (!(select instanceof HTMLSelectElement)) {
                    return;
                }

                themeClasses.forEach((className) => {
                    select.classList.remove(className);
                });

                const optionThemeClass = select.selectedOptions?.[0]?.dataset.statusTheme?.trim();
                const normalizedValue = (select.value || '').toLowerCase().trim();
                const themeClass = optionThemeClass && optionThemeClass.startsWith('lead-status-select--')
                    ? optionThemeClass
                    : (themeClasses.includes(`lead-status-select--${normalizedValue}`)
                        ? `lead-status-select--${normalizedValue}`
                        : 'lead-status-select--default');

                select.classList.add(themeClass);
            };

            document.querySelectorAll('[data-lead-status-select]').forEach(applyLeadStatusTheme);

            document.addEventListener('change', (event) => {
                const select = event.target.closest('[data-lead-status-select]');
                if (!select) {
                    return;
                }

                applyLeadStatusTheme(select);
            });
        })();
    </script>
    <script>
        (() => {
            const toast = document.querySelector('[data-login-open-leads-toast]');

            if (!toast) {
                return;
            }

            const closeToast = () => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(8px)';
                window.setTimeout(() => toast.remove(), 180);
            };

            toast.style.transition = 'opacity .18s ease, transform .18s ease';
            toast.querySelector('[data-login-open-leads-toast-close]')?.addEventListener('click', closeToast);
            window.setTimeout(closeToast, 12000);
        })();
    </script>
    @auth
        <script>
            (() => {
                const endpoint = @json(route('notifications.leadAssignmentPopups'));
                const pollIntervalMs = 7000;
                let isLoading = false;

                const ensureContainer = () => {
                    let container = document.querySelector('[data-lead-assignment-toast-container]');

                    if (container) {
                        return container;
                    }

                    container = document.createElement('div');
                    container.className = 'lead-assignment-toast-container';
                    container.setAttribute('data-lead-assignment-toast-container', '');
                    document.body.appendChild(container);

                    return container;
                };

                const addDetail = (parent, label, value) => {
                    if (!value) {
                        return;
                    }

                    const item = document.createElement('div');
                    item.textContent = `${label}: ${value}`;
                    parent.appendChild(item);
                };

                const removeToast = (toast) => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(8px)';
                    window.setTimeout(() => toast.remove(), 180);
                };

                const showToast = (notification) => {
                    const container = ensureContainer();
                    const toast = document.createElement('div');
                    toast.className = 'lead-assignment-toast';
                    toast.style.transition = 'opacity .18s ease, transform .18s ease';

                    const header = document.createElement('div');
                    header.className = 'd-flex justify-content-between align-items-start gap-2';

                    const title = document.createElement('div');
                    title.className = 'lead-assignment-toast__title';
                    title.textContent = 'ליד חדש שויך אליך';

                    const closeButton = document.createElement('button');
                    closeButton.type = 'button';
                    closeButton.className = 'lead-assignment-toast__close';
                    closeButton.setAttribute('aria-label', 'סגירה');
                    closeButton.textContent = '×';
                    closeButton.addEventListener('click', () => removeToast(toast));

                    header.append(title, closeButton);
                    toast.appendChild(header);

                    const leadName = document.createElement('div');
                    leadName.className = 'lead-assignment-toast__lead mt-2';
                    leadName.textContent = notification.lead_name || 'ליד ללא שם';
                    toast.appendChild(leadName);

                    const details = document.createElement('div');
                    details.className = 'lead-assignment-toast__details';
                    addDetail(details, 'חברה', notification.company);
                    addDetail(details, 'טלפון', notification.lead_phone);
                    addDetail(details, 'דוא"ל', notification.lead_email);
                    addDetail(details, 'התעניינות', notification.interested_in);
                    addDetail(details, 'קמפיין', notification.campaign);
                    addDetail(details, 'כניסה', notification.entry_at_display);
                    toast.appendChild(details);

                    if (notification.lead_url) {
                        const actions = document.createElement('div');
                        actions.className = 'lead-assignment-toast__actions';

                        const link = document.createElement('a');
                        link.className = 'btn btn-sm btn-primary';
                        link.href = notification.lead_url;
                        link.textContent = 'פתיחת הליד';
                        actions.appendChild(link);
                        toast.appendChild(actions);
                    }

                    container.prepend(toast);
                    window.setTimeout(() => removeToast(toast), 12000);
                };

                const loadAssignmentPopups = async ({ force = false } = {}) => {
                    if (isLoading || (!force && document.hidden)) {
                        return;
                    }

                    isLoading = true;

                    try {
                        const response = await fetch(endpoint, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const data = await response.json();
                        (data.notifications || []).forEach(showToast);
                    } catch (error) {
                        console.error(error);
                    } finally {
                        isLoading = false;
                    }
                };

                window.setTimeout(() => loadAssignmentPopups({ force: true }), 1000);
                window.setInterval(loadAssignmentPopups, pollIntervalMs);
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        loadAssignmentPopups({ force: true });
                    }
                });
            })();
        </script>
    @endauth
    @stack('scripts')
</body>
</html>
