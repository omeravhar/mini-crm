@php
    $showOwnerForm = $showOwnerForm ?? false;
    $users = $users ?? collect();
    $statusLabels = [
        'new' => 'חדש',
        'contacted' => 'נוצר קשר',
        'qualified' => 'מאושר',
        'proposal' => 'הצעה',
        'won' => 'נסגר בהצלחה',
        'lost' => 'אבוד',
    ];
    $priorityLabels = [
        'low' => 'נמוכה',
        'medium' => 'בינונית',
        'high' => 'גבוהה',
    ];
@endphp

<div class="table-responsive d-none d-lg-block">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>#</th>
                <th>שם</th>
                <th>חברה</th>
                <th>דוא"ל</th>
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
                    <td>{{ $lead->email }}</td>
                    <td style="min-width: 210px;">
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
                            {{ $lead->owner?->name ?: 'ללא שיוך' }}
                        @endif
                    </td>
                    <td>@include('partials.lead-status-badge', ['status' => $lead->status])</td>
                    <td>
                        <span class="badge text-bg-light border">{{ $priorityLabels[$lead->priority] ?? $lead->priority }}</span>
                    </td>
                    <td>{{ $lead->formatted_follow_up ?: 'לא נקבע' }}</td>
                    <td>
                        @if ($lead->customer)
                            <span class="badge text-bg-success">הומר ללקוח</span>
                        @else
                            <span class="badge text-bg-light border">עדיין לא</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
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
                    <td colspan="10" class="text-center text-muted py-4">לא נמצאו לידים.</td>
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
                    @include('partials.lead-status-badge', ['status' => $lead->status])
                    <span class="badge text-bg-light border">{{ $priorityLabels[$lead->priority] ?? $lead->priority }}</span>
                    @if ($lead->customer)
                        <span class="badge text-bg-success">הומר ללקוח</span>
                    @else
                        <span class="badge text-bg-light border">עדיין לא לקוח</span>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="small text-muted mb-1">דוא"ל</div>
                        <div>{{ $lead->email }}</div>
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
