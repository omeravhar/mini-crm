@extends('layouts.crm')

@php
    $statusLabels = $statusLabels ?? [];
    $priorityLabels = [
        'low' => 'נמוכה',
        'medium' => 'בינונית',
        'high' => 'גבוהה',
    ];
    $leadTypeLabels = [
        'new' => 'חדש',
        'returning' => 'חוזר',
    ];
    $followUpScopeLabels = [
        'today' => 'להיום',
        'upcoming' => 'עתידיים',
        'overdue' => 'באיחור',
        'none' => 'ללא מעקב',
    ];
@endphp

@section('pageTitle', 'כל הלידים')
@section('pageSubtitle', 'ניהול, שיוך, המרה ועדכון של כל הלידים במערכת')
@section('pageHeaderClass', 'all-leads-page-header')

@section('pageActions')
    <a class="btn btn-primary" href="{{ route('admin.leads.create') }}">יצירת ליד</a>
@endsection

@section('pageHeaderExtras')
    <form method="GET" action="{{ route('admin.leads.index') }}" class="card shadow-sm border-0 mb-4 admin-leads-filter" data-admin-leads-filter>
        <div class="card-body">
            <div class="row g-3 align-items-end admin-leads-filter__grid">
                <div class="col-sm-12 col-lg-4 col-xl-auto admin-leads-filter__search">
                    <label class="form-label" for="q">חיפוש</label>
                    <input class="form-control" id="q" name="q" value="{{ $filters['q'] }}" placeholder="שם, דוא&quot;ל, חברה, טלפון, התעניינות או קמפיין">
                </div>
                <div class="col-sm-6 col-xl-auto admin-leads-filter__date">
                    <label class="form-label" for="entry_from">מתאריך</label>
                    <input class="form-control" id="entry_from" name="entry_from" type="date" value="{{ $filters['entry_from'] }}">
                </div>
                <div class="col-sm-6 col-xl-auto admin-leads-filter__date">
                    <label class="form-label" for="entry_to">עד תאריך</label>
                    <input class="form-control" id="entry_to" name="entry_to" type="date" value="{{ $filters['entry_to'] }}">
                </div>
                <div class="col-sm-6 col-xl-auto admin-leads-filter__select">
                    <label class="form-label" for="status">סטטוס</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">כל הסטטוסים</option>
                        @foreach ($options['statuses'] as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-auto admin-leads-filter__select">
                    <label class="form-label" for="priority">עדיפות</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">כל העדיפויות</option>
                        @foreach ($options['priorities'] as $priority)
                            <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ $priorityLabels[$priority] ?? $priority }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-auto admin-leads-filter__select">
                    <label class="form-label" for="lead_type">סוג ליד</label>
                    <select class="form-select" id="lead_type" name="lead_type">
                        <option value="">כל סוגי הלידים</option>
                        @foreach ($options['lead_types'] as $leadType)
                            <option value="{{ $leadType }}" @selected($filters['lead_type'] === $leadType)>{{ $leadTypeLabels[$leadType] ?? $leadType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-auto admin-leads-filter__select">
                    <label class="form-label" for="owner_id">אחראי</label>
                    <select class="form-select" id="owner_id" name="owner_id">
                        <option value="">כל האחראים</option>
                        <option value="unassigned" @selected($filters['owner_id'] === 'unassigned')>ללא שיוך</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($filters['owner_id'] === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-auto admin-leads-filter__select">
                    <label class="form-label" for="campaign">קמפיין</label>
                    <select class="form-select" id="campaign" name="campaign">
                        <option value="">כל הקמפיינים</option>
                        @foreach ($campaignOptions as $campaignValue => $campaignLabel)
                            <option value="{{ $campaignValue }}" @selected($filters['campaign'] === $campaignValue)>{{ $campaignLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-auto admin-leads-filter__select">
                    <label class="form-label" for="follow_up_scope">מעקב</label>
                    <select class="form-select" id="follow_up_scope" name="follow_up_scope">
                        <option value="">כל המעקבים</option>
                        @foreach ($followUpScopeLabels as $scope => $label)
                            <option value="{{ $scope }}" @selected($filters['follow_up_scope'] === $scope)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-auto d-grid gap-2 admin-leads-filter__actions">
                    <button class="btn btn-dark" type="submit">סינון</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.leads.index') }}">ניקוי</a>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('content')
    <style>
        .all-leads-page-header {
            background: #f4f6fb;
            margin-bottom: 1rem;
        }

        .all-leads-page .card-stat {
            padding: 0;
        }

        .all-leads-page .card-stat .card-body {
            min-height: 148px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.4rem;
            padding: 1.35rem 1.5rem;
        }

        .all-leads-page .admin-leads-panel {
            padding: 0;
            overflow: visible;
        }

        .all-leads-page .admin-leads-panel .card-header {
            padding: 1.15rem 1.5rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .all-leads-page .admin-leads-panel .card-body {
            padding: 1rem;
            overflow: visible;
        }

        .all-leads-page .admin-leads-panel .lead-table-responsive {
            margin: 0;
        }

        .all-leads-page .admin-leads-panel .lead-management-table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .all-leads-page .admin-leads-panel .lead-management-table thead th {
            background-clip: padding-box;
            box-shadow: inset 0 -1px 0 #e2e8f0;
        }

        @media (min-width: 992px) {
            .all-leads-page-header {
                position: sticky;
                top: 0;
                z-index: 6;
            }

            .all-leads-page-header .admin-leads-filter {
                position: static;
                top: auto;
                z-index: auto;
            }

            .all-leads-page .admin-leads-panel .card-body {
                padding: 0;
            }

            .all-leads-page #adminLeadsContent .lead-table-responsive {
                overflow: visible;
            }

            .all-leads-page #adminLeadsContent .lead-management-table thead {
                position: static;
            }

            .all-leads-page #adminLeadsContent .lead-management-table thead th {
                position: sticky;
                top: var(--admin-leads-filter-offset, 0px);
                z-index: 4;
                background: #fff;
                box-shadow: inset 0 -1px 0 #e2e8f0, 0 2px 8px rgba(15, 23, 42, 0.08);
            }
        }
    </style>

    <div class="all-leads-page">
        <div id="adminLeadsContent">
            @include('leads.partials.admin-content', ['leads' => $leads, 'users' => $users])
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const container = document.getElementById('adminLeadsContent');
            const pageHeader = document.querySelector('[data-page-header-shell].all-leads-page-header');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const pollIntervalMs = 4000;
            let refreshPromise = null;

            if (!container) {
                return;
            }

            const updateStickyOffsets = () => {
                const isDesktop = window.matchMedia('(min-width: 992px)').matches;
                const pageHeaderHeight = isDesktop && pageHeader
                    ? Math.ceil(pageHeader.getBoundingClientRect().height)
                    : 0;

                document.documentElement.style.setProperty('--admin-leads-filter-offset', `${pageHeaderHeight}px`);
            };

            updateStickyOffsets();
            window.addEventListener('resize', updateStickyOffsets);

            const refreshUrl = () => {
                const url = new URL(window.location.href);
                url.searchParams.set('fragment', '1');
                return url.toString();
            };

            const loadContent = async ({ force = false } = {}) => {
                if (!force && document.hidden) {
                    return;
                }

                if (refreshPromise) {
                    return refreshPromise;
                }

                refreshPromise = fetch(refreshUrl(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(async (response) => {
                        if (!response.ok) {
                            throw new Error('Failed to refresh leads.');
                        }

                        const data = await response.json();
                        container.innerHTML = data.html;
                    })
                    .catch((error) => {
                        console.error(error);
                    })
                    .finally(() => {
                        refreshPromise = null;
                    });

                return refreshPromise;
            };

            const submitQuickForm = async (form, errorMessage) => {
                const selects = [...form.querySelectorAll('[data-lead-quick-select]')];
                const payload = Object.fromEntries(new FormData(form).entries());

                selects.forEach((item) => {
                    item.disabled = true;
                });

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(payload),
                    });

                    if (!response.ok) {
                        throw new Error(errorMessage);
                    }

                    await loadContent({ force: true });
                } catch (error) {
                    console.error(error);
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                } finally {
                    selects.forEach((item) => {
                        item.disabled = false;
                    });
                }
            };

            container.addEventListener('change', async (event) => {
                const select = event.target.closest('[data-lead-owner-select]');
                if (!select) {
                    const quickSelect = event.target.closest('[data-lead-quick-select]');
                    if (!quickSelect) {
                        return;
                    }

                    const quickForm = quickSelect.closest('form[data-lead-quick-form]');
                    if (!quickForm) {
                        return;
                    }

                    await submitQuickForm(quickForm, 'Failed to update lead.');

                    return;
                }

                const form = select.closest('form[data-lead-assign-form]');
                if (!form) {
                    return;
                }

                select.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            owner_id: select.value || null,
                        }),
                    });

                    if (!response.ok) {
                        throw new Error('Failed to assign lead.');
                    }

                    await loadContent({ force: true });
                } catch (error) {
                    console.error(error);
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                } finally {
                    select.disabled = false;
                }
            });

            const intervalId = window.setInterval(() => {
                loadContent();
            }, pollIntervalMs);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    loadContent({ force: true });
                }
            });

            window.addEventListener('beforeunload', () => {
                window.clearInterval(intervalId);
            }, { once: true });
        })();
    </script>
@endpush
