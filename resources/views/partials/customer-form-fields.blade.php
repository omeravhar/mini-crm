@php
    $customer = $customer ?? null;
    $isAdmin = auth()->user()?->isAdmin();
@endphp

<div class="row g-3">
    @if ($isAdmin)
        <div class="col-md-6">
            <label class="form-label" for="{{ $prefix }}_owner_id">אחראי</label>
            <select class="form-select" id="{{ $prefix }}_owner_id" name="owner_id">
                <option value="">ללא שיוך</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(old('owner_id', $customer?->owner_id) == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_lead_id">ליד מקושר</label>
        <input class="form-control" id="{{ $prefix }}_lead_id" name="lead_id" type="number" min="1" value="{{ old('lead_id', $customer?->lead_id) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_first_name">שם פרטי</label>
        <input class="form-control" id="{{ $prefix }}_first_name" name="first_name" value="{{ old('first_name', $customer?->first_name) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_last_name">שם משפחה</label>
        <input class="form-control" id="{{ $prefix }}_last_name" name="last_name" value="{{ old('last_name', $customer?->last_name) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_email">דוא"ל</label>
        <input class="form-control" id="{{ $prefix }}_email" name="email" type="email" value="{{ old('email', $customer?->email) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_phone">טלפון</label>
        <input class="form-control" id="{{ $prefix }}_phone" name="phone" value="{{ old('phone', $customer?->phone) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_company">חברה</label>
        <input class="form-control" id="{{ $prefix }}_company" name="company" value="{{ old('company', $customer?->company) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_job_title">תפקיד</label>
        <input class="form-control" id="{{ $prefix }}_job_title" name="job_title" value="{{ old('job_title', $customer?->job_title) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_website">אתר אינטרנט</label>
        <input class="form-control" id="{{ $prefix }}_website" name="website" type="url" value="{{ old('website', $customer?->website) }}">
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_street">רחוב</label>
        <input class="form-control" id="{{ $prefix }}_street" name="street" value="{{ old('street', $customer?->street) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}_city">עיר</label>
        <input class="form-control" id="{{ $prefix }}_city" name="city" value="{{ old('city', $customer?->city) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}_country">מדינה</label>
        <input class="form-control" id="{{ $prefix }}_country" name="country" value="{{ old('country', $customer?->country) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label" for="{{ $prefix }}_zip">מיקוד</label>
        <input class="form-control" id="{{ $prefix }}_zip" name="zip" value="{{ old('zip', $customer?->zip) }}">
    </div>

    <div class="col-12">
        <label class="form-label" for="{{ $prefix }}_notes">הערות</label>
        <textarea class="form-control" id="{{ $prefix }}_notes" name="notes" rows="3">{{ old('notes', $customer?->notes) }}</textarea>
    </div>
</div>
