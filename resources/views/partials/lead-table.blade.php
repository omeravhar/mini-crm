@php
    $showOwnerForm = $showOwnerForm ?? false;
    $enableBulkSelection = $enableBulkSelection ?? false;
    $users = $users ?? collect();
    $hiddenColumns = $hiddenColumns ?? [];
    $showCompanyColumn = ! in_array('company', $hiddenColumns, true);
    $showInterestedInColumn = ! in_array('interested_in', $hiddenColumns, true);
    $showCustomerColumn = ! in_array('customer', $hiddenColumns, true);
    $desktopColumnCount = 11
        + ($enableBulkSelection ? 1 : 0)
        + ($showCompanyColumn ? 1 : 0)
        + ($showInterestedInColumn ? 1 : 0)
        + ($showCustomerColumn ? 1 : 0);
    $statusLabels = $statusLabels ?? \App\Models\LeadStatus::labels();
    $statusSelectClasses = $statusSelectClasses ?? \App\Models\LeadStatus::selectThemeClasses();
    $priorityLabels = [
        'low' => 'נמוכה',
        'medium' => 'בינונית',
        'high' => 'גבוהה',
    ];
    $leadTypeBadgeClasses = [
        'new' => 'text-bg-info',
        'returning' => 'text-bg-warning',
    ];
@endphp

