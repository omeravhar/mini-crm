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

    <form method="POST" action="{{ route('admin.leads.bulk-assign') }}" class="card shadow-sm border-0 admin-leads-bulk-bar" data-bulk-assign-form>
        @csrf
        <div class="card-body">
            <div class="admin-leads-bulk-bar__grid">
                <div class="admin-leads-bulk-bar__summary">
                    <div class="fw-semibold">שיוך מרובה</div>
                    <div class="small text-muted">
                        נבחרו <span data-bulk-selected-count>0</span> לידים
                    </div>
                </div>
                <div class="admin-leads-bulk-bar__owner">
                    <label class="form-label mb-1" for="bulk_owner_id">משתמש יעד</label>
                    <select class="form-select" id="bulk_owner_id" name="owner_id" data-bulk-owner-select>
                        <option value="">בחר משתמש</option>
                        <option value="unassigned">ללא שיוך</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="admin-leads-bulk-bar__actions">
                    <button class="btn btn-primary" type="submit" data-bulk-assign-submit disabled>שייך נבחרים</button>
                    <button class="btn btn-outline-secondary" type="button" data-bulk-clear-selection disabled>נקה בחירה</button>
                </div>
            </div>
            <div class="admin-leads-bulk-bar__feedback small text-muted mt-2" data-bulk-feedback>
                בחר לידים מהטבלה ואז בחר משתמש אחד לשיוך.
            </div>
            <div data-bulk-selected-inputs></div>
        </div>
    </form>
@endsection

