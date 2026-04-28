@extends('layouts.crm')

@section('pageTitle', 'ניהול סטטוסים')
@section('pageSubtitle', 'יצירה, עדכון ומחיקה של סטטוסים ללידים')

@section('content')
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">
            <div class="fw-semibold">סטטוס חדש</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.lead-statuses.store') }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-3">
                    <label class="form-label" for="name">שם סטטוס</label>
                    <input class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="slug">מזהה</label>
                    <input class="form-control" id="slug" name="slug" value="{{ old('slug') }}" placeholder="למשל: waiting_for_quote">
                    <div class="form-text">אפשר להשאיר ריק, המערכת תייצר מזהה אוטומטי.</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="badge_class">צבע</label>
                    <select class="form-select" id="badge_class" name="badge_class" required>
                        @foreach ($badgeOptions as $class => $label)
                            <option value="{{ $class }}" @selected(old('badge_class', 'text-bg-secondary') === $class)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="sort_order">סדר</label>
                    <input class="form-control" id="sort_order" name="sort_order" type="number" min="0" max="10000" value="{{ old('sort_order', 100) }}">
                </div>
                <div class="col-md-2">
                    <div class="form-check mb-2">
                        <input class="form-check-input" id="is_closed" name="is_closed" type="checkbox" value="1" @checked(old('is_closed'))>
                        <label class="form-check-label" for="is_closed">סטטוס סגור</label>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">יצירת סטטוס</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <div class="fw-semibold">סטטוסים קיימים</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>תצוגה</th>
                        <th>מזהה</th>
                        <th>שם</th>
                        <th>צבע</th>
                        <th>סדר</th>
                        <th>סגור</th>
                        <th>לידים</th>
                        <th class="text-end">פעולות</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($statuses as $status)
                        @php($formId = 'status-update-' . $status->id)
                        <tr>
                            <td>
                                <span class="badge {{ $status->badge_class }}">{{ $status->name }}</span>
                                @if ($status->is_system)
                                    <span class="badge text-bg-light border ms-1">מערכת</span>
                                @endif
                            </td>
                            <td><code>{{ $status->slug }}</code></td>
                            <td style="min-width: 180px;">
                                <input class="form-control form-control-sm" name="name" value="{{ $status->name }}" form="{{ $formId }}" required>
                            </td>
                            <td style="min-width: 130px;">
                                <select class="form-select form-select-sm" name="badge_class" form="{{ $formId }}" required>
                                    @foreach ($badgeOptions as $class => $label)
                                        <option value="{{ $class }}" @selected($status->badge_class === $class)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="width: 110px;">
                                <input class="form-control form-control-sm" name="sort_order" type="number" min="0" max="10000" value="{{ $status->sort_order }}" form="{{ $formId }}">
                            </td>
                            <td>
                                <input class="form-check-input" name="is_closed" type="checkbox" value="1" form="{{ $formId }}" @checked($status->is_closed)>
                            </td>
                            <td>{{ $status->leads_count }}</td>
                            <td class="text-end">
                                <form id="{{ $formId }}" method="POST" action="{{ route('admin.lead-statuses.update', $status) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                </form>
                                <button class="btn btn-sm btn-outline-primary" type="submit" form="{{ $formId }}">עדכון</button>

                                <form method="POST" action="{{ route('admin.lead-statuses.destroy', $status) }}" class="d-inline" onsubmit="return confirm('למחוק את הסטטוס הזה?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" @disabled($status->is_system || $status->leads_count > 0)>מחיקה</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">אין עדיין סטטוסים.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
