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
        $filters['owner_id'] ?? '',
        $filters['campaign'] ?? '',
        $filters['follow_up_scope'] ?? '',
        $filters['summary_scope'] ?? '',
    ])->filter(fn ($value) => filled($value))->count();
@endphp

@section('pageTitle', 'כל הלידים')
@section('pageSubtitle', 'ניהול, שיוך, המרה ועדכון של כל הלידים במערכת')
@section('pageHeaderClass', 'all-leads-page-header')

@section('pageActions')
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <a class="btn btn-outline-secondary" href="{{ route('admin.leads.archive') }}">ארכיון לידים</a>
        <a class="btn btn-primary" href="{{ route('admin.leads.create') }}">יצירת ליד</a>
    </div>
@endsection
@section('pageHeaderExtras')
    <form method="GET" action="{{ route('admin.leads.index') }}" class="card mb-4 admin-leads-filter children-filters-card" data-admin-leads-filter>
        <div class="card-body p-0">
            <div class="children-filters-header visually-hidden">
                <div class="children-page-title-wrap">
                    <h2 class="children-page-title">כל הלידים</h2>
                    <p class="children-page-subtitle">ניהול, שיוך, המרה ועדכון של כל הלידים במערכת</p>
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
                <div class="filter-col filter-col-handler admin-leads-filter__select">
                    <label class="form-label" for="owner_id">מטפל</label>
                    <select class="form-select" id="owner_id" name="owner_id">
                        <option value="">כל המטפלים</option>
                        <option value="unassigned" @selected($filters['owner_id'] === 'unassigned')>ללא שיוך</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected($filters['owner_id'] === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="children-filters-footer">
                <div class="filters-footer-right">
                    <button class="btn btn-primary" type="submit">סינון</button>
                    <a class="btn btn-secondary d-inline-flex align-items-center justify-content-center text-decoration-none" href="{{ route('admin.leads.index') }}">ניקוי</a>
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
            margin-bottom: 0.6rem;
        }

        .all-leads-page-header .page-header-bar {
            margin-bottom: 0.45rem;
            padding-right: 0.3rem;
        }

        .all-leads-page-header .admin-leads-filter.children-filters-card {
            --filters-card-padding: 16px;
            --filters-gap: 10px;
            --filters-control-height: 40px;
            --filters-control-radius: 11px;
            padding: 16px;
            overflow: hidden;
            container-type: normal;
        }

        .all-leads-page-header .admin-leads-filter.children-filters-card .card-body {
            padding: 0 !important;
        }

        .all-leads-page-header .admin-leads-filter__grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .all-leads-page-header .admin-leads-filter .filter-col {
            gap: 4px;
        }

        .all-leads-page-header .admin-leads-filter.children-filters-card .form-label {
            margin-bottom: 0;
            font-size: 12px;
            white-space: normal;
        }

        .all-leads-page-header .admin-leads-filter.children-filters-card .form-control,
        .all-leads-page-header .admin-leads-filter.children-filters-card .form-select {
            min-height: 40px;
            padding: 0 12px;
            font-size: 13px;
            white-space: normal;
        }

        .all-leads-page-header .admin-leads-filter.children-filters-card .btn {
            min-height: 40px;
            padding: 0 15px;
            font-size: 13px;
        }

        .all-leads-page-header .admin-leads-filter.children-filters-card .filter-col-search {
            grid-column: span 2;
            width: auto;
        }

        .all-leads-page-header .admin-leads-filter__date,
        .all-leads-page-header .admin-leads-filter__select {
            min-width: 0;
        }

        .all-leads-page-header .children-filters-footer {
            grid-template-columns: auto minmax(180px, 1fr) auto;
            gap: 10px 14px;
            padding-top: 12px;
        }

        .all-leads-page-header .active-filters-box {
            min-height: 52px;
            min-width: 168px;
            width: min(100%, 220px);
            padding: 10px 14px;
            gap: 8px;
            border-radius: 12px;
        }

        .all-leads-page-header .active-filters-box strong {
            font-size: 12px;
        }

        .all-leads-page-header .active-filters-count {
            margin-top: 1px;
            font-size: 18px;
        }

        .all-leads-page-header .active-filters-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            font-size: 14px;
        }

        .all-leads-page-header .admin-leads-bulk-bar {
            margin-bottom: 0;
            padding: 0;
            border-radius: 14px;
        }

        .all-leads-page-header .admin-leads-bulk-bar .card-body {
            padding: 0.45rem 0.7rem 0.4rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__grid {
            display: grid;
            gap: 0.35rem 0.6rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__summary {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.2rem 0.45rem;
            line-height: 1.1;
        }

        .all-leads-page-header .admin-leads-bulk-bar__summary .fw-semibold {
            font-size: 0.82rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__summary .small {
            font-size: 0.74rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__owner {
            min-width: min(100%, 10.25rem);
        }

        .all-leads-page-header .admin-leads-bulk-bar__owner .form-label {
            margin-bottom: 0.1rem !important;
            font-size: 0.72rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__owner .form-select {
            min-height: 1.8rem;
            padding-block: 0.14rem;
            font-size: 0.78rem;
        }

        .all-leads-page-header .admin-leads-bulk-bar__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            align-items: center;
        }

        .all-leads-page-header .admin-leads-bulk-bar__actions .btn {
            min-height: 1.8rem;
            padding: 0.22rem 0.58rem;
            font-size: 0.78rem;
            white-space: nowrap;
        }

        .all-leads-page-header .admin-leads-bulk-bar__feedback {
            margin-top: 0.1rem !important;
            font-size: 0.72rem;
            line-height: 1.2;
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

        .all-leads-page .admin-leads-summary {
            --bs-gutter-x: 0.75rem;
            --bs-gutter-y: 0.75rem;
            margin-bottom: 0.75rem !important;
        }

        .all-leads-page .admin-leads-summary > [class*="col-"] > .card {
            padding: 0;
            border-radius: 16px;
        }

        .all-leads-page .admin-leads-summary__link {
            display: block;
            height: 100%;
            color: inherit;
            text-decoration: none;
            border-radius: 16px;
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .all-leads-page .admin-leads-summary__link:hover {
            transform: translateY(-1px);
        }

        .all-leads-page .admin-leads-summary__link:focus-visible {
            outline: 3px solid rgba(37, 99, 235, 0.16);
            outline-offset: 3px;
        }

        .all-leads-page .admin-leads-summary__link.is-active > .card {
            box-shadow: 0 0 0 2px #2563eb, 0 12px 28px -24px rgba(37, 99, 235, 0.65) !important;
        }

        .all-leads-page .admin-leads-summary > [class*="col-"] > .card:not(.admin-leads-summary__card--leads) > .card-body {
            min-height: 92px;
            padding: 0.8rem 1rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.15rem;
        }

        .all-leads-page .admin-leads-summary > [class*="col-"] > .card:not(.admin-leads-summary__card--leads) .text-muted.small {
            font-size: 0.76rem !important;
            line-height: 1.2;
        }

        .all-leads-page .admin-leads-summary > [class*="col-"] > .card:not(.admin-leads-summary__card--leads) .display-6 {
            font-size: clamp(2rem, 2vw, 2.35rem);
            line-height: 1;
        }

        .all-leads-page .card-stat .card-body {
            min-height: 92px;
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            gap: 0.15rem;
            padding: 0.8rem 1rem;
        }

        .all-leads-page .admin-leads-summary__card--leads {
            min-height: 92px;
            border-radius: 16px;
        }

        .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-direction: row;
            flex-wrap: nowrap;
            gap: 0.65rem;
            min-height: 92px;
            padding: 0.8rem 1rem;
        }

        .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-card__content {
            min-width: 0;
            display: grid;
            gap: 0.15rem;
        }

        .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-card__label {
            font-size: 0.76rem;
            line-height: 1.15;
        }

        .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-card__value {
            margin: 0;
            line-height: 1;
            letter-spacing: 0;
        }

        .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-icon {
            position: relative;
            isolation: isolate;
            flex: 0 0 auto;
            width: 3.35rem;
            aspect-ratio: 1;
            display: grid;
            place-items: center;
            border-radius: 999px;
            color: #3ba1f8;
            --icon-glow: rgba(59, 161, 248, 0.28);
        }

        .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-icon::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 18%, rgba(248, 250, 252, 0.94) 100%);
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.14), 0 14px 26px rgba(15, 23, 42, 0.08);
            z-index: -2;
        }

        .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-icon::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(circle at 20% 78%, var(--icon-glow) 0, transparent 44%);
            opacity: 0.95;
            z-index: -1;
        }

        .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-icon svg {
            width: 1.8rem;
            height: 1.8rem;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.05;
            stroke-linecap: round;
            stroke-linejoin: round;
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

            .all-leads-page-header .admin-leads-filter__grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }

            .all-leads-page-header .admin-leads-bulk-bar__grid {
                grid-template-columns: auto minmax(9.75rem, 11rem) auto;
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

        @media (max-width: 1199.98px) {
            .all-leads-page-header .admin-leads-filter__grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }

            .all-leads-page-header .admin-leads-filter.children-filters-card .filter-col-search {
                grid-column: span 2;
            }
        }

        @media (min-width: 992px) and (max-width: 1399.98px) {
            .all-leads-page-header {
                position: static;
            }

            .all-leads-page-header .page-header-bar {
                gap: 0.65rem 0.9rem;
                margin-bottom: 0.55rem;
                padding-right: 0;
            }

            .all-leads-page-header .page-header-bar .text-muted {
                display: none;
            }

            .all-leads-page-header .page-header-bar .h3 {
                font-size: 1.9rem;
            }

            .all-leads-page-header .page-actions {
                gap: 0.45rem !important;
            }

            .all-leads-page-header .page-actions .btn {
                min-height: 2.2rem;
                padding: 0.32rem 0.8rem;
                font-size: 0.82rem;
            }

            .all-leads-page-header .admin-leads-filter {
                position: sticky;
                top: 0;
                z-index: 6;
                margin-bottom: 0.55rem !important;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.10);
            }

            .all-leads-page-header .admin-leads-filter.children-filters-card {
                --filters-card-padding: 12px;
                --filters-gap: 8px;
                --filters-control-height: 36px;
                --filters-control-radius: 10px;
                padding: 12px;
                border-radius: 16px;
            }

            .all-leads-page-header .admin-leads-filter__grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
                gap: 8px;
                margin-bottom: 8px;
            }

            .all-leads-page-header .admin-leads-filter .filter-col {
                gap: 3px;
            }

            .all-leads-page-header .admin-leads-filter.children-filters-card .form-label {
                font-size: 11px;
            }

            .all-leads-page-header .admin-leads-filter.children-filters-card .form-control,
            .all-leads-page-header .admin-leads-filter.children-filters-card .form-select {
                min-height: 36px;
                padding: 0 10px;
                font-size: 12px;
            }

            .all-leads-page-header .admin-leads-filter.children-filters-card .btn {
                min-height: 36px;
                padding: 0 12px;
                font-size: 12px;
            }

            .all-leads-page-header .children-filters-footer {
                grid-template-columns: auto minmax(150px, 1fr) auto;
                gap: 8px 10px;
                padding-top: 8px;
            }

            .all-leads-page-header .filters-footer-right,
            .all-leads-page-header .filters-footer-left {
                gap: 8px;
            }

            .all-leads-page-header .active-filters-box {
                min-height: 42px;
                min-width: 140px;
                width: min(100%, 190px);
                padding: 8px 10px;
                gap: 6px;
                border-radius: 10px;
            }

            .all-leads-page-header .active-filters-box strong {
                font-size: 11px;
            }

            .all-leads-page-header .active-filters-count {
                font-size: 16px;
            }

            .all-leads-page-header .active-filters-icon {
                width: 28px;
                height: 28px;
                border-radius: 8px;
                font-size: 12px;
            }

            .all-leads-page-header .admin-leads-bulk-bar {
                position: sticky;
                top: var(--admin-leads-filter-height, 0px);
                z-index: 5;
                border-radius: 12px;
            }

            .all-leads-page-header .admin-leads-bulk-bar .card-body {
                padding: 0.35rem 0.55rem;
            }

            .all-leads-page-header .admin-leads-bulk-bar__grid {
                grid-template-columns: auto minmax(8.5rem, 10rem) auto;
                gap: 0.25rem 0.5rem;
                align-items: center;
            }

            .all-leads-page-header .admin-leads-bulk-bar__summary .fw-semibold {
                font-size: 0.78rem;
            }

            .all-leads-page-header .admin-leads-bulk-bar__summary .small {
                font-size: 0.7rem;
            }

            .all-leads-page-header .admin-leads-bulk-bar__owner .form-label {
                font-size: 0.68rem;
            }

            .all-leads-page-header .admin-leads-bulk-bar__owner .form-select {
                min-height: 1.75rem;
                padding-block: 0.08rem;
                font-size: 0.75rem;
            }

            .all-leads-page-header .admin-leads-bulk-bar__actions {
                gap: 0.25rem;
            }

            .all-leads-page-header .admin-leads-bulk-bar__actions .btn {
                min-height: 1.75rem;
                padding: 0.18rem 0.5rem;
                font-size: 0.74rem;
            }

            .all-leads-page-header .admin-leads-bulk-bar__feedback {
                margin-top: 0.15rem !important;
                font-size: 0.68rem;
                line-height: 1.15;
            }
        }

        @media (max-width: 991.98px) {
            .all-leads-page-header .admin-leads-filter__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .all-leads-page-header .admin-leads-filter.children-filters-card .filter-col-search {
                grid-column: span 2;
            }

            .all-leads-page-header .children-filters-footer {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .all-leads-page-header .admin-leads-filter__grid {
                grid-template-columns: 1fr;
            }

            .all-leads-page-header .admin-leads-filter.children-filters-card .filter-col-search {
                grid-column: auto;
            }

            .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-card {
                min-height: 84px;
                padding: 0.7rem 0.85rem;
            }

            .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-icon {
                width: 3rem;
            }

            .all-leads-page .admin-leads-summary__card--leads .dashboard-stat-icon svg {
                width: 1.6rem;
                height: 1.6rem;
            }

            .all-leads-page .admin-leads-summary > [class*="col-"] > .card:not(.admin-leads-summary__card--leads) > .card-body {
                min-height: 84px;
                padding: 0.7rem 0.85rem;
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
            const filterForm = document.querySelector('[data-admin-leads-filter]');
            const activeFiltersCount = filterForm?.querySelector('[data-active-filters-count]') ?? null;
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
                const isLaptop = window.matchMedia('(min-width: 992px) and (max-width: 1399.98px)').matches;
                let filterHeight = 0;
                let stickyOffset = 0;

                if (isLaptop && filterForm) {
                    const filterStyles = window.getComputedStyle(filterForm);
                    const filterMarginBottom = Number.parseFloat(filterStyles.marginBottom) || 0;
                    filterHeight = Math.ceil(filterForm.getBoundingClientRect().height + filterMarginBottom);

                    const bulkHeight = bulkForm
                        ? Math.ceil(bulkForm.getBoundingClientRect().height)
                        : 0;

                    stickyOffset = filterHeight + bulkHeight;
                } else if (isDesktop && pageHeader) {
                    stickyOffset = Math.ceil(pageHeader.getBoundingClientRect().height);
                }

                document.documentElement.style.setProperty('--admin-leads-filter-height', `${filterHeight}px`);
                document.documentElement.style.setProperty('--admin-leads-filter-offset', `${stickyOffset}px`);
            };

            const updateActiveFiltersCount = () => {
                if (!filterForm || !activeFiltersCount) {
                    return;
                }

                const count = [...new FormData(filterForm).entries()]
                    .filter(([, value]) => String(value ?? '').trim() !== '')
                    .length;

                activeFiltersCount.textContent = String(count);
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
            updateActiveFiltersCount();
            syncSelectionState();
            window.addEventListener('resize', updateStickyOffsets);
            filterForm?.addEventListener('input', updateActiveFiltersCount);
            filterForm?.addEventListener('change', updateActiveFiltersCount);

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
            const submitQuickForm = async (form, changedSelect, errorMessage) => {
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
                        const error = new Error(responseData?.message ?? errorMessage);
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

                    await submitQuickForm(quickForm, quickSelect, 'Failed to update lead.');

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
