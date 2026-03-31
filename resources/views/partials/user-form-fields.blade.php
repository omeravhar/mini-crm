@php
    $userModel = $userModel ?? null;
    $roleLabels = [
        'admin' => 'מנהל',
        'editor' => 'עורך',
        'viewer' => 'צופה',
    ];
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_name">שם</label>
        <input class="form-control" id="{{ $prefix }}_name" name="name" value="{{ old('name', $userModel?->name) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_email">דוא"ל</label>
        <input class="form-control" id="{{ $prefix }}_email" name="email" type="email" value="{{ old('email', $userModel?->email) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_role">תפקיד</label>
        <select class="form-select" id="{{ $prefix }}_role" name="role" required>
            @foreach (['admin', 'editor', 'viewer'] as $role)
                <option value="{{ $role }}" @selected(old('role', $userModel?->role) === $role)>{{ $roleLabels[$role] }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_password">סיסמה {{ $userModel ? '(השאר ריק כדי לשמור את הקיימת)' : '' }}</label>
        <input class="form-control" id="{{ $prefix }}_password" name="password" type="password" {{ $userModel ? '' : 'required' }}>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $prefix }}_password_confirmation">אימות סיסמה</label>
        <input class="form-control" id="{{ $prefix }}_password_confirmation" name="password_confirmation" type="password" {{ $userModel ? '' : 'required' }}>
    </div>
</div>