@section('content')
    <style>
        .all-leads-page-header {
            background: #f4f6fb;
            margin-bottom: 1rem;
        }

        .all-leads-page-header .page-header-bar {
            margin-bottom: 0.75rem;
            padding-right: 0.6rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar {
            margin-bottom: 0;
            padding: 0;
            border-radius: 16px;
        }

        .all-leads-page-header .admin-leads-bulk-bar .card-body {
            padding: 0.6rem 0.85rem 0.5rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__grid {
            display: grid;
            gap: 0.45rem 0.75rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__summary {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.25rem 0.6rem;
            line-height: 1.2;
        }

        .all-leads-page-header .admin-leads-bulk-bar__summary .fw-semibold {
            font-size: 0.9rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__summary .small {
            font-size: 0.8rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__owner {
            min-width: min(100%, 11.5rem);
        }

        .all-leads-page-header .admin-leads-bulk-bar__owner .form-label {
            margin-bottom: 0.2rem !important;
            font-size: 0.78rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__owner .form-select {
            min-height: 1.95rem;
            padding-block: 0.2rem;
            font-size: 0.82rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            align-items: center;
        }

        .all-leads-page-header .admin-leads-bulk-bar__actions .btn {
            min-height: 1.95rem;
            padding: 0.26rem 0.68rem;
            font-size: 0.82rem;
            white-space: nowrap;
        }

        .all-leads-page-header .admin-leads-bulk-bar__feedback {
            margin-top: 0.2rem !important;
            font-size: 0.76rem;
            line-height: 1.3;
        }

        .all-leads-page .lead-bulk-cell {
            width: 3rem;
            min-width: 3rem;
            text-align: center;
            vertical-align: middle;
        }

        .all-leads-page .lead-bulk-toggle {
            float: none;
            margin: 0;
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

            .all-leads-page-header .admin-leads-bulk-bar__grid {
                grid-template-columns: auto minmax(11rem, 12.5rem) auto;
                align-items: center;
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
            const bulkForm = document.querySelector('[data-bulk-assign-form]');
            const bulkOwnerSelect = bulkForm?.querySelector('[data-bulk-owner-select]') ?? null;
            const bulkSubmitButton = bulkForm?.querySelector('[data-bulk-assign-submit]') ?? null;
            const bulkClearButton = bulkForm?.querySelector('[data-bulk-clear-selection]') ?? null;
            const bulkSelectedCount = bulkForm?.querySelector('[data-bulk-selected-count]') ?? null;
            const bulkSelectedInputs = bulkForm?.querySelector('[data-bulk-selected-inputs]') ?? null;
            const bulkFeedback = bulkForm?.querySelector('[data-bulk-feedback]') ?? null;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const pollIntervalMs = 4000;
            const selectedLeadIds = new Set();
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

            const updateSelectAllStates = () => {
                container.querySelectorAll('[data-lead-select-all]').forEach((toggle) => {
                    const table = toggle.closest('table');
                    if (!(table instanceof HTMLTableElement)) {
                        return;
                    }

                    const rowCheckboxes = [...table.querySelectorAll('[data-lead-select-row]')];
                    const checkedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;

                    toggle.checked = rowCheckboxes.length > 0 && checkedCount === rowCheckboxes.length;
                    toggle.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
                });
            };

            const renderBulkInputs = () => {
                if (!bulkSelectedInputs) {
                    return;
                }

                bulkSelectedInputs.replaceChildren();

                [...selectedLeadIds]
                    .sort((left, right) => Number(left) - Number(right))
                    .forEach((leadId) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'lead_ids[]';
                        input.value = leadId;
                        bulkSelectedInputs.appendChild(input);
                    });
            };

            const updateBulkControls = () => {
                const count = selectedLeadIds.size;
                const hasOwnerTarget = !!bulkOwnerSelect && bulkOwnerSelect.value !== '';

                if (bulkSelectedCount) {
                    bulkSelectedCount.textContent = String(count);
                }

                if (bulkSubmitButton) {
                    bulkSubmitButton.disabled = count === 0 || !hasOwnerTarget;
                }

                if (bulkClearButton) {
                    bulkClearButton.disabled = count === 0;
                }

                if (bulkFeedback) {
                    bulkFeedback.textContent = count === 0
                        ? 'בחר לידים מהטבלה ואז בחר משתמש אחד לשיוך.'
                        : hasOwnerTarget
                            ? `מוכנים לשיוך ${count} לידים.`
                            : `נבחרו ${count} לידים. בחר משתמש יעד כדי להמשיך.`;
                }

                renderBulkInputs();
            };

            const syncSelectionState = () => {
                const rowCheckboxes = [...container.querySelectorAll('[data-lead-select-row]')];
                const availableIds = new Set(rowCheckboxes.map((checkbox) => checkbox.value));

                [...selectedLeadIds].forEach((leadId) => {
                    if (!availableIds.has(leadId)) {
                        selectedLeadIds.delete(leadId);
                    }
                });

                rowCheckboxes.forEach((checkbox) => {
                    checkbox.checked = selectedLeadIds.has(checkbox.value);
                });

                updateSelectAllStates();
                updateBulkControls();
            };

            updateStickyOffsets();
            syncSelectionState();
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
                        syncSelectionState();
                        updateStickyOffsets();
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

            bulkOwnerSelect?.addEventListener('change', () => {
                updateBulkControls();
            });

            bulkClearButton?.addEventListener('click', () => {
                selectedLeadIds.clear();
                syncSelectionState();
            });

            bulkForm?.addEventListener('submit', (event) => {
                const hasOwnerTarget = !!bulkOwnerSelect && bulkOwnerSelect.value !== '';

                if (selectedLeadIds.size === 0 || !hasOwnerTarget) {
                    event.preventDefault();
                    updateBulkControls();
                }
            });

            container.addEventListener('change', async (event) => {
                const rowCheckbox = event.target.closest('[data-lead-select-row]');
                if (rowCheckbox instanceof HTMLInputElement) {
                    if (rowCheckbox.checked) {
                        selectedLeadIds.add(rowCheckbox.value);
                    } else {
                        selectedLeadIds.delete(rowCheckbox.value);
                    }

                    syncSelectionState();

                    return;
                }

                const selectAllCheckbox = event.target.closest('[data-lead-select-all]');
                if (selectAllCheckbox instanceof HTMLInputElement) {
                    const table = selectAllCheckbox.closest('table');

                    if (table instanceof HTMLTableElement) {
                        table.querySelectorAll('[data-lead-select-row]').forEach((checkbox) => {
                            if (!(checkbox instanceof HTMLInputElement)) {
                                return;
                            }

                            checkbox.checked = selectAllCheckbox.checked;

                            if (selectAllCheckbox.checked) {
                                selectedLeadIds.add(checkbox.value);
                            } else {
                                selectedLeadIds.delete(checkbox.value);
                            }
                        });
                    }

                    syncSelectionState();

                    return;
                }

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
