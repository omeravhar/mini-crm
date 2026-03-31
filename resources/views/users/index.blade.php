@extends('layouts.crm')

@php
    $roleLabels = [
        'admin' => 'מנהל',
        'editor' => 'עורך',
        'viewer' => 'צופה',
    ];
@endphp

@section('pageTitle', 'משתמשי המערכת')
@section('pageSubtitle', 'יצירה, עדכון ומחיקה של משתמשי CRM')

@section('pageActions')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">הוספת משתמש</button>
@endsection

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>שם</th>
                            <th>דוא"ל</th>
                            <th>תפקיד</th>
                            <th>לידים</th>
                            <th>לקוחות</th>
                            <th class="text-end">פעולות</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $userModel)
                            <tr>
                                <td>{{ $userModel->id }}</td>
                                <td>{{ $userModel->name }}</td>
                                <td>{{ $userModel->email }}</td>
                                <td><span class="badge text-bg-secondary">{{ $roleLabels[$userModel->role] ?? $userModel->role }}</span></td>
                                <td>{{ $userModel->owned_leads_count }}</td>
                                <td>{{ $userModel->customers_count }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $userModel->id }}">עריכה</button>
                                        @if (! auth()->user()->is($userModel))
                                            <form method="POST" action="{{ route('admin.users.destroy', $userModel) }}" onsubmit="return confirm('למחוק את המשתמש הזה?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">מחיקה</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editUserModal{{ $userModel->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.users.update', $userModel) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">עריכת משתמש</h5>
                                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                @include('partials.user-form-fields', ['userModel' => $userModel, 'prefix' => 'edit_user_' . $userModel->id])
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">ביטול</button>
                                                <button class="btn btn-primary" type="submit">שמירת שינויים</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">לא נמצאו משתמשים.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-grid gap-3 d-lg-none">
                @forelse ($users as $userModel)
                    <div class="card mobile-record-card">
                        <div class="card-body d-grid gap-3">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $userModel->name }}</div>
                                    <div class="mobile-record-meta">{{ $userModel->email }}</div>
                                </div>
                                <span class="badge text-bg-secondary">{{ $roleLabels[$userModel->role] ?? $userModel->role }}</span>
                            </div>

                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="small text-muted mb-1">לידים</div>
                                    <div class="fw-semibold">{{ $userModel->owned_leads_count }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted mb-1">לקוחות</div>
                                    <div class="fw-semibold">{{ $userModel->customers_count }}</div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $userModel->id }}">עריכה</button>
                                @if (! auth()->user()->is($userModel))
                                    <form method="POST" action="{{ route('admin.users.destroy', $userModel) }}" onsubmit="return confirm('למחוק את המשתמש הזה?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger w-100" type="submit">מחיקה</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-4">לא נמצאו משתמשים.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">הוספת משתמש</h5>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @include('partials.user-form-fields', ['userModel' => null, 'prefix' => 'create_user'])
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">ביטול</button>
                        <button class="btn btn-primary" type="submit">יצירת משתמש</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
