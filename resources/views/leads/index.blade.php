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

@section('pageActions')
    <a class="btn btn-primary" href="{{ route('admin.leads.create') }}">יצירת ליד</a>
@endsection

@section('content')
    <form method="GET" action="{{ route('admin.leads.index') }}" class="card shadow-sm border-0 mb-4 admin-leads-filter" data-admin-leads-filter>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-xl-3">
                    <label class="form-label" for="q">חיפוש</label>
                    <input class="form-control" id="q" name="q" value="{{ $filters['q'] }}" placeholder="שם, דוא&quot;ל, חברה, טלפון, התעניינות או קמפיין">
                </div>
                <div class="col-sm-6 col-xl">
                    <label class="form-label" for="status">סטטוס</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">כל הסטטוסים</option>
                        @foreach ($options['statuses'] as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $statusLabels[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl">
                    <label class="form-label" for="priority">עדיפות</label>
                    <select class="form-select" id="priority" name="priority">
                        <option value="">כל העדיפויות</option>
                        @foreach ($options['priorities'] as $priority)
                            <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ $priorityLabels[$priority] ?? $priority }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl">
                    <label class="form-label" for="lead_type">סוג ליד</label>
                    <select class="form-select" id="lead_type" name="lead_type">
                        <option value="">כל סוגי הלידים</option>
                        @foreach ($options['lead_types'] as $leadType)
                            <option value="{{ $leadType }}" @selected($filters['lead_type'] === $leadType)>{{ $leadTypeLabels[$leadType] ?? $leadType }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl">
                    <label class="form-label" for="owner_id">אחראי</label>
                    <select class="form-select" id="owner_id" name="owner_id">
                        <option value="">כל האחראים</option>
                        <option value="unassigned" @selected($filters['owner_id'] === 'unassigned')>ללא שיוך</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($filters['owner_id'] === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl">
                    <label class="form-label" for="campaign">קמפיין</label>
                    <select class="form-select" id="campaign" name="campaign">
                        <option value="">כל הקמפיינים</option>
                        @foreach ($campaignOptions as $campaignValue => $campaignLabel)
                            <option value="{{ $campaignValue }}" @selected($filters['campaign'] === $campaignValue)>{{ $campaignLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl">
                    <label class="form-label" for="follow_up_scope">מעקב</label>
                    <select class="form-select" id="follow_up_scope" name="follow_up_scope">
                        <option value="">כל המעקבים</option>
                        @foreach ($followUpScopeLabels as $scope => $label)
                            <option value="{{ $scope }}" @selected($filters['follow_up_scope'] === $scope)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-xl-auto d-grid gap-2">
                    <button class="btn btn-dark" type="submit">סינון</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.leads.index') }}">ניקוי</a>
                </div>
            </div>
        </div>
    </form>

    <div id="adminLeadsContent">
        @include('leads.partials.admin-content', ['leads' => $leads, 'users' => $users])
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const container = document.getElementById('adminLeadsContent');
            const filter = document.querySelector('[data-admin-leads-filter]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const pollIntervalMs = 4000;
            let refreshPromise = null;

            if (!container) {
                return;
            }

            const updateStickyOffsets = () => {
                if (!filter) {
                    return;
                }

                const offset = window.matchMedia('(min-width: 992px)').matches
                    ? `${Math.ceil(filter.getBoundingClientRect().height)}px`
                    : '0px';

                document.documentElement.style.setProperty('--admin-leads-filter-offset', offset);
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
