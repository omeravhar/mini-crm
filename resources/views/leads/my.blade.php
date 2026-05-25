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
    $activeFilterCount = collect([
        $filters['q'] ?? '',
        $filters['entry_from'] ?? '',
        $filters['entry_to'] ?? '',
        $filters['status'] ?? '',
        $filters['priority'] ?? '',
        $filters['lead_type'] ?? '',
        $filters['campaign'] ?? '',
        $filters['follow_up_scope'] ?? '',
    ])->filter(fn ($value) => filled($value))->count();
@endphp

@section('pageTitle', 'הלידים שלי')
@section('pageSubtitle', 'כל הלידים המשויכים אליך כרגע')
@section('pageHeaderClass', 'my-leads-page-header')

@section('pageHeaderExtras')
    <form method="GET" action="{{ route('leads.my') }}" class="card mb-4 admin-leads-filter children-filters-card" data-my-leads-filter>
        <div class="card-body p-0">
            <div class="children-filters-header visually-hidden">
                <div class="children-page-title-wrap">
                    <h2 class="children-page-title">הלידים שלי</h2>
                    <p class="children-page-subtitle">כל הלידים המשויכים אליך כרגע</p>
                </div>
            </div>

            <div class="children-filters-grid admin-leads-filter__grid">
                <div class="filter-col filter-col-search admin-leads-filter__search">
                    <label class="form-label" for="q">חיפוש</label>
                    <input class="form-control" id="q" name="q" value="{{ $filters['q'] }}" placeholder="שם, דוא&quot;ל, חברה, טלפון, התעניינות או קמפיין">
                </div>
                <div class="filter-col filter-col-date-from admin-leads-filter__date">
                    <label class="form-label" for="entry_from">מתאריך</label>
                    <input class="form-control" id="entry_from" name="entry_from" type="date" value="{{ $filters['entry_from'] }}">
                </div>
                <div class="filter-col filter-col-date-to admin-leads-filter__date">
                    <label class="form-label" for="entry_to">עד תאריך</label>
                    <input class="form-control" id="entry_to" name="entry_to" type="date" value="{{ $filters['entry_to'] }}">
                </div>
                <div class="filter-col filter-col-status admin-leads-filter__select">
                    <label class="form-label" for="status">סטטוס</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">כל הסטטוסים</option>
                        <option value="__open__" @selected($filters['status'] === '__open__')>לידים פתוחים</option>
                        @foreach ($options['statuses'] as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-col filter-col-priority admin-leads-filter__select">
                    <label class="form-label" for="priority">עדיפות</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">כל העדיפויות</option>
                        @foreach ($options['priorities'] as $priority)
                            <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ $priorityLabels[$priority] ?? $priority }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-col filter-col-child-type admin-leads-filter__select">
                    <label class="form-label" for="lead_type">סוג ליד</label>
                    <select class="form-select" id="lead_type" name="lead_type">
                        <option value="">כל סוגי הלידים</option>
                        @foreach ($options['lead_types'] as $leadType)
                            <option value="{{ $leadType }}" @selected($filters['lead_type'] === $leadType)>{{ $leadTypeLabels[$leadType] ?? $leadType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-col filter-col-campaign admin-leads-filter__select">
                    <label class="form-label" for="campaign">קמפיין</label>
                    <select class="form-select" id="campaign" name="campaign">
                        <option value="">כל הקמפיינים</option>
                        @foreach ($campaignOptions as $campaignValue => $campaignLabel)
                            <option value="{{ $campaignValue }}" @selected($filters['campaign'] === $campaignValue)>{{ $campaignLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-col filter-col-followup admin-leads-filter__select">
                    <label class="form-label" for="follow_up_scope">מעקב</label>
                    <select class="form-select" id="follow_up_scope" name="follow_up_scope">
                        <option value="">כל המעקבים</option>
                        @foreach ($followUpScopeLabels as $scope => $label)
                            <option value="{{ $scope }}" @selected($filters['follow_up_scope'] === $scope)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="children-filters-footer">
                <div class="filters-footer-right">
                    <button class="btn btn-primary" type="submit">סינון</button>
                    <a class="btn btn-secondary d-inline-flex align-items-center justify-content-center text-decoration-none" href="{{ route('leads.my') }}">ניקוי</a>
                </div>

                <div class="filters-footer-center">
                    <div class="active-filters-box">
                        <span class="active-filters-icon" aria-hidden="true">⌕</span>
                        <div>
                            <strong>מסננים פעילים</strong>
                            <span class="active-filters-count" data-active-filters-count>{{ $activeFilterCount }}</span>
                        </div>
                    </div>
                </div>

                <div class="filters-footer-left">
                    <button class="btn btn-outline" type="submit">הצג תוצאות</button>
                    <button class="btn btn-outline" type="button" disabled aria-disabled="true">ייצוא לאקסל</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('content')
    <style>
        .my-leads-page-header {
            background: #f4f6fb;
            margin-bottom: 0.6rem;
        }

        .my-leads-page-header .page-header-bar {
            margin-bottom: 0.45rem;
            padding-right: 0.3rem;
        }

        .my-leads-page-header .admin-leads-filter.children-filters-card {
            --filters-card-padding: 16px;
            --filters-gap: 10px;
            --filters-control-height: 40px;
            --filters-control-radius: 11px;
            padding: 16px;
            overflow: hidden;
            container-type: normal;
        }

        .my-leads-page-header .admin-leads-filter.children-filters-card .card-body {
            padding: 0 !important;
        }

        .my-leads-page-header .admin-leads-filter__grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .my-leads-page-header .admin-leads-filter .filter-col {
            gap: 4px;
        }

        .my-leads-page-header .admin-leads-filter.children-filters-card .form-label {
            margin-bottom: 0;
            font-size: 12px;
            white-space: normal;
        }

        .my-leads-page-header .admin-leads-filter.children-filters-card .form-control,
        .my-leads-page-header .admin-leads-filter.children-filters-card .form-select {
            min-height: 40px;
            padding: 0 12px;
            font-size: 13px;
            white-space: normal;
        }

        .my-leads-page-header .admin-leads-filter.children-filters-card .btn {
            min-height: 40px;
            padding: 0 15px;
            font-size: 13px;
        }

        .my-leads-page-header .admin-leads-filter.children-filters-card .filter-col-search {
            grid-column: span 2;
            width: auto;
        }

        .my-leads-page-header .admin-leads-filter__date,
        .my-leads-page-header .admin-leads-filter__select {
            min-width: 0;
        }

        .my-leads-page-header .children-filters-footer {
            grid-template-columns: auto minmax(180px, 1fr) auto;
            gap: 10px 14px;
            padding-top: 12px;
        }

        .my-leads-page-header .active-filters-box {
            min-height: 52px;
            min-width: 168px;
            width: min(100%, 220px);
            padding: 10px 14px;
            gap: 8px;
            border-radius: 12px;
        }

        .my-leads-page-header .active-filters-box strong {
            font-size: 12px;
        }

        .my-leads-page-header .active-filters-count {
            margin-top: 1px;
            font-size: 18px;
        }

        .my-leads-page-header .active-filters-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            font-size: 14px;
        }

        .my-leads-page .admin-leads-panel {
            padding: 0;
            overflow: visible;
        }

        .my-leads-page .admin-leads-panel .card-header {
            padding: 1.15rem 1.5rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .my-leads-page .admin-leads-panel .card-body {
            padding: 1rem;
            overflow: visible;
        }

        .my-leads-page .admin-leads-panel .lead-table-responsive {
            margin: 0;
        }

        .my-leads-page .admin-leads-panel .lead-management-table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .my-leads-page .admin-leads-panel .lead-management-table thead th {
            background-clip: padding-box;
            box-shadow: inset 0 -1px 0 #e2e8f0;
        }

        @media (min-width: 992px) {
            .my-leads-page-header .admin-leads-filter {
                position: static;
                top: auto;
                z-index: auto;
            }

            .my-leads-page .admin-leads-panel .card-body {
                padding: 0;
            }

            .my-leads-page #myLeadsContent .lead-table-responsive {
                overflow: visible;
            }
        }

        @media (max-width: 1199.98px) {
            .my-leads-page-header .admin-leads-filter__grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .my-leads-page-header .admin-leads-filter.children-filters-card .filter-col-search {
                grid-column: span 2;
            }
        }

        @media (max-width: 991.98px) {
            .my-leads-page-header .admin-leads-filter__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .my-leads-page-header .admin-leads-filter.children-filters-card .filter-col-search {
                grid-column: span 2;
            }

            .my-leads-page-header .children-filters-footer {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .my-leads-page-header .admin-leads-filter__grid {
                grid-template-columns: 1fr;
            }

            .my-leads-page-header .admin-leads-filter.children-filters-card .filter-col-search {
                grid-column: auto;
            }
        }
    </style>

    <div class="my-leads-page">
        <div id="myLeadsContent">
            @include('leads.partials.my-content', ['leads' => $leads])
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const filterForm = document.querySelector('[data-my-leads-filter]');
            const activeFiltersCount = filterForm?.querySelector('[data-active-filters-count]') ?? null;
            const container = document.getElementById('myLeadsContent');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const pollIntervalMs = 4000;
            let refreshPromise = null;

            if (!container) {
                return;
            }

            const updateActiveFiltersCount = () => {
                if (!filterForm || !activeFiltersCount) {
                    return;
                }

                const count = [...new FormData(filterForm).entries()]
                    .filter(([, value]) => String(value ?? '').trim() !== '')
                    .length;

                activeFiltersCount.textContent = String(count);
            };

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
                            throw new Error('Failed to refresh my leads.');
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

            const requestArchiveReason = (select) => {
                const currentReason = select?.dataset.currentArchiveReason ?? '';
                const reason = window.prompt('יש למלא סיבה לפני העברת ליד לא רלוונטי לארכיון.', currentReason);
                if (reason === null) {
                    select.value = select.dataset.previousValue ?? select.value;
                    return null;
                }
                const trimmedReason = reason.trim();
                if (trimmedReason === '') {
                    window.alert('יש למלא סיבה לפני העברה לארכיון.');
                    select.value = select.dataset.previousValue ?? select.value;
                    return null;
                }
                return trimmedReason;
            };

            const buildQuickPayload = (form, changedSelect) => {
                const payload = Object.fromEntries(new FormData(form).entries());
                if (!(changedSelect instanceof HTMLSelectElement) || !changedSelect.matches('[data-lead-status-select]')) {
                    return payload;
                }
                if (changedSelect.value !== 'lost') {
                    delete payload.archive_reason;
                    return payload;
                }
                const archiveReason = requestArchiveReason(changedSelect);
                if (archiveReason === null) {
                    return null;
                }
                payload.archive_reason = archiveReason;
                return payload;
            };

            const submitQuickForm = async (form, changedSelect) => {
                const selects = [...form.querySelectorAll('[data-lead-quick-select]')];
                const payload = buildQuickPayload(form, changedSelect);
                if (payload === null) {
                    return;
                }

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
                        const responseData = await response.json().catch(() => null);
                        const error = new Error(responseData?.message ?? 'Failed to update lead.');
                        error.status = response.status;
                        throw error;
                    }

                    await loadContent({ force: true });
                } catch (error) {
                    console.error(error);
                    if (error.status === 422) {
                        window.alert(error.message);
                        return;
                    }
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

            updateActiveFiltersCount();
            filterForm?.addEventListener('input', updateActiveFiltersCount);
            filterForm?.addEventListener('change', updateActiveFiltersCount);

            container.addEventListener('change', async (event) => {
                const select = event.target.closest('[data-lead-quick-select]');
                if (!select) {
                    return;
                }

                const form = select.closest('form[data-lead-quick-form]');
                if (!form) {
                    return;
                }

                await submitQuickForm(form, select);
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
