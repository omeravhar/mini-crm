@php
    $showOwnerForm = $showOwnerForm ?? false;
    $users = $users ?? collect();
    $statusLabels = $statusLabels ?? \App\Models\LeadStatus::labels();
    $statusSelectClasses = [
        'new' => 'lead-status-select--new',
        'contacted' => 'lead-status-select--contacted',
        'qualified' => 'lead-status-select--qualified',
        'proposal' => 'lead-status-select--proposal',
        'won' => 'lead-status-select--won',
        'lost' => 'lead-status-select--lost',
    ];
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
                <th>#</th>
                <th>שם</th>
                <th>חברה</th>
                <th>דוא"ל</th>
                <th>תאריך כניסה</th>
                <th>במה התעניין</th>
                <th>קמפיין</th>
                <th>סוג ליד</th>
                <th>אחראי</th>
                <th>סטטוס</th>
                <th>עדיפות</th>
                <th>מעקב</th>
                <th>לקוח</th>
                <th class="text-end">פעולות</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leads as $lead)
                <tr>
                    <td>{{ $lead->id }}</td>
                    <td>
                        <div class="fw-semibold">{{ $lead->full_name }}</div>
                        <div class="text-muted small">{{ $lead->phone ?: 'ללא טלפון' }}</div>
                    </td>
                    <td>{{ $lead->company ?: 'ללא חברה' }}</td>
                    <td>{{ $lead->email ?: 'ללא דוא"ל' }}</td>
                    <td>{{ $lead->formatted_entry_at ?: 'לא זמין' }}</td>
                    <td>{{ $lead->interested_in ?: 'לא צוין' }}</td>
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
                            >
                                @if ($lead->status && ! array_key_exists($lead->status, $statusLabels))
                                    <option value="{{ $lead->status }}" selected>{{ $lead->status }}</option>
                                @endif
                                @foreach ($statusLabels as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" @selected($lead->status === $statusValue)>{{ $statusLabel }}</option>
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
                    <td>
                        @if ($lead->customer)
                            <span class="badge text-bg-success">הומר ללקוח</span>
                        @else
                            <span class="badge text-bg-light border">עדיין לא</span>
                        @endif
                    </td>
                    <td class="text-end lead-actions-cell">
                        <div class="d-inline-flex lead-actions-group">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('leads.edit', $lead) }}">עריכה</a>
                            @if (! $lead->customer)
                                <form method="POST" action="{{ route('leads.convert', $lead) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">המרה ללקוח</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('למחוק את הליד הזה?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">מחיקה</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center text-muted py-4">לא נמצאו לידים.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-grid gap-3 d-lg-none">
    @forelse ($leads as $lead)
        <div class="card mobile-record-card">
            <div class="card-body d-grid gap-3">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="fw-semibold">{{ $lead->full_name }}</div>
                        <div class="mobile-record-meta">{{ $lead->company ?: 'ללא חברה' }}</div>
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
                            >
                                @if ($lead->status && ! array_key_exists($lead->status, $statusLabels))
                                    <option value="{{ $lead->status }}" selected>{{ $lead->status }}</option>
                                @endif
                                @foreach ($statusLabels as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" @selected($lead->status === $statusValue)>{{ $statusLabel }}</option>
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
                    @if ($lead->customer)
                        <span class="badge text-bg-success">הומר ללקוח</span>
                    @else
                        <span class="badge text-bg-light border">עדיין לא לקוח</span>
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
                    <div class="col-sm-6">
                        <div class="small text-muted mb-1">במה התעניין</div>
                        <div>{{ $lead->interested_in ?: 'לא צוין' }}</div>
                    </div>
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

                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary" href="{{ route('leads.edit', $lead) }}">עריכה</a>
                    @if (! $lead->customer)
                        <form method="POST" action="{{ route('leads.convert', $lead) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-success w-100">המרה ללקוח</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('למחוק את הליד הזה?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger w-100">מחיקה</button>
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
