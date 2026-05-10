@extends('layouts.crm')

@section('pageTitle', 'לוח בקרה')
@section('pageSubtitle', 'תמונת מצב חיה מתוך בסיס הנתונים')

@section('content')
    <style>
        .dashboard-stats .card-stat {
            min-height: 132px;
            padding: 0;
            border-radius: 20px;
        }

        .dashboard-stat-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            min-height: 132px;
            padding: 1.15rem 1.35rem;
        }

        .dashboard-stat-card__content {
            min-width: 0;
            display: grid;
            gap: 0.45rem;
        }

        .dashboard-stat-card__label {
            font-size: 0.85rem;
            line-height: 1.35;
        }

        .dashboard-stat-card__value {
            margin: 0;
            line-height: 1;
            letter-spacing: 0;
        }

        .dashboard-stat-icon {
            position: relative;
            isolation: isolate;
            flex: 0 0 auto;
            width: 4.85rem;
            aspect-ratio: 1;
            display: grid;
            place-items: center;
            border-radius: 999px;
        }

        .dashboard-stat-icon::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.98) 18%, rgba(248, 250, 252, 0.94) 100%);
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.14), 0 14px 26px rgba(15, 23, 42, 0.08);
            z-index: -2;
        }

        .dashboard-stat-icon::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(circle at 20% 78%, var(--icon-glow) 0, transparent 44%);
            opacity: 0.95;
            z-index: -1;
        }

        .dashboard-stat-icon svg {
            width: 2.55rem;
            height: 2.55rem;
            stroke: currentColor;
            fill: none;
            stroke-width: 2.05;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .dashboard-stat-icon--leads {
            color: #3ba1f8;
            --icon-glow: rgba(59, 161, 248, 0.28);
        }

        .dashboard-stat-icon--open {
            color: #22c55e;
            --icon-glow: rgba(34, 197, 94, 0.24);
        }

        .dashboard-stat-icon--pending {
            color: #f59e0b;
            --icon-glow: rgba(245, 158, 11, 0.24);
        }

        .dashboard-stat-icon--customers {
            color: #7c3aed;
            --icon-glow: rgba(124, 58, 237, 0.25);
        }

        .dashboard-stat-icon--users {
            color: #38bdf8;
            --icon-glow: rgba(56, 189, 248, 0.28);
        }

        @media (max-width: 575.98px) {
            .dashboard-stat-card {
                min-height: 116px;
                padding: 1rem 1.1rem;
            }

            .dashboard-stat-icon {
                width: 4rem;
            }

            .dashboard-stat-icon svg {
                width: 2.2rem;
                height: 2.2rem;
            }
        }
    </style>

    <div class="row g-4 mb-4 dashboard-stats">
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card card-stat card-leads h-100">
                <div class="card-body dashboard-stat-card">
                    <div class="dashboard-stat-card__content">
                        <div class="text-muted small dashboard-stat-card__label">סה"כ לידים</div>
                        <div class="display-6 fw-semibold dashboard-stat-card__value">{{ $stats['leads'] }}</div>
                    </div>
                    <div class="dashboard-stat-icon dashboard-stat-icon--leads" aria-hidden="true">
                        <svg viewBox="0 0 48 48">
                            <circle cx="24" cy="13.5" r="6.5" />
                            <path d="M13 35v-2.5c0-5.8 4.8-10.5 10.7-10.5s10.7 4.7 10.7 10.5V35" />
                            <circle cx="10.5" cy="17" r="4.8" />
                            <path d="M3.5 35v-1.8c0-4.6 3.7-8.3 8.3-8.3" />
                            <circle cx="37.5" cy="17" r="4.8" />
                            <path d="M44.5 35v-1.8c0-4.6-3.7-8.3-8.3-8.3" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card card-stat card-open h-100">
                <div class="card-body dashboard-stat-card">
                    <div class="dashboard-stat-card__content">
                        <div class="text-muted small dashboard-stat-card__label">לידים פתוחים</div>
                        <div class="display-6 fw-semibold dashboard-stat-card__value">{{ $stats['open_leads'] }}</div>
                    </div>
                    <div class="dashboard-stat-icon dashboard-stat-icon--open" aria-hidden="true">
                        <svg viewBox="0 0 48 48">
                            <circle cx="24" cy="24" r="15.5" />
                            <path d="m17.5 24.8 4.5 4.4 8.8-9.1" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card card-stat card-pending h-100">
                <div class="card-body dashboard-stat-card">
                    <div class="dashboard-stat-card__content">
                        <div class="text-muted small dashboard-stat-card__label">לא נוצר קשר</div>
                        <div class="display-6 fw-semibold dashboard-stat-card__value">{{ $stats['uncontacted_leads'] }}</div>
                    </div>
                    <div class="dashboard-stat-icon dashboard-stat-icon--pending" aria-hidden="true">
                        <svg viewBox="0 0 48 48">
                            <path d="M15 7.5h18" />
                            <path d="M15 40.5h18" />
                            <path d="M18 8v5.5c0 3.4 1.5 6.5 4.1 8.6l1.9 1.5-1.9 1.5c-2.6 2.1-4.1 5.2-4.1 8.6V40" />
                            <path d="M30 8v5.5c0 3.4-1.5 6.5-4.1 8.6L24 23.6l1.9 1.5c2.6 2.1 4.1 5.2 4.1 8.6V40" />
                            <path d="M19.5 10.5h9" />
                            <path d="M19.5 37.5h9" />
                            <path d="M21 30.5h6" />
                            <path d="m21.8 27.2 2.2-1.7 2.2 1.7" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card card-stat card-customers h-100">
                <div class="card-body dashboard-stat-card">
                    <div class="dashboard-stat-card__content">
                        <div class="text-muted small dashboard-stat-card__label">לקוחות</div>
                        <div class="display-6 fw-semibold dashboard-stat-card__value">{{ $stats['customers'] }}</div>
                    </div>
                    <div class="dashboard-stat-icon dashboard-stat-icon--customers" aria-hidden="true">
                        <svg viewBox="0 0 48 48">
                            <circle cx="19.5" cy="15" r="6.5" />
                            <path d="M8 36v-2.8c0-6 4.9-10.8 10.9-10.8s10.9 4.8 10.9 10.8V36" />
                            <path d="M37 15.5v12" />
                            <path d="M31 21.5h12" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        @if (! is_null($stats['users']))
            <div class="col-xl col-lg-4 col-md-6">
                <div class="card card-stat card-users h-100">
                    <div class="card-body dashboard-stat-card">
                        <div class="dashboard-stat-card__content">
                            <div class="text-muted small dashboard-stat-card__label">משתמשי EasyCRM</div>
                            <div class="display-6 fw-semibold dashboard-stat-card__value">{{ $stats['users'] }}</div>
                        </div>
                        <div class="dashboard-stat-icon dashboard-stat-icon--users" aria-hidden="true">
                            <svg viewBox="0 0 48 48">
                                <path d="M10 35V24" />
                                <path d="M19.5 35V16" />
                                <path d="M29 35V21" />
                                <path d="M7.5 35h25" />
                                <path d="m20 15 6-6 5 5" />
                                <path d="M26 9h5v5" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <div class="fw-semibold">לידים אחרונים</div>
                </div>
                <div class="table-responsive d-none d-lg-block">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>שם</th>
                                <th>סטטוס</th>
                                <th>אחראי</th>
                                <th>נוצר בתאריך</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentLeads as $lead)
                                <tr>
                                    <td>{{ $lead->full_name }}</td>
                                    <td>@include('partials.lead-status-badge', ['status' => $lead->status])</td>
                                    <td>{{ $lead->owner?->name ?: 'ללא שיוך' }}</td>
                                    <td>{{ $lead->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">אין עדיין לידים להצגה.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-grid gap-3 d-lg-none p-3">
                    @forelse ($recentLeads as $lead)
                        <div class="card mobile-record-card">
                            <div class="card-body d-grid gap-2">
                                <div class="fw-semibold">{{ $lead->full_name }}</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @include('partials.lead-status-badge', ['status' => $lead->status])
                                </div>
                                <div class="small text-muted">{{ $lead->owner?->name ?: 'ללא שיוך' }}</div>
                                <div class="small">{{ $lead->created_at->format('Y-m-d') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">אין עדיין לידים להצגה.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <div class="fw-semibold">משימות מעקב קרובות</div>
                </div>
                <div class="table-responsive d-none d-lg-block">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>שם</th>
                                <th>חברה</th>
                                <th>תאריך מעקב</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($upcomingLeads as $lead)
                                <tr>
                                    <td>{{ $lead->full_name }}</td>
                                    <td>{{ $lead->company ?: 'ללא חברה' }}</td>
                                    <td>{{ $lead->formatted_follow_up ?: 'לא נקבע' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">אין משימות מעקב מתוזמנות.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-grid gap-3 d-lg-none p-3">
                    @forelse ($upcomingLeads as $lead)
                        <div class="card mobile-record-card">
                            <div class="card-body d-grid gap-2">
                                <div class="fw-semibold">{{ $lead->full_name }}</div>
                                <div class="mobile-record-meta">{{ $lead->company ?: 'ללא חברה' }}</div>
                                <div class="small">מעקב: {{ $lead->formatted_follow_up ?: 'לא נקבע' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">אין משימות מעקב מתוזמנות.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <div class="fw-semibold">לקוחות אחרונים</div>
                </div>
                <div class="table-responsive d-none d-lg-block">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>שם</th>
                                <th>חברה</th>
                                <th>דוא"ל</th>
                                <th>אחראי</th>
                                <th>עודכן בתאריך</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentCustomers as $customer)
                                <tr>
                                    <td>{{ $customer->full_name }}</td>
                                    <td>{{ $customer->company ?: 'ללא חברה' }}</td>
                                    <td>{{ $customer->email }}</td>
                                    <td>{{ $customer->owner?->name ?: 'ללא שיוך' }}</td>
                                    <td>{{ $customer->updated_at->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">אין עדיין לקוחות להצגה.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-grid gap-3 d-lg-none p-3">
                    @forelse ($recentCustomers as $customer)
                        <div class="card mobile-record-card">
                            <div class="card-body d-grid gap-2">
                                <div class="fw-semibold">{{ $customer->full_name }}</div>
                                <div class="mobile-record-meta">{{ $customer->company ?: 'ללא חברה' }}</div>
                                <div>{{ $customer->email }}</div>
                                <div class="small text-muted">{{ $customer->owner?->name ?: 'ללא שיוך' }}</div>
                                <div class="small">עודכן: {{ $customer->updated_at->format('Y-m-d') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">אין עדיין לקוחות להצגה.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