<div class="table-responsive d-none d-lg-block lead-table-responsive">
    <table class="table table-hover align-middle lead-management-table">
        <thead>
            <tr>
                @if ($enableBulkSelection)
                    <th class="lead-bulk-cell">
                        <input
                            class="form-check-input lead-bulk-toggle"
                            type="checkbox"
                            value="1"
                            data-lead-select-all
                            aria-label="בחר את כל הלידים בטבלה"
                        >
                    </th>
                @endif
                <th>#</th>
                <th>שם</th>
                @if ($showCompanyColumn)
                    <th>חברה</th>
                @endif
                <th>דוא"ל</th>
                <th>תאריך כניסה</th>
                @if ($showInterestedInColumn)
                    <th>במה התעניין</th>
                @endif
                <th>קמפיין</th>
                <th>סוג ליד</th>
                <th>אחראי</th>
                <th>סטטוס</th>
                <th>עדיפות</th>
                <th>מעקב</th>
                @if ($showCustomerColumn)
                    <th>לקוח</th>
                @endif
                <th class="text-end">פעולות</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leads as $lead)
                <tr>
                    @if ($enableBulkSelection)
                        <td class="lead-bulk-cell">
                            <input
                                class="form-check-input lead-bulk-toggle"
                                type="checkbox"
                                value="{{ $lead->id }}"
                                data-lead-select-row
                                aria-label="בחר את {{ $lead->full_name }}"
                            >
                        </td>
                    @endif
                    <td>{{ $lead->id }}</td>
                    <td>
                        <div class="fw-semibold">{{ $lead->full_name }}</div>
                        <div class="text-muted small">{{ $lead->phone ?: 'ללא טלפון' }}</div>
                    </td>
                    @if ($showCompanyColumn)
                        <td>{{ $lead->company ?: 'ללא חברה' }}</td>
                    @endif
                    <td>{{ $lead->email ?: 'ללא דוא"ל' }}</td>
                    <td>{{ $lead->formatted_entry_at ?: 'לא זמין' }}</td>
                    @if ($showInterestedInColumn)
                        <td>{{ $lead->interested_in ?: 'לא צוין' }}</td>
                    @endif
                    <td>{{ $lead->campaign_display ?: 'ללא קמפיין' }}</td>
                    <td><span class="badge {{ $leadTypeBadgeClasses[$lead->lead_type] ?? 'text-bg-secondary' }}">{{ $lead->lead_type_label }}</span></td>
                    <td class="lead-owner-cell">
                        @if ($showOwnerForm)
                            <form method="POST" action="{{ route('admin.leads.assign', $lead) }}" data-lead-assign-form>
                                @csrf
                                <select name="owner_id" class="form-select form-select-sm lead-compact-select" data-lead-owner-select>
                                    <option value="">ללא שיוך</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected($lead->owner_id === $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <noscript>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary mt-2">שמירה</button>
                                </noscript>
                            </form>
                        @else
                            {{ $lead->owner?->name ?: 'ללא שיוך' }}
                        @endif
                    </td>
                    <td class="lead-status-cell">
                        <form method="POST" action="{{ route('leads.quick-update', $lead) }}" data-lead-quick-form>
                            @csrf
                            <select
                                name="status"
                                class="form-select form-select-sm lead-compact-select lead-status-select {{ $statusSelectClasses[$lead->status] ?? 'lead-status-select--default' }}"
                                data-lead-quick-select
                                data-lead-status-select
                                data-previous-value="{{ $lead->status }}"
                                data-current-archive-reason="{{ $lead->archive_reason ?? '' }}"
                            >
                                @if ($lead->status && ! array_key_exists($lead->status, $statusLabels))
                                    <option value="{{ $lead->status }}" selected data-status-theme="lead-status-select--default">{{ $lead->status }}</option>
                                @endif
                                @foreach ($statusLabels as $statusValue => $statusLabel)
                                    <option
                                        value="{{ $statusValue }}"
                                        data-status-theme="{{ $statusSelectClasses[$statusValue] ?? 'lead-status-select--default' }}"
                                        @selected($lead->status === $statusValue)
                                    >{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td class="lead-priority-cell">
                        <form method="POST" action="{{ route('leads.quick-update', $lead) }}" data-lead-quick-form>
                            @csrf
                            <select name="priority" class="form-select form-select-sm lead-compact-select" data-lead-quick-select>
                                @foreach ($priorityLabels as $priorityValue => $priorityLabel)
                                    <option value="{{ $priorityValue }}" @selected($lead->priority === $priorityValue)>{{ $priorityLabel }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>{{ $lead->formatted_follow_up ?: 'לא נקבע' }}</td>
                    @if ($showCustomerColumn)
                        <td>
                            @if ($lead->customer)
                                <span class="badge text-bg-success">הומר ללקוח</span>
                            @else
                                <span class="badge text-bg-light border">עדיין לא</span>
                            @endif
                        </td>
                    @endif
                    <td class="text-end lead-actions-cell">
                        <div class="icon-action-group lead-actions-group">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('leads.edit', $lead) }}" title="עריכה" aria-label="עריכה"><i class="bi bi-pencil-square icon-action-icon" aria-hidden="true"></i><span class="visually-hidden">עריכה</span></a>
                            @if (! $lead->customer)
                                <form method="POST" action="{{ route('leads.convert', $lead) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="המרה ללקוח" aria-label="המרה ללקוח"><i class="bi bi-person-check icon-action-icon icon-action-icon--success" aria-hidden="true"></i><span class="visually-hidden">המרה ללקוח</span></button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('למחוק את הליד הזה?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="מחיקה" aria-label="מחיקה"><i class="bi bi-trash3 icon-action-icon" aria-hidden="true"></i><span class="visually-hidden">מחיקה</span></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $desktopColumnCount }}" class="text-center text-muted py-4">לא נמצאו לידים.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-grid gap-3 d-lg-none">
    @forelse ($leads as $lead)
        <div class="card mobile-record-card">
            <div class="card-body d-grid gap-3">
                @if ($enableBulkSelection)
                    <div class="d-flex justify-content-end">
                        <label class="form-check d-inline-flex align-items-center gap-2 small mb-0">
                            <input
                                class="form-check-input lead-bulk-toggle mt-0"
                                type="checkbox"
                                value="{{ $lead->id }}"
                                data-lead-select-row
                            >
                            <span>בחירה</span>
                        </label>
                    </div>
                @endif
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="fw-semibold">{{ $lead->full_name }}</div>
                        @if ($showCompanyColumn)
                            <div class="mobile-record-meta">{{ $lead->company ?: 'ללא חברה' }}</div>
                        @endif
                    </div>
                    <div class="text-start">
                        <div class="small text-muted">#{{ $lead->id }}</div>
                        <div class="small text-muted">{{ $lead->phone ?: 'ללא טלפון' }}</div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <div class="flex-grow-1" style="min-width: 150px;">
                        <div class="small text-muted mb-1">סטטוס</div>
                        <form method="POST" action="{{ route('leads.quick-update', $lead) }}" data-lead-quick-form>
                            @csrf
                            <select
                                name="status"
                                class="form-select form-select-sm lead-status-select {{ $statusSelectClasses[$lead->status] ?? 'lead-status-select--default' }}"
                                data-lead-quick-select
                                data-lead-status-select
                                data-previous-value="{{ $lead->status }}"
                                data-current-archive-reason="{{ $lead->archive_reason ?? '' }}"
                            >
                                @if ($lead->status && ! array_key_exists($lead->status, $statusLabels))
                                    <option value="{{ $lead->status }}" selected data-status-theme="lead-status-select--default">{{ $lead->status }}</option>
                                @endif
                                @foreach ($statusLabels as $statusValue => $statusLabel)
                                    <option
                                        value="{{ $statusValue }}"
                                        data-status-theme="{{ $statusSelectClasses[$statusValue] ?? 'lead-status-select--default' }}"
                                        @selected($lead->status === $statusValue)
                                    >{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="flex-grow-1" style="min-width: 150px;">
                        <div class="small text-muted mb-1">עדיפות</div>
                        <form method="POST" action="{{ route('leads.quick-update', $lead) }}" data-lead-quick-form>
                            @csrf
                            <select name="priority" class="form-select form-select-sm" data-lead-quick-select>
                                @foreach ($priorityLabels as $priorityValue => $priorityLabel)
                                    <option value="{{ $priorityValue }}" @selected($lead->priority === $priorityValue)>{{ $priorityLabel }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    @if ($showCustomerColumn)
                        @if ($lead->customer)
                            <span class="badge text-bg-success">הומר ללקוח</span>
                        @else
                            <span class="badge text-bg-light border">עדיין לא לקוח</span>
                        @endif
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="small text-muted mb-1">דוא"ל</div>
                        <div>{{ $lead->email ?: 'ללא דוא"ל' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted mb-1">תאריך כניסה</div>
                        <div>{{ $lead->formatted_entry_at ?: 'לא זמין' }}</div>
                    </div>
                    @if ($showInterestedInColumn)
                        <div class="col-sm-6">
                            <div class="small text-muted mb-1">במה התעניין</div>
                            <div>{{ $lead->interested_in ?: 'לא צוין' }}</div>
                        </div>
                    @endif
                    <div class="col-sm-6">
                        <div class="small text-muted mb-1">קמפיין</div>
                        <div>{{ $lead->campaign_display ?: 'ללא קמפיין' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted mb-1">סוג ליד</div>
                        <div><span class="badge {{ $leadTypeBadgeClasses[$lead->lead_type] ?? 'text-bg-secondary' }}">{{ $lead->lead_type_label }}</span></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted mb-1">מעקב הבא</div>
                        <div>{{ $lead->formatted_follow_up ?: 'לא נקבע' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="small text-muted mb-1">אחראי</div>
                        @if ($showOwnerForm)
                            <form method="POST" action="{{ route('admin.leads.assign', $lead) }}" data-lead-assign-form>
                                @csrf
                                <select name="owner_id" class="form-select form-select-sm" data-lead-owner-select>
                                    <option value="">ללא שיוך</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected($lead->owner_id === $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <noscript>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary mt-2">שמירה</button>
                                </noscript>
                            </form>
                        @else
                            <div>{{ $lead->owner?->name ?: 'ללא שיוך' }}</div>
                        @endif
                    </div>
                </div>

                <div class="icon-action-group table-action-group--mobile">
                    <a class="btn btn-outline-primary" href="{{ route('leads.edit', $lead) }}" title="עריכה" aria-label="עריכה"><i class="bi bi-pencil-square icon-action-icon" aria-hidden="true"></i><span class="visually-hidden">עריכה</span></a>
                    @if (! $lead->customer)
                        <form method="POST" action="{{ route('leads.convert', $lead) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-success w-100" title="המרה ללקוח" aria-label="המרה ללקוח"><i class="bi bi-person-check icon-action-icon icon-action-icon--success" aria-hidden="true"></i><span class="visually-hidden">המרה ללקוח</span></button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('למחוק את הליד הזה?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100" title="מחיקה" aria-label="מחיקה"><i class="bi bi-trash3 icon-action-icon" aria-hidden="true"></i><span class="visually-hidden">מחיקה</span></button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-4">לא נמצאו לידים.</div>
        </div>
    @endforelse
</div>
