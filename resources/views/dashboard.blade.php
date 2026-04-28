@extends('layouts.crm')

@section('pageTitle', 'לוח בקרה')
@section('pageSubtitle', 'תמונת מצב חיה מתוך בסיס הנתונים')

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="text-muted small">סה"כ לידים</div>
                    <div class="display-6 fw-semibold">{{ $stats['leads'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="text-muted small">לידים פתוחים</div>
                    <div class="display-6 fw-semibold">{{ $stats['open_leads'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="text-muted small">לא נוצר קשר</div>
                    <div class="display-6 fw-semibold">{{ $stats['uncontacted_leads'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl col-lg-4 col-md-6">
            <div class="card card-stat">
                <div class="card-body">
                    <div class="text-muted small">לקוחות</div>
                    <div class="display-6 fw-semibold">{{ $stats['customers'] }}</div>
                </div>
            </div>
        </div>
        @if (! is_null($stats['users']))
            <div class="col-xl col-lg-4 col-md-6">
                <div class="card card-stat">
                    <div class="card-body">
                        <div class="text-muted small">משתמשי EeasyCRM</div>
                        <div class="display-6 fw-semibold">{{ $stats['users'] }}</div>
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
