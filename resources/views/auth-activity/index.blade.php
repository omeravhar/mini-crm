@extends('layouts.crm')

@php
    $eventBadgeClasses = [
        'login_success' => 'text-bg-success',
        'login_failed' => 'text-bg-danger',
        'logout' => 'text-bg-secondary',
    ];

    $eventLabels = [
        'login_success' => 'התחברות מוצלחת',
        'login_failed' => 'כשלון התחברות',
        'logout' => 'התנתקות',
    ];
@endphp

@section('pageTitle', 'לוגי התחברות')
@section('pageSubtitle', 'מעקב אחרי התחברויות, כשלונות והתנתקויות של משתמשי המערכת')

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-stat h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">התחברויות מוצלחות</div>
                    <div class="display-6 fw-semibold">{{ number_format($summary['successful_logins']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-stat h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">כשלונות התחברות</div>
                    <div class="display-6 fw-semibold text-danger">{{ number_format($summary['failed_logins']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-stat h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">התנתקויות</div>
                    <div class="display-6 fw-semibold">{{ number_format($summary['logouts']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card card-stat h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">משתמשים פעילים</div>
                    <div class="display-6 fw-semibold">{{ number_format($summary['active_users']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.auth-activity.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">חיפוש</label>
                    <input
                        class="form-control"
                        name="q"
                        value="{{ $filters['q'] }}"
                        placeholder="שם משתמש, דוא&quot;ל, IP או סיבת כשל"
                    >
                </div>
                <div class="col-md-3">
                    <label class="form-label">סוג אירוע</label>
                    <select class="form-select" name="event_type">
                        <option value="">הכל</option>
                        @foreach ($eventOptions as $value => $label)
                            <option value="{{ $value }}" @selected($filters['event_type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">מתאריך</label>
                    <input class="form-control" name="from" type="date" value="{{ $filters['from'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">עד תאריך</label>
                    <input class="form-control" name="to" type="date" value="{{ $filters['to'] }}">
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary" type="submit">סנן</button>
                </div>
                <div class="col-12">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.auth-activity.index') }}">נקה פילטרים</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <div class="fw-semibold">מי באמת עובד עם המערכת</div>
                    <div class="small text-muted">משתמשים עם הכי הרבה התחברויות מוצלחות בטווח הנבחר</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>משתמש</th>
                                    <th>התחברויות</th>
                                    <th>כשלונות</th>
                                    <th>פעילות אחרונה</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topUsers as $user)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $user->name }}</div>
                                            <div class="small text-muted">{{ $user->email }}</div>
                                        </td>
                                        <td>{{ number_format((int) $user->successful_logins) }}</td>
                                        <td>{{ number_format((int) $user->failed_attempts) }}</td>
                                        <td>{{ \Illuminate\Support\Carbon::parse($user->last_seen_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">אין עדיין פעילות בטווח הזה.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <div class="fw-semibold">יומן התחברויות ושגיאות</div>
                    <div class="small text-muted">כולל משתמש, דוא"ל, IP, דפדפן וסיבת כשל כשההתחברות נכשלה</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>זמן</th>
                                    <th>אירוע</th>
                                    <th>משתמש</th>
                                    <th>דוא"ל</th>
                                    <th>IP</th>
                                    <th>סיבת כשל</th>
                                    <th>דפדפן</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td>{{ optional($log->occurred_at)->format('Y-m-d H:i:s') ?: $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <span class="badge {{ $eventBadgeClasses[$log->event_type] ?? 'text-bg-secondary' }}">
                                                {{ $eventLabels[$log->event_type] ?? $log->event_type }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($log->user)
                                                <div class="fw-semibold">{{ $log->user->name }}</div>
                                                <div class="small text-muted">#{{ $log->user->id }}</div>
                                            @else
                                                <span class="text-muted">לא זוהה</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->email ?: '-' }}</td>
                                        <td class="font-monospace">{{ $log->ip_address ?: '-' }}</td>
                                        <td>
                                            @if ($log->failure_reason)
                                                {{ $failureReasonLabels[$log->failure_reason] ?? $log->failure_reason }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td title="{{ $log->user_agent }}">
                                            {{ $log->user_agent ? \Illuminate\Support\Str::limit($log->user_agent, 60) : '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">אין עדיין לוגי התחברות.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($logs->hasPages())
                    <div class="card-footer bg-white">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
